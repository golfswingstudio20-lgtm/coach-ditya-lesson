<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * Jadwal otomatis (Admin > Jadwal Otomatis):
 *  - agenda   : agenda lesson hari ini -> WA admin + lonceng admin (tiap pagi)
 *  - reminder : pengingat lesson besok -> lonceng (+ email jika diatur) murid (tiap sore)
 *  - weekly   : laporan 7 hari terakhir -> WA admin + lonceng (Senin pagi)
 *  - monthly  : laporan bulan lalu -> WA admin + lonceng (tanggal 1)
 * Setiap tugas hanya jalan 1x per hari/periode walau cron terpanggil dua kali.
 */
class AutoReports
{
    public const JOBS = [
        'agenda'   => ['Agenda harian ke WA admin', 'Setiap pagi ±06:00 WIB'],
        'reminder' => ['Pengingat lesson H-1 ke murid', 'Setiap sore ±17:00 WIB'],
        'weekly'   => ['Laporan mingguan ke WA admin', 'Setiap Senin ±08:00 WIB'],
        'monthly'  => ['Laporan bulanan ke WA admin', 'Setiap tanggal 1 ±08:00 WIB'],
    ];

    public static function enabled(string $job): bool
    {
        return Setting::get('auto_' . $job, '1') === '1';
    }

    public static function lastRun(string $job): ?array
    {
        $data = json_decode((string) Setting::get('auto_last_' . $job), true);

        return is_array($data) ? $data : null;
    }

    /**
     * Jalankan satu tugas. $force = true untuk tombol "Kirim sekarang" (abaikan status on/off & sudah-terkirim).
     */
    public static function run(string $job, bool $force = false): array
    {
        if (! isset(self::JOBS[$job])) {
            return ['ok' => false, 'detail' => 'Tugas tidak dikenal.'];
        }
        if (! $force && ! self::enabled($job)) {
            return ['ok' => true, 'detail' => 'Dimatikan di admin, dilewati.'];
        }

        $key = match ($job) {
            'weekly'  => now()->format('o-\WW'),
            'monthly' => now()->format('Y-m'),
            default   => now()->toDateString(),
        };
        $last = self::lastRun($job);
        if (! $force && ($last['key'] ?? null) === $key) {
            return ['ok' => true, 'detail' => 'Sudah dijalankan untuk periode ini.'];
        }

        $result = match ($job) {
            'agenda'   => self::sendAdmin('agenda', 'Agenda hari ini', self::agendaText(Carbon::today())),
            'reminder' => self::sendReminders(Carbon::tomorrow()),
            'weekly'   => self::sendAdmin('report', 'Laporan mingguan', self::reportText(Carbon::today()->subDays(7), Carbon::yesterday(), '7 hari terakhir')),
            'monthly'  => self::sendAdmin('report', 'Laporan bulanan', self::reportText(Carbon::today()->subMonthNoOverflow()->startOfMonth(), Carbon::today()->subMonthNoOverflow()->endOfMonth(), Carbon::today()->subMonthNoOverflow()->locale('id')->translatedFormat('F Y'))),
        };

        Setting::put(['auto_last_' . $job => json_encode(['key' => $key, 'at' => now()->toDateTimeString(), 'detail' => $result['detail'], 'ok' => $result['ok']])]);

        return $result;
    }

    /* ------------------------------------------------------------------ */

    /** Booking aktif (pending/booked) pada satu tanggal, urut jam. */
    private static function bookingsOn(CarbonInterface $date): Collection
    {
        return Booking::with('user')
            ->whereDate('booking_date', $date->toDateString())
            ->whereIn('status', ['pending', 'booked'])
            ->orderBy('start_time')
            ->get();
    }

    private static function payments(Collection $bookings): Collection
    {
        return Payment::whereIn('booking_id', $bookings->pluck('id'))->latest('id')->get()->unique('booking_id')->keyBy('booking_id');
    }

    public static function name(Booking $b): string
    {
        return trim((string) ($b->user->name ?? $b->offline_customer_name ?? '')) ?: 'Murid';
    }

    private static function time(Booking $b): string
    {
        return str_replace(':', '.', substr((string) $b->start_time, 0, 5)) . '–' . str_replace(':', '.', substr((string) $b->end_time, 0, 5));
    }

    public static function agendaText(CarbonInterface $date): string
    {
        $list = self::bookingsOn($date);
        $pay = self::payments($list);
        $day = $date->locale('id')->translatedFormat('l, j F Y');

        if ($list->isEmpty()) {
            $text = "Tidak ada lesson hari ini ({$day}).";
        } else {
            $lines = $list->map(function (Booking $b) use ($pay) {
                $p = $pay[$b->id] ?? null;
                $status = $b->status === 'pending' ? 'menunggu' : 'booked';
                $bayar = $p ? match ($p->status) { 'paid' => 'lunas', 'cash' => 'cash', 'verifying' => 'cek bukti bayar', default => 'belum bayar' } : 'belum bayar';
                $where = $b->isCourse() ? 'Course' . ($b->place_label ? ' · ' . $b->place_label : '') : ($b->place_label ?: $b->lesson_label);

                return '• ' . self::time($b) . ' ' . self::name($b) . ' (' . $where . ') — ' . $status . ', ' . $bayar;
            })->implode("\n");

            $hours = $list->sum(fn ($b) => max(1, (strtotime($b->end_time) - strtotime($b->start_time)) / 3600));
            $text = "{$day}\n{$list->count()} lesson, total {$hours} jam:\n{$lines}";
        }

        $verifying = Payment::where('status', 'verifying')->count();
        $pending = Booking::where('status', 'pending')->whereDate('booking_date', '>=', Carbon::today())->count();
        $tomorrow = self::bookingsOn($date->copy()->addDay())->count();

        $extra = [];
        if ($verifying) {
            $extra[] = "• {$verifying} bukti bayar menunggu dicek";
        }
        if ($pending) {
            $extra[] = "• {$pending} booking masih pending";
        }
        $extra[] = "• Besok: {$tomorrow} lesson";

        return $text . "\n\nPerlu perhatian:\n" . implode("\n", $extra);
    }

    public static function reportText(CarbonInterface $from, CarbonInterface $to, string $label): string
    {
        $bookings = Booking::with('user')
            ->whereDate('booking_date', '>=', $from->toDateString())
            ->whereDate('booking_date', '<=', $to->toDateString())
            ->get();
        $active = $bookings->whereIn('status', ['booked', 'pending']);
        $cancelled = $bookings->whereIn('status', ['cancelled', 'rejected', 'failed', 'expired'])->count();
        $hours = $active->sum(fn ($b) => max(1, (strtotime($b->end_time) - strtotime($b->start_time)) / 3600));

        $paid = Payment::where('status', 'paid')
            ->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->sum('amount');

        $newMembers = User::where('role', 'customer')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->count();

        $students = $active->map(fn ($b) => $b->user_id ? 'u' . $b->user_id : 'o' . strtolower(self::name($b)))->unique()->count();

        $busyDay = $active->groupBy(fn ($b) => Carbon::parse($b->booking_date)->locale('id')->translatedFormat('l'))->map->count()->sortDesc();
        $busyHour = $active->groupBy(fn ($b) => substr((string) $b->start_time, 0, 2) . '.00')->map->count()->sortDesc();

        $top = $active->groupBy(fn ($b) => self::name($b))->map->count()->sortDesc()->take(3)
            ->map(fn ($n, $name) => "{$name} ({$n}x)")->implode(', ');

        $period = $from->locale('id')->translatedFormat('j M') . ' – ' . $to->locale('id')->translatedFormat('j M Y');

        $lines = [
            "Periode: {$label} ({$period})",
            '',
            "• Lesson: {$active->count()} booking, {$hours} jam",
            "• Murid aktif: {$students}",
            "• Murid baru daftar: {$newMembers}",
            '• Pemasukan (lunas): ' . Payment::formatRupiah((int) $paid),
            "• Batal/ditolak: {$cancelled}",
        ];
        if ($busyDay->isNotEmpty()) {
            $lines[] = '• Hari paling ramai: ' . $busyDay->keys()->first() . ' (' . $busyDay->first() . ' lesson)';
            $lines[] = '• Jam paling ramai: ' . $busyHour->keys()->first() . ' (' . $busyHour->first() . ' lesson)';
        }
        if ($top) {
            $lines[] = '• Murid paling rajin: ' . $top;
        }

        return implode("\n", $lines);
    }

    /** Kirim ke WhatsApp admin (jika aktif) + lonceng semua admin. */
    private static function sendAdmin(string $event, string $title, string $text): array
    {
        $admins = User::where('role', 'admin')->get();
        $url = route('admin.dashboard');

        Notification::send($admins, new \App\Notifications\AutoReport($event, $title, $text, $url));

        if (! WhatsApp::enabled()) {
            return ['ok' => true, 'detail' => 'Masuk ke lonceng admin. WhatsApp belum aktif (Settings → Notifikasi & WhatsApp).'];
        }

        $wa = WhatsApp::send(WhatsApp::adminNumbers(), WhatsApp::format($title, $text, $url));

        return ['ok' => $wa['ok'], 'detail' => 'Lonceng admin ✓ · WhatsApp: ' . $wa['detail']];
    }

    private static function sendReminders(CarbonInterface $date): array
    {
        $list = self::bookingsOn($date)->where('status', 'booked');
        $day = $date->locale('id')->translatedFormat('l, j F Y');
        $mail = ! in_array(config('mail.default'), ['log', 'array', null], true);
        $bell = 0;
        $emails = 0;

        foreach ($list as $b) {
            $place = $b->place_label ? ' di ' . $b->place_label : '';
            $title = 'Pengingat: lesson besok';
            $text = "Halo " . self::name($b) . "! Besok {$day} jam " . self::time($b) . " ada {$b->lesson_label}{$place}. Datang tepat waktu ya, dan konfirmasi ke coach jika berhalangan.";

            if ($b->user) {
                $b->user->notify(new \App\Notifications\AutoReport('reminder', $title, $text, route('jadwal')));
                $bell++;
            }

            $email = $b->user->email ?? $b->offline_customer_email;
            if ($mail && $email) {
                try {
                    Mail::raw($text . "\n\n— " . Brand::name(), fn ($m) => $m->to($email)->subject($title . ' (' . self::time($b) . ')'));
                    $emails++;
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return ['ok' => true, 'detail' => "Lesson besok: {$list->count()} · lonceng terkirim: {$bell}" . ($mail ? " · email: {$emails}" : ' · email belum diatur')];
    }
}
