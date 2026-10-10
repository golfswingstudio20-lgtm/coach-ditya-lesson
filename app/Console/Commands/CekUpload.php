<?php

namespace App\Console\Commands;

use App\Support\Uploads;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Cek apakah upload foto benar-benar tersimpan dan bisa dibuka.
 * Pakai: php artisan cek:upload
 */
class CekUpload extends Command
{
    protected $signature = 'cek:upload';

    protected $description = 'Tes upload file ke penyimpanan (Supabase Storage / lokal) dan cek gambarnya bisa dibuka';

    public function handle(): int
    {
        $this->info('== Cek upload foto ==');
        $this->line('Penyimpanan      : ' . (Uploads::supabaseOn() ? 'Supabase Storage (bucket "' . Uploads::bucket() . '")' : 'Lokal (storage/app/public)'));
        $this->line('Upload langsung  : ' . (Uploads::directEnabled() ? 'AKTIF (ukuran file tidak dibatasi)' : 'tidak aktif (isi SUPABASE_SERVICE_KEY untuk mengaktifkan)'));
        $this->line('Batas PHP        : upload_max_filesize=' . ini_get('upload_max_filesize') . ', post_max_size=' . ini_get('post_max_size'));

        $path = 'gallery/images/_cek-upload.txt';

        try {
            $ok = Storage::disk('public')->put($path, 'cek ' . now());
        } catch (Throwable $e) {
            $ok = false;
            $this->error('Error: ' . $e->getMessage());
        }

        if (! $ok) {
            $this->error('X Gagal menyimpan file. Cek SUPABASE_S3_KEY, SUPABASE_S3_SECRET, SUPABASE_S3_ENDPOINT, SUPABASE_S3_REGION di .env / Vercel.');

            return self::FAILURE;
        }

        $url = Storage::disk('public')->url($path);
        $this->line('File tersimpan   : ' . $path);
        $this->line('Alamat file      : ' . $url);

        if (str_starts_with($url, 'http')) {
            try {
                $status = Http::timeout(15)->get($url)->status();
                $status === 200
                    ? $this->info('OK File bisa dibuka (200).')
                    : $this->error('X File tidak bisa dibuka (HTTP ' . $status . '). Pastikan bucket "' . Uploads::bucket() . '" di Supabase berstatus Public.');
            } catch (Throwable $e) {
                $this->warn('! Tidak bisa mengecek alamat: ' . $e->getMessage());
            }
        } elseif (! is_dir(public_path('storage'))) {
            $this->error('X Folder public/storage belum ada. Jalankan: php artisan storage:link');
        } else {
            $this->info('OK File lokal tersedia di public/storage.');
        }

        if (Uploads::directEnabled()) {
            try {
                Uploads::sign('gallery/images', 'cek.jpg');
                $this->info('OK Link upload langsung Supabase berhasil dibuat.');
            } catch (Throwable $e) {
                $this->error('X Upload langsung gagal: ' . $e->getMessage());
            }
        }

        Storage::disk('public')->delete($path);

        return self::SUCCESS;
    }
}
