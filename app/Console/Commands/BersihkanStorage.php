<?php

namespace App\Console\Commands;

use App\Support\StorageCleaner;
use Illuminate\Console\Command;

/**
 * php artisan storage:bersihkan          -> hapus file yatim (umur > 24 jam)
 * php artisan storage:bersihkan --cek    -> hanya tampilkan, tidak menghapus
 */
class BersihkanStorage extends Command
{
    protected $signature = 'storage:bersihkan {--cek : Hanya tampilkan file yang akan dihapus}';

    protected $description = 'Hapus file foto/video yang sudah tidak dipakai di penyimpanan (Supabase/lokal)';

    public function handle(): int
    {
        $dry = (bool) $this->option('cek');
        $r = StorageCleaner::run(! $dry);

        if (! $r['ok']) {
            $this->error($r['error']);

            return self::FAILURE;
        }

        foreach ($r['folders'] as $folder => $f) {
            $this->line(sprintf('  %-16s %4d file  %10s   tidak terpakai: %d', $folder, $f['files'], StorageCleaner::size($f['bytes']), $f['orphans']));
        }

        $this->newLine();
        $this->line('Total terpakai : ' . StorageCleaner::size($r['bytes']) . ' dari ' . StorageCleaner::size(StorageCleaner::limitBytes()));
        $this->line('File tidak terpakai: ' . count($r['orphans']) . ' (yang umurnya < ' . StorageCleaner::GRACE_HOURS . ' jam dilewati dulu)');

        if ($dry) {
            foreach ($r['orphans'] as $o) {
                $this->line('  - ' . $o['path'] . ' (' . StorageCleaner::size($o['size']) . ')' . ($o['old'] ? '' : ' [baru, dilewati]'));
            }
            $this->info('Mode cek: tidak ada file yang dihapus.');
        } else {
            $this->info('Dihapus: ' . $r['deleted'] . ' file, hemat ' . StorageCleaner::size($r['freed']) . '.');
        }

        return self::SUCCESS;
    }
}
