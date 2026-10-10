<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Bersihkan "file yatim" di penyimpanan publik (Supabase Storage / storage lokal):
 * file yang tidak lagi dipakai oleh data mana pun, misalnya foto yang sudah diganti,
 * upload yang batal disimpan, atau poster jadwal lama yang sudah dikirim ke WhatsApp.
 *
 * Aman: file yang masih tercatat di database TIDAK PERNAH dihapus, dan file baru
 * (umur < GRACE_HOURS jam) dilewati supaya upload yang sedang berlangsung tidak ikut terhapus.
 * Bukti pembayaran (disk private) tidak disentuh.
 */
class StorageCleaner
{
    public const GRACE_HOURS = 24;

    /** Folder yang dikelola => label */
    public const FOLDERS = [
        'gallery/images' => 'Foto galeri',
        'gallery/videos' => 'Video galeri',
        'gallery/albums' => 'Sampul folder galeri',
        'events/posters' => 'Poster event',
        'events/payments' => 'QR pembayaran event',
        'programs'       => 'Foto program',
        'coaches'        => 'Foto coach',
        'content'        => 'Foto konten & poster jadwal',
        'settings'       => 'Logo & background',
    ];

    /** Kolom database yang menyimpan path file: tabel => kolom[] */
    private const COLUMNS = [
        'galleries'      => ['image', 'video_file'],
        'gallery_albums' => ['cover'],
        'events'         => ['poster', 'pay_qr'],
        'programs'       => ['image'],
        'coaches'        => ['photo'],
    ];

    /** Batas penyimpanan paket (MB), bisa diubah lewat env SUPABASE_STORAGE_LIMIT_MB. */
    public static function limitBytes(): int
    {
        return (int) env('SUPABASE_STORAGE_LIMIT_MB', 1024) * 1024 * 1024;
    }

    /**
     * Pindai penyimpanan. $delete = true untuk benar-benar menghapus file yatim.
     *
     * @return array{ok: bool, error: ?string, folders: array, files: int, bytes: int, orphans: array, deleted: int, freed: int, at: string}
     */
    public static function run(bool $delete = false): array
    {
        $result = ['ok' => true, 'error' => null, 'folders' => [], 'files' => 0, 'bytes' => 0, 'orphans' => [], 'deleted' => 0, 'freed' => 0, 'at' => now()->toDateTimeString()];

        try {
            $used = self::referencedPaths();
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'error' => 'Database tidak bisa dibaca, pembersihan dibatalkan: ' . $e->getMessage()] + $result;
        }

        $api = Uploads::directEnabled();
        $cutoff = now()->subHours(self::GRACE_HOURS)->getTimestamp();
        $toDelete = [];

        foreach (self::FOLDERS as $folder => $label) {
            $info = ['label' => $label, 'files' => 0, 'bytes' => 0, 'orphans' => 0, 'orphan_bytes' => 0, 'error' => false];

            try {
                foreach (($api ? self::listApi($folder) : self::listDisk($folder)) as [$path, $size, $time]) {
                    $info['files']++;
                    $info['bytes'] += $size;

                    if (isset($used[$path]) || basename($path) === '.gitignore' || basename($path) === '.emptyFolderPlaceholder') {
                        continue;
                    }

                    $old = $time <= $cutoff;
                    $info['orphans']++;
                    $info['orphan_bytes'] += $size;
                    $result['orphans'][] = ['path' => $path, 'size' => $size, 'time' => $time, 'old' => $old, 'folder' => $label];

                    if ($old) {
                        $toDelete[$path] = $size;
                    }
                }
            } catch (\Throwable $e) {
                report($e);
                $info['error'] = true;
                $result['error'] = 'Sebagian folder tidak bisa dibaca dari penyimpanan: ' . \Illuminate\Support\Str::limit($e->getMessage(), 160);
            }

            $result['folders'][$folder] = $info;
            $result['files'] += $info['files'];
            $result['bytes'] += $info['bytes'];
        }

        if ($delete && $toDelete) {
            foreach (array_chunk(array_keys($toDelete), 100) as $chunk) {
                try {
                    $done = $api ? self::deleteApi($chunk) : self::deleteDisk($chunk);
                    foreach ($done as $path) {
                        $result['deleted']++;
                        $result['freed'] += $toDelete[$path] ?? 0;
                    }
                } catch (\Throwable $e) {
                    report($e);
                    $result['error'] = 'Sebagian file gagal dihapus: ' . \Illuminate\Support\Str::limit($e->getMessage(), 160);
                }
            }
        }

        if ($delete) {
            Setting::put(['storage_cleanup_last' => json_encode([
                'at' => $result['at'], 'deleted' => $result['deleted'], 'freed' => $result['freed'],
                'files' => $result['files'] - $result['deleted'], 'bytes' => $result['bytes'] - $result['freed'],
            ])]);
        }

        return $result;
    }

    /** Daftar file lewat disk Laravel (lokal / S3). Hasil: [[path, size, time]] */
    private static function listDisk(string $folder): array
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($folder) && ! $disk->directoryExists($folder)) {
            return [];
        }

        $out = [];
        foreach ($disk->listContents($folder, true) as $item) {
            if ($item->isFile()) {
                $out[] = [ltrim($item->path(), '/'), (int) ($item->fileSize() ?? 0), (int) ($item->lastModified() ?? time())];
            }
        }

        return $out;
    }

    /** Daftar file lewat Supabase Storage API (service key), termasuk sub-folder. */
    private static function listApi(string $folder): array
    {
        $out = [];
        $offset = 0;
        do {
            $res = Http::withHeaders(Uploads::headers())->timeout(20)
                ->post(Uploads::apiBase() . '/object/list/' . Uploads::bucket(), [
                    'prefix' => $folder, 'limit' => 1000, 'offset' => $offset,
                    'sortBy' => ['column' => 'name', 'order' => 'asc'],
                ]);
            if (! $res->successful()) {
                throw new \RuntimeException('Supabase list ' . $folder . ' (' . $res->status() . '): ' . $res->body());
            }
            $items = (array) $res->json();
            foreach ($items as $item) {
                $path = $folder . '/' . ($item['name'] ?? '');
                if (empty($item['id'])) {
                    $out = array_merge($out, self::listApi($path)); // sub-folder
                    continue;
                }
                $out[] = [$path, (int) ($item['metadata']['size'] ?? 0), strtotime($item['updated_at'] ?? $item['created_at'] ?? 'now') ?: time()];
            }
            $offset += 1000;
        } while (count($items) === 1000);

        return $out;
    }

    /** Hapus file; hasil = path yang berhasil dihapus. */
    private static function deleteDisk(array $paths): array
    {
        Storage::disk('public')->delete($paths);

        return $paths;
    }

    private static function deleteApi(array $paths): array
    {
        $res = Http::withHeaders(Uploads::headers())->timeout(30)
            ->send('DELETE', Uploads::apiBase() . '/object/' . Uploads::bucket(), ['json' => ['prefixes' => array_values($paths)]]);
        if (! $res->successful()) {
            throw new \RuntimeException('Supabase delete (' . $res->status() . '): ' . $res->body());
        }

        $json = (array) $res->json();
        $done = array_values(array_intersect($paths, array_map(fn ($i) => is_array($i) ? ($i['name'] ?? '') : '', $json)));

        // Beberapa versi Supabase hanya mengembalikan nama file: cocokkan dari jumlahnya
        return $done ?: (count($json) === count($paths) ? array_values($paths) : $done);
    }

    public static function lastRun(): ?array
    {
        $data = json_decode((string) Setting::get('storage_cleanup_last'), true);

        return is_array($data) ? $data : null;
    }

    /** Semua path file yang masih dipakai (database + pengaturan). */
    private static function referencedPaths(): array
    {
        $used = [];

        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }
                foreach (DB::table($table)->whereNotNull($column)->pluck($column) as $path) {
                    $used[self::normalize($path)] = true;
                }
            }
        }

        // Pengaturan (logo, background, QRIS, foto konten, logo poster, poster terakhir dikirim, dll.)
        foreach (DB::table('settings')->pluck('value') as $value) {
            if (is_string($value) && $value !== '' && strlen($value) < 500) {
                $used[self::normalize($value)] = true;
            }
        }

        return $used;
    }

    private static function normalize(string $path): string
    {
        // Path bisa tersimpan sebagai "storage/..." atau URL penuh: ambil bagian folder/nama-file saja
        $path = preg_replace('#^https?://[^?]+?/(?:storage/v1/object/public/[^/]+/|storage/)#', '', trim($path));

        return ltrim(preg_replace('#^/?storage/#', '', $path), '/');
    }

    public static function size(int $bytes): string
    {
        return $bytes >= 1073741824 ? number_format($bytes / 1073741824, 2, ',', '.') . ' GB'
            : ($bytes >= 1048576 ? number_format($bytes / 1048576, 1, ',', '.') . ' MB'
            : number_format(max(0, $bytes) / 1024, 0, ',', '.') . ' KB');
    }
}
