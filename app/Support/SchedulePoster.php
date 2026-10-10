<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\ScheduleBlock;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Poster jadwal mingguan (Admin > Poster Jadwal).
 * Data booking diambil dari booking customer (online & offline) berstatus pending / booked.
 */
class SchedulePoster
{
    public const MAX_DAYS = 7;

    public const DEFAULTS = [
        'poster_title'     => 'Weekly [Schedule]',
        'poster_subtitle'  => 'Coach Ditya',
        'poster_notes'     => "Durasi lesson (60 menit)\nWajib datang sesuai jam yang sudah dibooking\nTelat dianggap tetap terhitung sesi lesson\nJadwal bisa berubah sewaktu-waktu\n(Untuk package) reschedule hanya bisa dilakukan 2x dan wajib selesai dalam kurun waktu 1 bulan\nHarap konfirmasi kehadiran minimal H-1\nApabila cancel mendadak dianggap terhitung",
        'poster_free'      => 'Available',
        'poster_booked'    => 'Booked',
        'poster_footer'    => '#ThankYouGais ‼️',
        'poster_ig'        => '@kochditya_',
        'poster_contact'   => '',
        'poster_website'   => '',
        'poster_mode'      => 'name',
        'poster_show_logo' => '1',
    ];

    public const MODES = [
        'name'   => 'Nama lengkap murid',
        'first'  => 'Nama depan saja',
        'booked' => 'Tulis "Booked" (sembunyikan nama)',
    ];

    public static function get(string $key): string
    {
        $value = (string) Setting::get($key, self::DEFAULTS[$key] ?? '');

        if ($value === '' && $key === 'poster_contact') {
            // Bawaan: nomor WhatsApp di menu Kontak & Lokasi
            $value = (string) (\App\Models\ContactSetting::query()->value('whatsapp') ?? '');
        }
        if ($value === '' && $key === 'poster_website') {
            $value = app()->runningInConsole()
                ? (string) preg_replace('#^https?://(www\.)?#', '', rtrim((string) config('app.url'), '/'))
                : request()->getHttpHost();
        }

        return $value;
    }

    /** Nilai untuk poster: tanda "-" berarti disembunyikan. */
    public static function show(string $key): string
    {
        $value = self::get($key);

        return trim($value) === '-' ? '' : $value;
    }

    public static function notes(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', self::get('poster_notes')))));
    }

    /** Judul dengan [kata] => <em>kata</em>, aman dari HTML. */
    public static function titleHtml(): string
    {
        return preg_replace('/\[(.+?)\]/u', '<em>$1</em>', e(self::get('poster_title')));
    }

    /**
     * Logo sebagai data URI (supaya ikut tersimpan saat poster diunduh jadi PNG).
     * Urutan: logo khusus poster > logo website > null.
     */
    public static function logoDataUri(): ?string
    {
        if (self::get('poster_show_logo') !== '1') {
            return null;
        }

        foreach ([Setting::get('poster_logo'), Setting::get('logo')] as $path) {
            if (! $path) {
                continue;
            }
            try {
                $url = Storage::disk('public')->url($path);
                // Supabase / cloud: ambil lewat URL publik; lokal: baca langsung dari disk
                $bytes = str_starts_with($url, 'http')
                    ? \Illuminate\Support\Facades\Http::timeout(10)->get($url)->body()
                    : Storage::disk('public')->get($path);
                if ($bytes) {
                    $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes) ?: 'image/png';

                    return 'data:' . $mime . ';base64,' . base64_encode($bytes);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return null;
    }

    /** Tanggal bawaan: Sabtu–Minggu terdekat. */
    public static function defaultRange(): array
    {
        $today = Carbon::today();
        $sat = $today->isSaturday() ? $today->copy() : ($today->isSunday() ? $today->copy()->subDay() : $today->copy()->next(Carbon::SATURDAY));

        return [$sat->toDateString(), $sat->copy()->addDay()->toDateString()];
    }

    /**
     * Susun data poster.
     * Hasil: ['days' => [[date, label, short, rows => [[start, end, label, booked(bool)|null(tidak tersedia)]]]], 'hours' => [...], 'range' => '...']
     */
    public static function build(string $from, string $to, string $mode): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $end   = Carbon::parse($to)->startOfDay();
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }
        if ($start->diffInDays($end) >= self::MAX_DAYS) {
            $end = $start->copy()->addDays(self::MAX_DAYS - 1);
        }

        $open  = (int) substr(BookingRules::openTime(), 0, 2);
        $close = (int) substr(BookingRules::closeTime(), 0, 2);

        $bookings = Booking::with('user')
            ->whereDate('booking_date', '>=', $start->toDateString())
            ->whereDate('booking_date', '<=', $end->toDateString())
            ->whereIn('status', ['pending', 'booked'])
            ->get();
        $blocks = ScheduleBlock::whereDate('date', '>=', $start->toDateString())->whereDate('date', '<=', $end->toDateString())->get();

        $days = [];
        $hours = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $date = $d->toDateString();
            $dayBookings = $bookings->filter(fn ($b) => Carbon::parse($b->booking_date)->toDateString() === $date);
            $dayBlocks = $blocks->filter(fn ($b) => Carbon::parse($b->date)->toDateString() === $date);

            $rows = [];
            for ($h = $open; $h < $close; $h++) {
                $s = sprintf('%02d:00', $h);
                $e = sprintf('%02d:00', $h + 1);
                $hit = fn ($a, $b) => substr((string) $a, 0, 5) < $e && substr((string) $b, 0, 5) > $s;

                $booking = $dayBookings->first(fn ($b) => $hit($b->start_time, $b->end_time));
                $blocked = $dayBlocks->first(fn ($b) => ! $b->start_time || $hit($b->start_time, $b->end_time));

                if ($blocked && ! $booking) {
                    continue; // jam ditutup coach: tidak ditampilkan
                }

                $rows[$s] = [
                    'time'   => str_replace(':', '.', $s) . ' – ' . str_replace(':', '.', $e),
                    'booked' => (bool) $booking,
                    'label'  => $booking ? self::name($booking, $mode) : self::get('poster_free'),
                ];
                $hours[$s] = str_replace(':', '.', $s) . ' – ' . str_replace(':', '.', $e);
            }

            $days[] = [
                'date'  => $date,
                'day'   => $d->locale('id')->translatedFormat('l'),
                'short' => $d->locale('id')->translatedFormat('D'),
                'dm'    => $d->locale('id')->translatedFormat('j M'),
                'rows'  => $rows,
            ];
        }
        ksort($hours);

        return [
            'from'  => $start->toDateString(),
            'to'    => $end->toDateString(),
            'days'  => $days,
            'hours' => $hours,
            'range' => self::rangeLabel($start, $end),
            'count' => $bookings->count(),
        ];
    }

    private static function name(Booking $booking, string $mode): string
    {
        if ($mode === 'booked') {
            return self::get('poster_booked');
        }

        $name = trim((string) ($booking->user->name ?? $booking->offline_customer_name ?? '')) ?: self::get('poster_booked');

        if ($mode === 'first') {
            // Nama depan + keterangan dalam kurung tetap ikut, misal "Kanya (Junior)"
            preg_match('/\(.*\)$/u', $name, $m);
            $name = strtok($name, ' ') . (isset($m[0]) && ! str_starts_with($name, $m[0]) ? ' ' . $m[0] : '');
        }

        return $name;
    }

    private static function rangeLabel(Carbon $a, Carbon $b): string
    {
        $a = $a->copy()->locale('id');
        $b = $b->copy()->locale('id');

        if ($a->isSameDay($b)) {
            return $a->translatedFormat('l, j F Y');
        }
        if ($a->isSameMonth($b)) {
            return $a->translatedFormat('j') . ' – ' . $b->translatedFormat('j F Y');
        }
        if ($a->isSameYear($b)) {
            return $a->translatedFormat('j F') . ' – ' . $b->translatedFormat('j F Y');
        }

        return $a->translatedFormat('j F Y') . ' – ' . $b->translatedFormat('j F Y');
    }
}
