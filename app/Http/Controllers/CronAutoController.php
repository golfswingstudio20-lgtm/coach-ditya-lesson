<?php

namespace App\Http\Controllers;

use App\Support\AutoReports;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vercel Cron untuk jadwal otomatis (agenda, pengingat, laporan).
 * Diamankan dengan env CRON_SECRET (header "Authorization: Bearer <CRON_SECRET>").
 */
class CronAutoController extends Controller
{
    private const PATHS = [
        'agenda-harian'    => 'agenda',
        'pengingat-lesson' => 'reminder',
        'laporan-mingguan' => 'weekly',
        'laporan-bulanan'  => 'monthly',
    ];

    public function __invoke(Request $request, string $task): JsonResponse
    {
        $secret = (string) env('CRON_SECRET', '');

        if ($secret === '' || ! hash_equals('Bearer ' . $secret, (string) $request->header('Authorization'))) {
            return response()->json(['ok' => false, 'detail' => 'Unauthorized'], 401);
        }

        abort_unless(isset(self::PATHS[$task]), 404);

        return response()->json(AutoReports::run(self::PATHS[$task]));
    }
}
