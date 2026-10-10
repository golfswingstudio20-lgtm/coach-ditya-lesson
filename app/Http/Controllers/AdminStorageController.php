<?php

namespace App\Http\Controllers;

use App\Support\StorageCleaner;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin > Penyimpanan: pemakaian Supabase Storage + bersihkan file yang tidak terpakai.
 */
class AdminStorageController extends Controller
{
    public function index(): View
    {
        $scan = StorageCleaner::run(false);

        return view('admin.storage.index', ['scan' => $scan, 'last' => StorageCleaner::lastRun()]);
    }

    public function clean(): RedirectResponse
    {
        $r = StorageCleaner::run(true);

        if (! $r['ok']) {
            return back()->with('error', $r['error']);
        }

        return back()->with('success', $r['deleted']
            ? 'Berhasil menghapus ' . $r['deleted'] . ' file tidak terpakai (hemat ' . StorageCleaner::size($r['freed']) . ').'
            : 'Tidak ada file lama yang perlu dihapus.');
    }
}
