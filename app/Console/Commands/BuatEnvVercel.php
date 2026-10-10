<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Membuat file .env.vercel dari .env saat ini, siap di-paste ke
 * Vercel > Project > Settings > Environment Variables.
 */
class BuatEnvVercel extends Command
{
    protected $signature = 'vercel:env {--url= : Alamat website di Vercel, mis. https://golf-booking-lesson.vercel.app}';

    protected $description = 'Buat .env.vercel untuk di-paste ke Environment Variables Vercel';

    /** Diatur oleh vercel.json, jadi tidak perlu disalin. */
    private const SKIP = [
        'APP_ENV', 'APP_DEBUG', 'APP_URL', 'LOG_CHANNEL', 'LOG_STACK', 'LOG_LEVEL', 'SESSION_DRIVER', 'CACHE_STORE',
        'QUEUE_CONNECTION', 'FILESYSTEM_DISK', 'SUPABASE_STORAGE', 'VIEW_COMPILED_PATH', 'APP_MAINTENANCE_DRIVER',
    ];

    public function handle(): int
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            $this->error('File .env tidak ditemukan.');

            return self::FAILURE;
        }

        $url   = rtrim($this->option('url') ?: 'https://GANTI-NAMA-PROJECT.vercel.app', '/');
        $lines = ['APP_ENV=production', 'APP_DEBUG=false', 'APP_URL=' . $url];
        $env   = [];

        foreach (preg_split('/\R/', (string) file_get_contents($envPath)) as $line) {
            if (! preg_match('/^\s*([A-Z0-9_]+)\s*=(.*)$/', $line, $m)) {
                continue;
            }
            $env[$m[1]] = trim($m[2]);
            if (in_array($m[1], self::SKIP, true) || trim($m[2]) === '') {
                continue;
            }
            $lines[] = $m[1] . '=' . trim($m[2]);
        }

        file_put_contents(base_path('.env.vercel'), implode(PHP_EOL, $lines) . PHP_EOL);

        $this->info('Dibuat: .env.vercel (JANGAN di-commit / dibagikan, berisi password).');

        if (($env['DB_CONNECTION'] ?? '') !== 'pgsql') {
            $this->warn('DB_CONNECTION di .env belum pgsql. Isi data Supabase dulu, lalu jalankan ulang perintah ini.');
        }
        if (($env['SUPABASE_S3_KEY'] ?? '') === '') {
            $this->warn('SUPABASE_S3_KEY masih kosong. Upload logo/galeri/bukti transfer tidak akan jalan di Vercel.');
        }
        if (($env['SUPABASE_SERVICE_KEY'] ?? $env['SUPABASE_SERVICE_ROLE_KEY'] ?? '') === '') {
            $this->warn('SUPABASE_SERVICE_KEY kosong. Upload foto/video besar dari admin akan dibatasi server (maks. ~4 MB di Vercel).');
        }
        if (! $this->option('url')) {
            $this->warn('APP_URL masih contoh. Setelah tahu alamat Vercel, ganti APP_URL di Vercel.');
        }

        return self::SUCCESS;
    }
}
