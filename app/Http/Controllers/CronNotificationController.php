<?php

namespace App\Http\Controllers;

use App\Support\NotificationCleaner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dipanggil otomatis oleh Vercel Cron 1x sehari: hapus notifikasi lebih dari 7 hari.
 * Diamankan dengan env CRON_SECRET (header "Authorization: Bearer <CRON_SECRET>").
 */
class CronNotificationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) env('CRON_SECRET', '');

        if ($secret === '' || ! hash_equals('Bearer ' . $secret, (string) $request->header('Authorization'))) {
            return response()->json(['ok' => false, 'detail' => 'Unauthorized'], 401);
        }

        return response()->json(['ok' => true, 'notifikasi_dihapus' => NotificationCleaner::run()]);
    }
}
