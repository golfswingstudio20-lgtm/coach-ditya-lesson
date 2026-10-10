<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

/**
 * Kirim pesan WhatsApp lewat layanan yang dipilih di Settings (Fonnte / Wablas).
 */
class WhatsApp
{
    public const PROVIDERS = [
        'off'    => 'Nonaktif',
        'fonnte' => 'Fonnte',
        'wablas' => 'Wablas',
    ];

    /** Kejadian yang bisa dikirim ke WhatsApp admin */
    public const ADMIN_EVENTS = [
        'created'     => 'Booking baru',
        'paid'        => 'Pembayaran (lunas / perlu dicek)',
        'cancelled'   => 'Customer membatalkan booking',
        'rescheduled' => 'Customer mengubah jadwal',
    ];

    public const DEFAULT_TEMPLATE = "*{judul}*\n{pesan}\n\nBuka: {link}\n— {usaha}";

    /** Mencegah pesan yang sama terkirim berkali-kali dalam satu request */
    private static array $queued = [];

    public static function provider(): string
    {
        $provider = (string) Setting::get('wa_provider', 'off');

        return array_key_exists($provider, self::PROVIDERS) ? $provider : 'off';
    }

    public static function enabled(): bool
    {
        return self::provider() !== 'off' && self::token() !== null && self::adminNumbers() !== [];
    }

    public static function token(): ?string
    {
        return self::decrypt(Setting::get('wa_token'));
    }

    public static function secret(): ?string
    {
        return self::decrypt(Setting::get('wa_secret'));
    }

    public static function hasToken(): bool
    {
        return self::token() !== null;
    }

    public static function wablasUrl(): string
    {
        return rtrim((string) Setting::get('wa_wablas_url', ''), '/');
    }

    /** Nomor admin, format 62xxxxxxxx */
    public static function adminNumbers(): array
    {
        $raw = (string) Setting::get('wa_admin_numbers', '');

        return array_values(array_unique(array_filter(array_map(
            fn ($number) => self::normalize($number),
            preg_split('/[,;\s]+/', $raw)
        ))));
    }

    public static function adminEventEnabled(string $event): bool
    {
        return array_key_exists($event, self::ADMIN_EVENTS)
            && Setting::get("wa_admin_{$event}", '1') === '1';
    }

    public static function template(): string
    {
        return (string) Setting::get('wa_template', self::DEFAULT_TEMPLATE);
    }

    /** "0858 8680 3126" -> "6285886803126" */
    public static function normalize(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return '';
        }

        return str_starts_with($digits, '0') ? '62' . substr($digits, 1) : $digits;
    }

    public static function format(string $title, string $message, ?string $url): string
    {
        $link = $url ? (str_starts_with($url, 'http') ? $url : url($url)) : url('/');

        return strtr(self::template(), [
            '{judul}' => $title,
            '{pesan}' => $message,
            '{link}'  => $link,
            '{usaha}' => Brand::name(),
        ]);
    }

    /**
     * Antrekan WhatsApp ke admin (dikirim setelah halaman selesai dimuat,
     * jadi customer tidak perlu menunggu).
     */
    public static function queueToAdmins(string $event, string $title, string $message, ?string $url): void
    {
        if (! self::enabled() || ! self::adminEventEnabled($event)) {
            return;
        }

        $text = self::format($title, $message, $url);
        $key  = md5($event . $text);

        if (isset(self::$queued[$key])) {
            return;
        }

        self::$queued[$key] = true;
        $numbers = self::adminNumbers();

        dispatch(function () use ($numbers, $text) {
            $result = WhatsApp::send($numbers, $text);

            if (! $result['ok']) {
                logger()->warning('WhatsApp gagal dikirim: ' . $result['detail']);
            }
        })->afterResponse();
    }

    /**
     * Kirim langsung. Hasil: ['ok' => bool, 'detail' => string]
     */
    public static function send(array $numbers, string $message): array
    {
        $token = self::token();

        if (self::provider() === 'off' || ! $token) {
            return ['ok' => false, 'detail' => 'Layanan WhatsApp belum diaktifkan atau token kosong.'];
        }

        if ($numbers === []) {
            return ['ok' => false, 'detail' => 'Nomor WhatsApp tujuan kosong.'];
        }

        try {
            if (self::provider() === 'fonnte') {
                $response = Http::timeout(15)
                    ->withHeaders(['Authorization' => $token])
                    ->asForm()
                    ->post('https://api.fonnte.com/send', [
                        'target'      => implode(',', $numbers),
                        'message'     => $message,
                        'countryCode' => '0',
                    ]);

                $json = $response->json() ?? [];
                $ok   = $response->successful() && ($json['status'] ?? false) === true;

                return ['ok' => $ok, 'detail' => $ok ? 'Terkirim lewat Fonnte.' : ('Fonnte: ' . ($json['reason'] ?? $json['detail'] ?? $response->body()))];
            }

            // Wablas
            $base = self::wablasUrl();

            if ($base === '') {
                return ['ok' => false, 'detail' => 'URL server Wablas belum diisi.'];
            }

            $auth = $token . (self::secret() ? '.' . self::secret() : '');

            $response = Http::timeout(15)
                ->withHeaders(['Authorization' => $auth])
                ->asForm()
                ->post($base . '/api/send-message', [
                    'phone'   => implode(',', $numbers),
                    'message' => $message,
                ]);

            $json = $response->json() ?? [];
            $ok   = $response->successful() && ($json['status'] ?? false) === true;

            return ['ok' => $ok, 'detail' => $ok ? 'Terkirim lewat Wablas.' : ('Wablas: ' . ($json['message'] ?? $response->body()))];
        } catch (\Throwable $e) {
            return ['ok' => false, 'detail' => 'Tidak bisa menghubungi layanan WhatsApp: ' . $e->getMessage()];
        }
    }

    /**
     * Kirim gambar (URL publik) + keterangan. Hasil: ['ok' => bool, 'detail' => string]
     */
    public static function sendImage(array $numbers, string $caption, string $imageUrl): array
    {
        $token = self::token();

        if (self::provider() === 'off' || ! $token) {
            return ['ok' => false, 'detail' => 'Layanan WhatsApp belum diaktifkan atau token kosong.'];
        }

        if ($numbers === []) {
            return ['ok' => false, 'detail' => 'Nomor WhatsApp admin belum diisi.'];
        }

        try {
            if (self::provider() === 'fonnte') {
                $response = Http::timeout(30)
                    ->withHeaders(['Authorization' => $token])
                    ->asForm()
                    ->post('https://api.fonnte.com/send', [
                        'target'      => implode(',', $numbers),
                        'message'     => $caption,
                        'url'         => $imageUrl,
                        'countryCode' => '0',
                    ]);

                $json = $response->json() ?? [];
                $ok   = $response->successful() && ($json['status'] ?? false) === true;

                return ['ok' => $ok, 'detail' => $ok ? 'Terkirim lewat Fonnte.' : ('Fonnte: ' . ($json['reason'] ?? $json['detail'] ?? $response->body()))];
            }

            $base = self::wablasUrl();

            if ($base === '') {
                return ['ok' => false, 'detail' => 'URL server Wablas belum diisi.'];
            }

            $auth = $token . (self::secret() ? '.' . self::secret() : '');

            $response = Http::timeout(30)
                ->withHeaders(['Authorization' => $auth])
                ->asForm()
                ->post($base . '/api/send-image', [
                    'phone'   => implode(',', $numbers),
                    'caption' => $caption,
                    'image'   => $imageUrl,
                ]);

            $json = $response->json() ?? [];
            $ok   = $response->successful() && ($json['status'] ?? false) === true;

            return ['ok' => $ok, 'detail' => $ok ? 'Terkirim lewat Wablas.' : ('Wablas: ' . ($json['message'] ?? $response->body()))];
        } catch (\Throwable $e) {
            return ['ok' => false, 'detail' => 'Tidak bisa menghubungi layanan WhatsApp: ' . $e->getMessage()];
        }
    }

    private static function decrypt(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
