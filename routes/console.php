<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hosting biasa (bukan Vercel): hapus file yang tidak terpakai setiap hari jam 02:00 WIB.
// Di Vercel, jadwal ini dijalankan oleh Vercel Cron (vercel.json -> /cron/bersihkan-storage).
Schedule::command('storage:bersihkan')->dailyAt('02:00')->timezone('Asia/Jakarta');
