<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\AutoReports;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin > Jadwal Otomatis: atur on/off, lihat contoh pesan, kirim sekarang.
 */
class AdminAutoController extends Controller
{
    public function index(): View
    {
        $previews = [
            'agenda'  => AutoReports::agendaText(Carbon::today()),
            'weekly'  => AutoReports::reportText(Carbon::today()->subDays(7), Carbon::yesterday(), '7 hari terakhir'),
            'monthly' => AutoReports::reportText(Carbon::today()->subMonthNoOverflow()->startOfMonth(), Carbon::today()->subMonthNoOverflow()->endOfMonth(), Carbon::today()->subMonthNoOverflow()->locale('id')->translatedFormat('F Y')),
        ];

        return view('admin.auto.index', compact('previews'));
    }

    public function toggle(Request $request, string $job): RedirectResponse
    {
        abort_unless(isset(AutoReports::JOBS[$job]), 404);
        $on = ! AutoReports::enabled($job);
        Setting::put(['auto_' . $job => $on ? '1' : '0']);

        return back()->with('success', AutoReports::JOBS[$job][0] . ($on ? ' diaktifkan.' : ' dimatikan.'));
    }

    public function run(string $job): RedirectResponse
    {
        abort_unless(isset(AutoReports::JOBS[$job]), 404);
        $r = AutoReports::run($job, true);

        return back()->with($r['ok'] ? 'success' : 'error', AutoReports::JOBS[$job][0] . ': ' . $r['detail']);
    }
}
