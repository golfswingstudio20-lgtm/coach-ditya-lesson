<?php

use App\Http\Controllers\AdminChatController;
use App\Http\Controllers\AdminCoachController;
use App\Http\Controllers\AdminContentController;
use App\Http\Controllers\AdminGalleryAlbumController;
use App\Http\Controllers\AdminSchedulePosterController;
use App\Http\Controllers\AdminStorageController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\CronNotificationController;
use App\Http\Controllers\AdminUploadController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CustomerPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Chat member & Kelola Coach
|--------------------------------------------------------------------------
*/

// Member: chat dengan admin
Route::middleware(['auth', 'verified', 'role:customer'])->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat');
    Route::get('/chat/messages', [ChatController::class, 'poll'])->name('chat.poll');
    Route::post('/chat', [ChatController::class, 'send'])->name('chat.send');

    Route::get('/jadwal-saya', [CustomerPageController::class, 'jadwal'])->name('jadwal');
});

// Vercel Cron (1x sehari): hapus file yang tidak terpakai. Diamankan dengan CRON_SECRET.
Route::get('/cron/bersihkan-storage', [CronController::class, 'cleanup'])->name('cron.storage');

// Vercel Cron (1x sehari): hapus notifikasi lebih dari 7 hari.
Route::get('/cron/bersihkan-notifikasi', CronNotificationController::class)->name('cron.notifications');

// Menu (HP): akun, halaman lain, keluar. Bisa dibuka visitor & customer.
Route::get('/menu', [CustomerPageController::class, 'menu'])->name('menu');

// Admin: kotak masuk chat, broadcast, kelola coach
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::view('/admin/menu', 'admin.menu')->name('admin.menu');
    Route::get('/admin/konten', [AdminContentController::class, 'index'])->name('admin.content.index');
    Route::put('/admin/konten/{section}', [AdminContentController::class, 'update'])->name('admin.content.update');
    Route::get('/admin/poster-jadwal', [AdminSchedulePosterController::class, 'index'])->name('admin.poster.index');
    Route::put('/admin/poster-jadwal', [AdminSchedulePosterController::class, 'update'])->name('admin.poster.update');
    Route::post('/admin/poster-jadwal/kirim', [AdminSchedulePosterController::class, 'send'])->name('admin.poster.send');
    Route::get('/admin/penyimpanan', [AdminStorageController::class, 'index'])->name('admin.storage.index');
    Route::post('/admin/penyimpanan/bersihkan', [AdminStorageController::class, 'clean'])->name('admin.storage.clean');
    Route::post('/admin/uploads/sign', [AdminUploadController::class, 'sign'])->name('admin.uploads.sign');
    Route::resource('/admin/gallery-albums', AdminGalleryAlbumController::class)
        ->parameters(['gallery-albums' => 'album'])
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('admin.gallery-albums');
    Route::get('/admin/chat', [AdminChatController::class, 'index'])->name('admin.chat.index');
    Route::get('/admin/chat/{member}/messages', [AdminChatController::class, 'poll'])->name('admin.chat.poll');
    Route::post('/admin/chat/{member}', [AdminChatController::class, 'send'])->name('admin.chat.send');
    Route::post('/admin/chat-broadcast', [AdminChatController::class, 'broadcast'])->name('admin.chat.broadcast');

    Route::resource('/admin/coaches', AdminCoachController::class)
        ->names('admin.coaches')
        ->except(['show']);
});
