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

// Hapus notifikasi lebih dari 7 hari (di Vercel lewat /cron/bersihkan-notifikasi).
Schedule::command('notifikasi:bersihkan')->dailyAt('03:00')->timezone('Asia/Jakarta');

// Jadwal otomatis (di Vercel lewat /cron/agenda-harian, /cron/pengingat-lesson, /cron/laporan-*)
Schedule::call(fn () => \App\Support\AutoReports::run('agenda'))->dailyAt('06:00')->timezone('Asia/Jakarta')->name('agenda-harian');
Schedule::call(fn () => \App\Support\AutoReports::run('reminder'))->dailyAt('17:00')->timezone('Asia/Jakarta')->name('pengingat-lesson');
Schedule::call(fn () => \App\Support\AutoReports::run('weekly'))->weeklyOn(1, '08:00')->timezone('Asia/Jakarta')->name('laporan-mingguan');
Schedule::call(fn () => \App\Support\AutoReports::run('monthly'))->monthlyOn(1, '08:00')->timezone('Asia/Jakarta')->name('laporan-bulanan');
