<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Upload file admin (foto & video) tanpa batas ukuran dari website.
 *
 * - Online (Vercel + Supabase) dengan SUPABASE_SERVICE_KEY: browser meng-upload file LANGSUNG
 *   ke Supabase Storage lewat "signed upload URL", jadi tidak kena batas 4,5 MB Vercel / batas PHP.
 *   Form hanya mengirim path file (field "<nama>_path").
 * - Tanpa kunci itu: upload biasa lewat form, tapi kalau gagal tersimpan muncul pesan error
 *   (sebelumnya diam-diam "berhasil" padahal gambarnya tidak ada).
 */
class Uploads
{
    /** Folder yang boleh dipakai upload langsung. */
    public const FOLDERS = ['gallery/images', 'gallery/videos', 'gallery/albums', 'events/posters', 'programs', 'coaches'];

    public static function supabaseOn(): bool
    {
        return filter_var(env('SUPABASE_STORAGE', false), FILTER_VALIDATE_BOOL);
    }

    public static function serviceKey(): ?string
    {
        foreach (['SUPABASE_SERVICE_KEY', 'SUPABASE_SERVICE_ROLE_KEY', 'SUPABASE_SECRET_KEY'] as $name) {
            $value = trim((string) env($name, ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /** Upload langsung ke Supabase aktif? */
    public static function directEnabled(): bool
    {
        return self::supabaseOn() && self::serviceKey() && trim((string) env('SUPABASE_URL', '')) !== '';
    }

    public static function bucket(): string
    {
        return (string) env('SUPABASE_PUBLIC_BUCKET', 'public');
    }

    /**
     * Minta URL upload sekali pakai ke Supabase.
     *
     * @return array{upload_url:string,path:string,public_url:string}
     */
    public static function sign(string $folder, string $filename): array
    {
        if (! in_array($folder, self::FOLDERS, true)) {
            throw new \InvalidArgumentException('Folder tidak diizinkan.');
        }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $ext = preg_match('/^[a-z0-9]{1,8}$/', $ext) ? $ext : 'bin';
        $path = $folder . '/' . Str::uuid() . '.' . $ext;

        $base = rtrim((string) env('SUPABASE_URL'), '/') . '/storage/v1';
        $key  = self::serviceKey();

        if (str_starts_with($key, 'sb_publishable_') || self::jwtRole($key) === 'anon') {
            throw new \RuntimeException('SUPABASE_SERVICE_KEY berisi kunci publik (publishable/anon). Ganti dengan kunci rahasia: sb_secret_... atau service_role.');
        }

        // Kunci baru (sb_secret_...) hanya boleh di header "apikey"; kunci lama (JWT service_role) di keduanya.
        $headers = ['apikey' => $key];
        if (str_starts_with($key, 'eyJ')) {
            $headers['Authorization'] = 'Bearer ' . $key;
        }

        $res = Http::withHeaders($headers)
            ->timeout(15)
            ->post($base . '/object/upload/sign/' . self::bucket() . '/' . $path);

        if (! $res->successful() || ! $res->json('url')) {
            throw new \RuntimeException('Supabase menolak membuat link upload (' . $res->status() . '): ' . Str::limit((string) $res->body(), 200));
        }

        return [
            'upload_url' => $base . $res->json('url'),
            'path'       => $path,
            'public_url' => Storage::disk('public')->url($path),
        ];
    }

    /**
     * Ambil file dari form: path hasil upload langsung ("<field>_path") atau file biasa.
     * Mengembalikan path baru, atau null kalau tidak ada file baru.
     */
    public static function take(Request $request, string $field, string $folder): ?string
    {
        $direct = trim((string) $request->input($field . '_path', ''));

        if ($direct !== '') {
            if (! str_starts_with($direct, $folder . '/') || str_contains($direct, '..') || ! preg_match('~^[a-z0-9/_-]+\.[a-z0-9]{1,8}$~i', $direct)) {
                throw ValidationException::withMessages([$field => 'File upload tidak dikenali. Silakan pilih ulang file.']);
            }

            return $direct;
        }

        $file = $request->file($field);

        if (! $file) {
            return null;
        }

        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            $tooBig = $file instanceof UploadedFile && in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);

            throw ValidationException::withMessages([$field => $tooBig
                ? 'File terlalu besar untuk server ini. Aktifkan upload langsung ke Supabase (SUPABASE_SERVICE_KEY) supaya ukuran file tidak dibatasi.'
                : 'File gagal di-upload. Coba lagi.']);
        }

        try {
            $stored = $file->store($folder, 'public');
        } catch (Throwable $e) {
            report($e);
            $stored = false;
        }

        if (! $stored) {
            throw ValidationException::withMessages([$field => 'File tidak berhasil disimpan ke penyimpanan (Supabase Storage). Cek kunci S3 Supabase, lalu jalankan: php artisan cek:upload']);
        }

        return $stored;
    }

    /** Role di dalam kunci JWT lama (anon / service_role), atau null. */
    private static function jwtRole(string $key): ?string
    {
        $parts = explode('.', $key);
        if (count($parts) !== 3) {
            return null;
        }
        $payload = json_decode((string) base64_decode(strtr($parts[1], '-_', '+/')), true);

        return is_array($payload) ? ($payload['role'] ?? null) : null;
    }

    /** Hapus file lama yang dulu di-upload lewat admin. */
    public static function delete(?string $path, string $prefix): void
    {
        if (! $path || ! str_starts_with($path, $prefix)) {
            return;
        }

        try {
            Storage::disk('public')->delete($path);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
