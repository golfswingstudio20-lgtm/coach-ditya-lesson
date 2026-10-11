<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminCustomerController extends Controller
{
    public const SORTS = [
        'newest' => 'Terbaru',
        'name'   => 'Nama A–Z',
        'most'   => 'Booking terbanyak',
        'recent' => 'Booking terakhir',
    ];

    /** Filter member tidak aktif (bulan) */
    public const INACTIVE = [
        '3'  => 'Tidak aktif > 3 bulan',
        '6'  => 'Tidak aktif > 6 bulan',
        '12' => 'Tidak aktif > 12 bulan',
    ];

    public const STATUSES = [
        'pending'   => 'Pending',
        'booked'    => 'Booked',
        'cancelled' => 'Dibatalkan',
        'rejected'  => 'Ditolak',
    ];

    /**
     * Daftar customer: tab Member (punya akun) & Offline (dari booking admin).
     */
    public function index(Request $request): View
    {
        $tab    = $request->query('tab') === 'offline' ? 'offline' : 'member';
        $search = trim((string) $request->query('q', ''));
        $sort   = array_key_exists((string) $request->query('sort'), self::SORTS)
            ? $request->query('sort')
            : 'newest';
        $inactive = array_key_exists((string) $request->query('inactive'), self::INACTIVE)
            ? (string) $request->query('inactive')
            : '';

        $stats = [
            'members'   => User::where('role', 'customer')->count(),
            'new_month' => User::where('role', 'customer')
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'active_30' => Booking::whereNotNull('user_id')
                ->where('created_at', '>=', now()->subDays(30))
                ->distinct()
                ->count('user_id'),
            'inactive_6' => $this->inactiveFilter(User::where('role', 'customer'), 6)->count(),
            'offline'   => Booking::whereNull('user_id')
                ->whereNotNull('offline_customer_phone')
                ->distinct()
                ->count('offline_customer_phone'),
        ];

        if ($tab === 'member') {
            $query = $this->memberQuery($search, $sort);

            if ($inactive !== '') {
                $this->inactiveFilter($query, (int) $inactive);
            }

            $customers = $query->paginate(15)->withQueryString();
            $customers->getCollection()->transform(fn (User $user) => $this->withActivity($user));
        } else {
            $customers = $this->offlineQuery($search, $sort)->paginate(15)->withQueryString();
        }

        return view('admin.customers.index', [
            'customers' => $customers,
            'tab'       => $tab,
            'search'    => $search,
            'sort'      => $sort,
            'sorts'     => self::SORTS,
            'stats'     => $stats,
            'inactive'  => $inactive,
            'inactives' => self::INACTIVE,
        ]);
    }

    /* ---------------------------------------------------------------
     | TAMBAH CUSTOMER ONLINE
     * --------------------------------------------------------------- */
    public function create(): View
    {
        return view('admin.customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'verified' => ['nullable', 'boolean'],
        ], [
            'name.required'      => 'Nama wajib diisi.',
            'email.required'     => 'Email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'email.unique'       => 'Email ini sudah terdaftar.',
            'password.required'  => 'Password wajib diisi.',
            'password.confirmed' => 'Ulangi password tidak sama.',
            'password.min'       => 'Password minimal 8 karakter.',
        ]);

        $user = new User();
        $user->forceFill([
            'name'              => trim($validated['name']),
            'email'             => Str::lower(trim($validated['email'])),
            'password'          => $validated['password'],
            'role'              => 'customer',
            'email_verified_at' => $request->boolean('verified') ? now() : null,
        ])->save();

        // Belum ditandai terverifikasi: kirim email verifikasi seperti pendaftaran biasa
        if (! $request->boolean('verified') && method_exists($user, 'sendEmailVerificationNotification')) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()
            ->route('admin.customers.show', $user)
            ->with('success', 'Akun customer berhasil dibuat.')
            ->with('new_account', [
                'email'    => $user->email,
                'password' => $validated['password'],
            ]);
    }

    /* ---------------------------------------------------------------
     | HAPUS CUSTOMER
     * --------------------------------------------------------------- */
    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->role === 'customer', 404);

        if ($reason = self::deleteBlockedReason($user)) {
            return redirect()->route('admin.customers.show', $user)->with('error', $reason);
        }

        $name = $user->name;

        try {
            DB::transaction(function () use ($user) {
                // Riwayat booking tetap tersimpan atas nama customer, tanpa akun
                Booking::where('user_id', $user->id)->get()->each(function (Booking $booking) use ($user) {
                    $booking->forceFill([
                        'offline_customer_name'  => $booking->offline_customer_name ?: $user->name,
                        'offline_customer_email' => $booking->offline_customer_email ?: $user->email,
                        'user_id'                => null,
                    ])->saveQuietly();
                });

                if (Schema::hasTable('payments')) {
                    DB::table('payments')->where('user_id', $user->id)->update(['user_id' => null]);
                }

                if (Schema::hasTable('notifications')) {
                    DB::table('notifications')
                        ->where('notifiable_type', $user->getMorphClass())
                        ->where('notifiable_id', $user->id)
                        ->delete();
                }

                if (Schema::hasTable('sessions')) {
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                }

                if (Schema::hasTable('password_reset_tokens')) {
                    DB::table('password_reset_tokens')->where('email', $user->email)->delete();
                }

                $user->delete();
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.customers.show', $user)
                ->with('error', 'Customer gagal dihapus: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.customers.index', ['tab' => 'member'])
            ->with('success', "Akun {$name} dihapus. Riwayat booking & pembayarannya tetap tersimpan.");
    }

    /**
     * Alasan customer tidak boleh dihapus (null = boleh).
     */
    public static function deleteBlockedReason(User $user): ?string
    {
        $active = Booking::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'booked'])
            ->whereDate('booking_date', '>=', today())
            ->count();

        if ($active > 0) {
            return "Masih ada {$active} booking aktif (pending / booked). Batalkan atau selesaikan dulu sebelum menghapus akun.";
        }

        if (Schema::hasTable('payments')
            && DB::table('payments')->where('user_id', $user->id)->where('status', 'verifying')->exists()) {
            return 'Masih ada pembayaran yang menunggu verifikasi. Konfirmasi atau tolak dulu di Dashboard.';
        }

        return null;
    }

    /**
     * Detail member (customer yang punya akun).
     */
    public function show(User $user): View
    {
        abort_unless($user->role === 'customer', 404);

        $bookings = Booking::where('user_id', $user->id)
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->get();

        return view('admin.customers.show', [
            'user'     => $this->withActivity($user),
            'deleteBlocked' => self::deleteBlockedReason($user),
            'phone'    => $user->getAttributes()['phone'] ?? null,
            'waUrl'    => self::whatsappUrl($user->getAttributes()['phone'] ?? null),
            'bookings' => $bookings,
            'summary'  => $this->summary($bookings),
            'upcoming' => $this->upcoming($bookings),
            'statuses' => self::STATUSES,
        ]);
    }

    /**
     * Buat password baru untuk member yang lupa password (ditampilkan sekali ke admin).
     */
    public function resetPassword(User $user): RedirectResponse
    {
        abort_unless($user->role === 'customer', 404);

        // Mudah dibaca & diketik: tanpa huruf/angka yang mirip (0/O, 1/l/I)
        $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $password = '';
        for ($i = 0; $i < 10; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

        // Keluarkan dari semua perangkat yang masih login dengan password lama
        if (Schema::hasTable(config('session.table', 'sessions'))) {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        return redirect()
            ->route('admin.customers.show', $user)
            ->with('success', 'Password baru dibuat untuk ' . $user->name . '.')
            ->with('new_account', ['email' => $user->email, 'password' => $password, 'reset' => true]);
    }

    /**
     * Tandai email member sudah terverifikasi (kalau email verifikasi tidak sampai).
     */
    public function verifyEmail(User $user): RedirectResponse
    {
        abort_unless($user->role === 'customer', 404);

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return back()->with('success', 'Email ' . $user->email . ' ditandai terverifikasi. Member sudah bisa booking.');
    }

    /**
     * Detail customer offline (dikelompokkan berdasarkan nomor HP).
     */
    public function offline(Request $request): View|RedirectResponse
    {
        $phone = trim((string) $request->query('phone', ''));

        if ($phone === '') {
            return redirect()->route('admin.customers.index', ['tab' => 'offline']);
        }

        $bookings = Booking::whereNull('user_id')
            ->where('offline_customer_phone', $phone)
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->get();

        abort_if($bookings->isEmpty(), 404);

        $latest = $bookings->first();

        return view('admin.customers.offline', [
            'name'     => $latest->offline_customer_name ?: 'Customer offline',
            'phone'    => $phone,
            'email'    => $bookings->pluck('offline_customer_email')->filter()->first(),
            'waUrl'    => self::whatsappUrl($phone),
            'bookings' => $bookings,
            'summary'  => $this->summary($bookings),
            'upcoming' => $this->upcoming($bookings),
            'statuses' => self::STATUSES,
        ]);
    }

    /* ---------------------------------------------------------------
     | QUERY
     * --------------------------------------------------------------- */
    private function memberQuery(string $search, string $sort)
    {
        $hasPhone = Schema::hasColumn('users', 'phone');

        $query = User::query()
            ->where('role', 'customer')
            ->select('users.*')
            ->addSelect([
                'bookings_count' => Booking::selectRaw('COUNT(*)')
                    ->whereColumn('bookings.user_id', 'users.id'),
                'last_booking_date' => Booking::select('booking_date')
                    ->whereColumn('bookings.user_id', 'users.id')
                    ->orderByDesc('booking_date')
                    ->limit(1),
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search, $hasPhone) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");

                if ($hasPhone) {
                    $q->orWhere('phone', 'like', "%{$search}%");
                }
            });
        }

        return match ($sort) {
            'name'   => $query->orderBy('name'),
            'most'   => $query->orderByDesc('bookings_count')->orderBy('name'),
            'recent' => $query->orderByDesc('last_booking_date')->orderBy('name'),
            default  => $query->orderByDesc('users.created_at'),
        };
    }

    private function offlineQuery(string $search, string $sort)
    {
        $query = DB::table('bookings')
            ->whereNull('user_id')
            ->whereNotNull('offline_customer_phone')
            ->selectRaw('
                offline_customer_phone AS phone,
                MAX(offline_customer_name) AS name,
                MAX(offline_customer_email) AS email,
                COUNT(*) AS bookings_count,
                MAX(booking_date) AS last_booking_date,
                MIN(created_at) AS first_seen
            ')
            ->groupBy('offline_customer_phone');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('offline_customer_name', 'like', "%{$search}%")
                    ->orWhere('offline_customer_phone', 'like', "%{$search}%")
                    ->orWhere('offline_customer_email', 'like', "%{$search}%");
            });
        }

        return match ($sort) {
            'name'   => $query->orderBy('name'),
            'most'   => $query->orderByDesc('bookings_count')->orderBy('name'),
            'recent' => $query->orderByDesc('last_booking_date')->orderBy('name'),
            default  => $query->orderByDesc('first_seen'),
        };
    }

    /* ---------------------------------------------------------------
     | AKTIVITAS
     * --------------------------------------------------------------- */

    /**
     * Member tidak aktif: akun lebih lama dari N bulan, tanpa booking
     * dan tanpa login dalam N bulan terakhir.
     */
    private function inactiveFilter($query, int $months)
    {
        $cutoff = now()->subMonths($months);

        $query->where('users.created_at', '<', $cutoff)
            ->whereNotExists(function ($q) use ($cutoff) {
                $q->selectRaw('1')
                    ->from('bookings')
                    ->whereColumn('bookings.user_id', 'users.id')
                    ->where(function ($inner) use ($cutoff) {
                        $inner->where('bookings.created_at', '>=', $cutoff)
                            ->orWhere('bookings.updated_at', '>=', $cutoff)
                            ->orWhere('bookings.booking_date', '>=', $cutoff->toDateString());
                    });
            });

        if (Schema::hasTable('sessions')) {
            $query->whereNotExists(function ($q) use ($cutoff) {
                $q->selectRaw('1')
                    ->from('sessions')
                    ->whereColumn('sessions.user_id', 'users.id')
                    ->where('sessions.last_activity', '>=', $cutoff->timestamp);
            });
        }

        return $query;
    }

    /**
     * Tambahkan "last_active_at": aktivitas terakhir dari booking, login, atau tanggal daftar.
     */
    private function withActivity(User $user): User
    {
        $times = [$user->created_at];

        $lastBooking = Booking::where('user_id', $user->id)->max('updated_at');

        if ($lastBooking) {
            $times[] = Carbon::parse($lastBooking);
        }

        if (Schema::hasTable('sessions')) {
            $lastSession = DB::table('sessions')->where('user_id', $user->id)->max('last_activity');

            if ($lastSession) {
                $times[] = Carbon::createFromTimestamp($lastSession);
            }
        }

        $user->setAttribute('last_active_at', collect($times)->filter()->max());

        return $user;
    }

    /* ---------------------------------------------------------------
     | HELPER
     * --------------------------------------------------------------- */
    private function summary(Collection $bookings): array
    {
        return [
            'total'     => $bookings->count(),
            'booked'    => $bookings->where('status', 'booked')->count(),
            'pending'   => $bookings->where('status', 'pending')->count(),
            'cancelled' => $bookings->whereIn('status', ['cancelled', 'rejected'])->count(),
        ];
    }

    private function upcoming(Collection $bookings): ?Booking
    {
        return $bookings
            ->filter(fn ($booking) => in_array($booking->status, ['pending', 'booked'], true)
                && $booking->booking_date
                && $booking->booking_date->gte(today()))
            ->sortBy(fn ($booking) => $booking->booking_date->format('Y-m-d') . ' ' . $booking->start_time)
            ->first();
    }

    /**
     * "0858 8680 3126" -> "https://wa.me/6285886803126"
     */
    public static function whatsappUrl(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }

        return 'https://wa.me/' . $digits;
    }
}
