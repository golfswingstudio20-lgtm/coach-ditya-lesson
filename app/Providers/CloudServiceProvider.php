<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

/**
 * Pengaturan saat aplikasi berjalan online (Vercel + Supabase).
 * Di komputer lokal tanpa SUPABASE_STORAGE / VERCEL, tidak mengubah apa pun.
 */
class CloudServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! filter_var(env('SUPABASE_STORAGE', false), FILTER_VALIDATE_BOOL)) {
            // Lokal: alamat gambar relatif (/storage/...), supaya tetap tampil walau APP_URL beda
            // dengan alamat yang dibuka di browser (mis. http://golf-booking-lesson.test).
            config(['filesystems.disks.public.url' => '/storage']);

            return;
        }

        $s3 = [
            'driver'                  => 's3',
            'key'                     => env('SUPABASE_S3_KEY'),
            'secret'                  => env('SUPABASE_S3_SECRET'),
            'region'                  => env('SUPABASE_S3_REGION', 'ap-southeast-1'),
            'endpoint'                => env('SUPABASE_S3_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'throw'                   => false,
            'report'                  => true,
        ];

        $publicBucket = env('SUPABASE_PUBLIC_BUCKET', 'public');

        config([
            // File yang boleh dilihat semua orang: logo, background, galeri, QRIS.
            'filesystems.disks.public' => $s3 + [
                'bucket' => $publicBucket,
                'url'    => rtrim((string) env('SUPABASE_URL'), '/') . '/storage/v1/object/public/' . $publicBucket,
            ],
            // File rahasia: bukti transfer customer.
            'filesystems.disks.local' => $s3 + [
                'bucket' => env('SUPABASE_PRIVATE_BUCKET', 'private'),
            ],
        ]);
    }

    public function boot(): void
    {
        if (env('VERCEL') || filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOL)) {
            URL::forceScheme('https');
        }
    }
}
