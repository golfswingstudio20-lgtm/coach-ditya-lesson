<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Brand;
use App\Support\BookingRules;
use App\Support\Security;
use App\Support\WhatsApp;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminSettingController extends Controller
{
    /** Tab yang sudah aktif (tahap 1). Tab lain tampil sebagai "segera hadir". */
    public const TABS = [
        'general'    => ['label' => 'Umum & Branding',       'ready' => true],
        'appearance' => ['label' => 'Tampilan',              'ready' => true],
        'booking'    => ['label' => 'Booking & Jadwal',      'ready' => true],
        'payment'    => ['label' => 'Pembayaran',            'ready' => true],
        'whatsapp'   => ['label' => 'Notifikasi & WhatsApp', 'ready' => true],
        'security'   => ['label' => 'Keamanan',              'ready' => true],
        'account'    => ['label' => 'Akun Admin',            'ready' => true],
    ];

    /** Tab lanjutan (Tahap 3) yang bisa disembunyikan dari menu Settings */
    public const ADVANCED_TABS = ['whatsapp', 'security'];

    public static function showAdvanced(): bool
    {
        return Setting::get('show_advanced_settings', '1') === '1';
    }

    public function index(Request $request): View
    {
        $tabs = self::TABS;

        if (! self::showAdvanced()) {
            $tabs = array_diff_key($tabs, array_flip(self::ADVANCED_TABS));
        }

        $tab = array_key_exists((string) $request->query('tab'), $tabs) ? $request->query('tab') : 'general';

        return view('admin.settings.index', [
            'tab'  => $tab,
            'tabs' => $tabs,
            'user' => $request->user(),
        ]);
    }

    /* ---------------------------------------------------------------
     | UMUM & BRANDING
     * --------------------------------------------------------------- */
    public function updateGeneral(Request $request): RedirectResponse
    {
        $request->validate([
            'site_name'           => ['required', 'string', 'max:60'],
            'tagline'             => ['nullable', 'string', 'max:120'],
            'logo'                => ['nullable', 'file', 'mimes:png,webp', 'max:1024'],
            'favicon'             => ['nullable', 'file', 'mimes:png', 'max:512'],
            'maintenance'         => ['nullable', 'boolean'],
            'show_advanced_settings' => ['nullable', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:300'],
        ], [
            'site_name.required' => 'Nama usaha wajib diisi.',
            'logo.mimes'         => 'Logo harus berupa PNG atau WEBP.',
            'logo.max'           => 'Ukuran logo maksimal 1 MB.',
            'favicon.mimes'      => 'Favicon harus berupa PNG.',
            'favicon.max'        => 'Ukuran favicon maksimal 512 KB.',
        ]);

        Setting::put([
            'site_name'           => trim($request->input('site_name')),
            'tagline'             => trim((string) $request->input('tagline')),
            'maintenance'         => $request->boolean('maintenance') ? '1' : '0',
            'maintenance_message' => trim((string) $request->input('maintenance_message')),
            'show_advanced_settings' => $request->boolean('show_advanced_settings') ? '1' : '0',
        ]);

        $this->handleImage($request, 'logo', 'logo', 'settings/brand');
        $this->handleImage($request, 'favicon', 'favicon', 'settings/brand');

        $message = 'Pengaturan umum disimpan.';

        if ($request->boolean('maintenance')) {
            $message .= ' Mode maintenance AKTIF: customer & visitor tidak bisa membuka website.';
        }

        return redirect()
            ->route('admin.settings.index', ['tab' => 'general'])
            ->with($request->boolean('maintenance') ? 'error' : 'success', $message);
    }

    /* ---------------------------------------------------------------
     | TAMPILAN
     * --------------------------------------------------------------- */
    public function updateAppearance(Request $request): RedirectResponse
    {
        $image = ['nullable', 'file', 'mimes:jpg,jpeg,webp', 'max:3072'];

        $request->validate([
            'bg_public'      => $image,
            'bg_auth'        => $image,
            'bg_admin'       => $image,
            'hero_label'     => ['nullable', 'string', 'max:60'],
            'hero_title_1'   => ['nullable', 'string', 'max:60'],
            'hero_title_2'   => ['nullable', 'string', 'max:60'],
            'hero_highlight' => ['nullable', 'string', 'max:40'],
            'hero_text'      => ['nullable', 'string', 'max:300'],
        ], [
            '*.mimes' => 'Background harus berupa JPG atau WEBP.',
            '*.max'   => 'Ukuran background maksimal 3 MB.',
        ]);

        Setting::put($request->only(array_keys(Brand::HERO_DEFAULTS)));

        foreach (['bg_public', 'bg_auth', 'bg_admin'] as $key) {
            $this->handleImage($request, $key, $key, 'settings/backgrounds');
        }

        return redirect()
            ->route('admin.settings.index', ['tab' => 'appearance'])
            ->with('success', 'Pengaturan tampilan disimpan.');
    }

    /* ---------------------------------------------------------------
     | BOOKING & JADWAL
     * --------------------------------------------------------------- */
    public function updateBooking(Request $request): RedirectResponse
    {
        $request->validate([
            'open_time'         => ['required', 'date_format:H:i'],
            'close_time'        => ['required', 'date_format:H:i', 'after:open_time'],
            'min_minutes'       => ['required', Rule::in(['60', '120', '180'])],
            'cancel_days'       => ['required', Rule::in(['0', '1', '2', '3'])],
            'max_active'        => ['required', Rule::in(['0', '1', '2', '3', '4', '5'])],
            'auto_approve_paid' => ['nullable', 'boolean'],
            'course_enabled'    => ['nullable', 'boolean'],
            'course_start'      => ['required', 'date_format:H:i'],
            'course_end'        => ['required', 'date_format:H:i', 'after:course_start'],
            'course_note'       => ['nullable', 'string', 'max:200'],
        ], [
            'course_end.after'         => 'Jam selesai Course Lesson harus setelah jam mulai.',

            'open_time.required'  => 'Jam buka wajib diisi.',
            'close_time.required' => 'Jam tutup wajib diisi.',
            'close_time.after'    => 'Jam tutup harus setelah jam buka.',
        ]);

        $open    = $request->input('open_time');
        $close   = $request->input('close_time');
        $minutes = (strtotime($close) - strtotime($open)) / 60;

        if ($minutes < (int) $request->input('min_minutes')) {
            return back()->withInput()->withErrors(['close_time' => 'Rentang jam buka terlalu pendek untuk durasi minimal yang dipilih.']);
        }

        Setting::put([
            'open_time'         => $open,
            'close_time'        => $close,
            'slot_minutes'      => '60',
            'min_minutes'       => $request->input('min_minutes'),
            'cancel_days'       => $request->input('cancel_days'),
            'max_active'        => $request->input('max_active'),
            'auto_approve_paid' => $request->boolean('auto_approve_paid') ? '1' : '0',
            'course_enabled'    => $request->boolean('course_enabled') ? '1' : '0',
            'course_start'      => $request->input('course_start'),
            'course_end'        => $request->input('course_end'),
            'course_note'       => trim((string) $request->input('course_note')),
        ]);

        return redirect()
            ->route('admin.settings.index', ['tab' => 'booking'])
            ->with('success', 'Aturan booking & jadwal disimpan. Berlaku untuk booking berikutnya.');
    }

    /* ---------------------------------------------------------------
     | PEMBAYARAN
     * --------------------------------------------------------------- */
    public function updatePayment(Request $request): RedirectResponse
    {
        $account = ['nullable', 'string', 'max:30', 'regex:/^[0-9\s\-]+$/'];

        $request->validate([
            'price_per_hour'       => ['required', 'integer', 'min:0', 'max:100000000'],
            'course_price'         => ['required', 'integer', 'min:0', 'max:100000000'],
            'payment_deadline_minutes' => ['required', Rule::in(['15', '30', '45', '60', '120'])],
            'require_paid'         => ['nullable', 'boolean'],
            'payment_mode'         => ['required', Rule::in(['demo', 'live'])],

            'pay_qris_enabled'     => ['nullable', 'boolean'],
            'qris_image'           => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:2048'],
            'qris_merchant'        => ['nullable', 'string', 'max:60'],
            'qris_nmid'            => ['nullable', 'string', 'max:30'],

            'pay_mandiri_enabled'  => ['nullable', 'boolean'],
            'mandiri_account'      => $account,
            'mandiri_holder'       => ['nullable', 'string', 'max:60'],

            'pay_bca_enabled'      => ['nullable', 'boolean'],
            'pay_cash_enabled'     => ['nullable', 'boolean'],
            'cash_allow_course'    => ['nullable', 'boolean'],
            'bca_account'          => $account,
            'bca_holder'           => ['nullable', 'string', 'max:60'],
        ], [
            'price_per_hour.required' => 'Harga per jam wajib diisi.',
            'price_per_hour.integer'  => 'Harga harus berupa angka tanpa titik, misalnya 900000.',
            'course_price.required'   => 'Harga Course Lesson wajib diisi.',
            'course_price.integer'    => 'Harga Course Lesson harus berupa angka tanpa titik, misalnya 3300000.',
            'qris_image.mimes'        => 'Gambar QRIS harus PNG atau JPG.',
            'qris_image.max'          => 'Ukuran gambar QRIS maksimal 2 MB.',
            '*_account.regex'         => 'Nomor rekening hanya boleh berisi angka.',
        ]);

        $enabled = array_filter(['qris', 'mandiri', 'bca', 'cash'], fn ($key) => $request->boolean("pay_{$key}_enabled"));

        if (empty($enabled)) {
            return back()->withInput()->withErrors(['pay_qris_enabled' => 'Minimal satu metode pembayaran harus aktif.']);
        }

        // Mode live: bank yang diaktifkan wajib punya nomor rekening & atas nama asli
        if ($request->input('payment_mode') === 'live') {
            foreach (['mandiri' => 'Mandiri', 'bca' => 'BCA'] as $key => $label) {
                if ($request->boolean("pay_{$key}_enabled")
                    && (! $request->filled("{$key}_account") || ! $request->filled("{$key}_holder"))) {
                    return back()->withInput()->withErrors([
                        "{$key}_account" => "Mode Live: nomor rekening dan atas nama {$label} wajib diisi jika {$label} diaktifkan.",
                    ]);
                }
            }

            if ($request->boolean('pay_qris_enabled') && ! Setting::get('qris_image') && ! $request->hasFile('qris_image')) {
                return back()->withInput()->withErrors([
                    'qris_image' => 'Mode Live: upload gambar QRIS asli, atau nonaktifkan QRIS.',
                ]);
            }
        }

        $values = [
            'price_per_hour'       => (string) (int) $request->input('price_per_hour'),
            'course_price'         => (string) (int) $request->input('course_price'),
            'payment_deadline_minutes' => $request->input('payment_deadline_minutes'),
            'require_paid'         => $request->boolean('require_paid') ? '1' : '0',
            'payment_mode'         => $request->input('payment_mode'),
            'qris_merchant'        => trim((string) $request->input('qris_merchant')),
            'qris_nmid'            => trim((string) $request->input('qris_nmid')),
        ];

        $values['cash_allow_course'] = $request->boolean('cash_allow_course') ? '1' : '0';

        foreach (['qris', 'mandiri', 'bca', 'cash'] as $key) {
            $values["pay_{$key}_enabled"] = $request->boolean("pay_{$key}_enabled") ? '1' : '0';
        }

        foreach (['mandiri', 'bca'] as $key) {
            $values["{$key}_account"] = preg_replace('/\D+/', '', (string) $request->input("{$key}_account"));
            $values["{$key}_holder"]  = trim((string) $request->input("{$key}_holder"));
        }

        Setting::put($values);

        $this->handleImage($request, 'qris_image', 'qris_image', 'settings/payment');

        return redirect()
            ->route('admin.settings.index', ['tab' => 'payment'])
            ->with('success', 'Pengaturan pembayaran disimpan. Harga baru berlaku untuk tagihan berikutnya.');
    }

    /* ---------------------------------------------------------------
     | NOTIFIKASI & WHATSAPP
     * --------------------------------------------------------------- */
    public function updateWhatsapp(Request $request): RedirectResponse
    {
        $rules = [
            'wa_provider'      => ['required', Rule::in(array_keys(WhatsApp::PROVIDERS))],
            'wa_wablas_url'    => ['nullable', 'url', 'max:200', 'required_if:wa_provider,wablas'],
            'wa_token'         => ['nullable', 'string', 'max:500'],
            'wa_secret'        => ['nullable', 'string', 'max:200'],
            'wa_admin_numbers' => ['nullable', 'string', 'max:300', 'regex:/^[0-9+,;\s\-]*$/'],
            'wa_template'      => ['nullable', 'string', 'max:1000'],
        ];

        foreach (array_keys(WhatsApp::ADMIN_EVENTS) as $event) {
            $rules["wa_admin_{$event}"] = ['nullable', 'boolean'];
        }

        $request->validate($rules, [
            'wa_wablas_url.required_if' => 'URL server Wablas wajib diisi, misalnya https://solo.wablas.com.',
            'wa_wablas_url.url'         => 'URL server Wablas tidak valid.',
            'wa_admin_numbers.regex'    => 'Nomor WhatsApp admin hanya boleh berisi angka, dipisahkan koma.',
        ]);

        $provider = $request->input('wa_provider');

        if ($provider !== 'off' && ! $request->filled('wa_token') && ! WhatsApp::hasToken()) {
            return back()->withInput()->withErrors(['wa_token' => 'Token API wajib diisi untuk mengaktifkan WhatsApp.']);
        }

        if ($provider !== 'off' && trim((string) $request->input('wa_admin_numbers')) === '') {
            return back()->withInput()->withErrors(['wa_admin_numbers' => 'Isi minimal satu nomor WhatsApp admin.']);
        }

        $values = [
            'wa_provider'      => $provider,
            'wa_wablas_url'    => trim((string) $request->input('wa_wablas_url')),
            'wa_admin_numbers' => trim((string) $request->input('wa_admin_numbers')),
            'wa_template'      => trim((string) $request->input('wa_template')) ?: WhatsApp::DEFAULT_TEMPLATE,
        ];

        foreach (array_keys(WhatsApp::ADMIN_EVENTS) as $event) {
            $values["wa_admin_{$event}"] = $request->boolean("wa_admin_{$event}") ? '1' : '0';
        }

        // Token & secret disimpan terenkripsi; kosong = tidak diubah
        if ($request->filled('wa_token')) {
            $values['wa_token'] = Crypt::encryptString(trim($request->input('wa_token')));
        }

        if ($request->filled('wa_secret')) {
            $values['wa_secret'] = Crypt::encryptString(trim($request->input('wa_secret')));
        }

        if ($request->boolean('remove_wa_token')) {
            $values['wa_token']    = null;
            $values['wa_secret']   = null;
            $values['wa_provider'] = 'off';
        }

        Setting::put($values);

        return redirect()
            ->route('admin.settings.index', ['tab' => 'whatsapp'])
            ->with('success', 'Pengaturan WhatsApp disimpan. Gunakan tombol "Kirim pesan uji" untuk mengecek koneksi.');
    }

    public function testWhatsapp(): RedirectResponse
    {
        $result = WhatsApp::send(
            WhatsApp::adminNumbers(),
            WhatsApp::format('Pesan uji', 'Koneksi WhatsApp dari ' . Brand::name() . ' berhasil. Notifikasi booking akan dikirim ke nomor ini.', null)
        );

        Setting::put([
            'wa_last_test' => json_encode([
                'ok'     => $result['ok'],
                'detail' => $result['detail'],
                'at'     => now()->toDateTimeString(),
            ]),
        ]);

        return redirect()
            ->route('admin.settings.index', ['tab' => 'whatsapp'])
            ->with($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Pesan uji terkirim. Cek WhatsApp admin.' : $result['detail']);
    }

    /* ---------------------------------------------------------------
     | KEAMANAN
     * --------------------------------------------------------------- */
    public function updateSecurity(Request $request): RedirectResponse
    {
        $request->validate([
            'login_max_attempts' => ['required', Rule::in(['3', '5', '10'])],
            'login_lock_minutes' => ['required', Rule::in(['1', '5', '15', '60'])],
            'admin_idle_minutes' => ['required', Rule::in(['0', '15', '30', '120', '480'])],
            'require_admin_2fa'  => ['nullable', 'boolean'],
        ]);

        // Cegah admin terkunci: 2FA wajib hanya boleh diaktifkan jika admin ini sudah memakai 2FA
        if ($request->boolean('require_admin_2fa')) {
            if (! Security::twoFactorRoute()) {
                return back()->withInput()->withErrors(['require_admin_2fa' => 'Halaman pengaturan 2FA tidak ditemukan di aplikasi ini.']);
            }

            if (empty($request->user()->two_factor_confirmed_at)) {
                return back()->withInput()->withErrors([
                    'require_admin_2fa' => 'Aktifkan 2FA di akun Anda sendiri terlebih dahulu (tombol "Atur 2FA"), baru wajibkan untuk semua admin.',
                ]);
            }
        }

        Setting::put([
            'login_max_attempts' => $request->input('login_max_attempts'),
            'login_lock_minutes' => $request->input('login_lock_minutes'),
            'admin_idle_minutes' => $request->input('admin_idle_minutes'),
            'require_admin_2fa'  => $request->boolean('require_admin_2fa') ? '1' : '0',
        ]);

        // Mulai hitung waktu aktif dari sekarang
        $request->session()->put('admin_last_activity', time());

        return redirect()
            ->route('admin.settings.index', ['tab' => 'security'])
            ->with('success', 'Pengaturan keamanan disimpan.');
    }

    /* ---------------------------------------------------------------
     | AKUN ADMIN
     * --------------------------------------------------------------- */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'current_password'],
        ], [
            'name.required'                     => 'Nama wajib diisi.',
            'email.unique'                      => 'Email sudah dipakai akun lain.',
            'current_password.required'         => 'Masukkan password saat ini untuk menyimpan perubahan.',
            'current_password.current_password' => 'Password saat ini salah.',
        ]);

        $user->forceFill([
            'name'  => $request->input('name'),
            'email' => $request->input('email'),
        ])->save();

        return redirect()
            ->route('admin.settings.index', ['tab' => 'account'])
            ->with('success', 'Profil admin diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.required'         => 'Masukkan password saat ini.',
            'current_password.current_password' => 'Password saat ini salah.',
            'password.required'                 => 'Password baru wajib diisi.',
            'password.confirmed'                => 'Ulangi password baru tidak sama.',
            'password.min'                      => 'Password baru minimal 8 karakter.',
        ]);

        $user = $request->user();
        $user->forceFill(['password' => $request->input('password')])->save();

        // Keluarkan sesi lain, tetap login di perangkat ini
        Auth::logoutOtherDevices($request->input('password'));
        $request->session()->regenerate();

        return redirect()
            ->route('admin.settings.index', ['tab' => 'account'])
            ->with('success', 'Password berhasil diganti.');
    }

    /* ---------------------------------------------------------------
     | HELPER
     * --------------------------------------------------------------- */

    /**
     * Upload gambar baru atau hapus (kembali ke bawaan) jika "hapus_<field>" dicentang.
     */
    private function handleImage(Request $request, string $field, string $settingKey, string $folder): void
    {
        $oldPath = Setting::get($settingKey);

        if ($request->hasFile($field)) {
            $path = $request->file($field)->store($folder, 'public');
            Setting::put([$settingKey => $path]);
            $this->deleteFile($oldPath);

            return;
        }

        if ($request->boolean('remove_' . $field) && $oldPath) {
            Setting::put([$settingKey => null]);
            $this->deleteFile($oldPath);
        }
    }

    private function deleteFile(?string $path): void
    {
        if ($path && str_starts_with($path, 'settings/') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
