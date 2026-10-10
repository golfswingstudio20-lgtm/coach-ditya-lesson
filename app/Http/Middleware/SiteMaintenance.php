<?php

namespace App\Http\Middleware;

use App\Support\Brand;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mode maintenance dari menu Settings.
 * Visitor & customer melihat halaman "sedang perbaikan", admin tetap bisa masuk.
 */
class SiteMaintenance
{
    /** Alamat yang tetap bisa dibuka saat maintenance (login admin, dsb.) */
    private const ALLOWED = [
        'login', 'logout', 'forgot-password', 'reset-password*', 'two-factor-challenge',
        'email/*', 'user/*', 'livewire*', 'up', 'storage/*', 'admin*', 'cron/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! Brand::maintenance()) {
            return $next($request);
        }

        if ($request->user()?->role === 'admin' || $request->is(...self::ALLOWED)) {
            return $next($request);
        }

        return response()->view('maintenance', [], 503);
    }
}
