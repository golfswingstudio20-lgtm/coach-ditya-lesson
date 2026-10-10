<?php

namespace App\Http\Controllers;

use App\Support\StorageCleaner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dipanggil otomatis oleh Vercel Cron (lihat vercel.json), 1x sehari.
 * Diamankan dengan env CRON_SECRET: Vercel mengirim header "Authorization: Bearer <CRON_SECRET>".
 */
class CronController extends Controller
{
    public function cleanup(Request $request): JsonResponse
    {
        $secret = (string) env('CRON_SECRET', '');

        if ($secret === '' || ! hash_equals('Bearer ' . $secret, (string) $request->header('Authorization'))) {
            return response()->json(['ok' => false, 'detail' => 'Unauthorized'], 401);
        }

        $r = StorageCleaner::run(true);

        return response()->json([
            'ok'      => $r['ok'],
            'detail'  => $r['error'],
            'deleted' => $r['deleted'],
            'freed'   => StorageCleaner::size($r['freed']),
            'used'    => StorageCleaner::size($r['bytes'] - $r['freed']),
        ], $r['ok'] ? 200 : 500);
    }
}
