<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hapus notifikasi lonceng (admin & customer) yang umurnya lebih dari KEEP_DAYS hari.
 * Jadwal sendiri: Vercel Cron -> /cron/bersihkan-notifikasi (1x sehari).
 */
class NotificationCleaner
{
    public const KEEP_DAYS = 7;

    public static function run(): int
    {
        if (! Schema::hasTable('notifications')) {
            return 0;
        }

        $deleted = DB::table('notifications')->where('created_at', '<', now()->subDays(self::KEEP_DAYS))->delete();

        Setting::put(['notif_cleanup_last' => json_encode(['at' => now()->toDateTimeString(), 'deleted' => $deleted])]);

        return $deleted;
    }
}
