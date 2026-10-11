<?php

namespace App\Console\Commands;

use App\Support\NotificationCleaner;
use Illuminate\Console\Command;

/** php artisan notifikasi:bersihkan -> hapus notifikasi lebih dari 7 hari */
class BersihkanNotifikasi extends Command
{
    protected $signature = 'notifikasi:bersihkan';

    protected $description = 'Hapus notifikasi yang lebih lama dari 7 hari';

    public function handle(): int
    {
        $this->info('Notifikasi dihapus: ' . NotificationCleaner::run());

        return self::SUCCESS;
    }
}
