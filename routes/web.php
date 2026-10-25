
<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminSettingController;
use App\Http\Controllers\AdminSecurityController;
use App\Http\Controllers\AdminBookingRescheduleController;
use App\Http\Controllers\AdminGalleryController;
use App\Http\Controllers\AdminEventController;
use App\Http\Controllers\AdminProgramController;
use App\Http\Controllers\AdminContactController;
use App\Http\Controllers\AdminContactLocationController;
use App\Http\Controllers\AdminCustomerController;
use App\Http\Controllers\AdminPaymentController;
use App\Http\Controllers\AdminScheduleBlockController;
use App\Http\Controllers\AdminOfflineBookingController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SwingAnalysisController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public / Visitor Routes
|--------------------------------------------------------------------------
*/

Route::view('/', 'welcome')
    ->name('home');

Route::view('/program', 'program')
    ->name('program');

Route::view('/galeri', 'galeri')
    ->name('galeri');

Route::view('/event', 'event')
    ->name('event');

Route::view('/contact', 'contact')
    ->name('contact');


/*
|--------------------------------------------------------------------------
| Customer / Member Routes
|--------------------------------------------------------------------------
|
| Hanya untuk user yang:
| - Sudah login
| - Sudah verified
| - Memiliki role customer
|
*/

Route::middleware([
    'auth',
    'verified',
    'role:customer',
])->group(function () {

    // Customer Dashboard
    Route::view('/dashboard', 'dashboard')
        ->name('dashboard');


    // Booking
    Route::get('/booking', [BookingController::class, 'index'])
        ->name('booking');

    Route::post('/booking', [BookingController::class, 'store'])
        ->name('booking.store');

    Route::post('/booking/{booking}/cancel', [BookingController::class, 'cancel'])
        ->name('booking.cancel');

    Route::post('/booking/{booking}/reschedule', [BookingController::class, 'reschedule'])
        ->name('booking.reschedule');


    // Payment
    Route::view('/payment', 'payment')
        ->name('payment');

    // Pembayaran booking lesson (dummy)
    Route::get('/payment/booking/{booking}', [PaymentController::class, 'show'])
        ->name('payment.booking');

    Route::post('/payment/booking/{booking}/method', [PaymentController::class, 'chooseMethod'])
        ->name('payment.booking.method');

    Route::post('/payment/booking/{booking}/reset', [PaymentController::class, 'resetMethod'])
        ->name('payment.booking.reset');

    Route::post('/payment/booking/{booking}/confirm', [PaymentController::class, 'confirm'])
        ->name('payment.booking.confirm');

    Route::get('/payment/booking/{booking}/proof', [PaymentController::class, 'proof'])
        ->name('payment.booking.proof');


    // AI Swing Analysis (Golf Analyzer) - analisis video di browser, server menyimpan hasilnya
    Route::get('/swing', [SwingAnalysisController::class, 'index'])
        ->name('swing.index');

    Route::get('/swing/baru', [SwingAnalysisController::class, 'create'])
        ->name('swing.create');

    Route::post('/swing', [SwingAnalysisController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('swing.store');

    Route::get('/swing/riwayat', [SwingAnalysisController::class, 'history'])
        ->name('swing.history');

    Route::get('/swing/{analysis}', [SwingAnalysisController::class, 'show'])
        ->whereNumber('analysis')
        ->name('swing.show');
});


/*
|--------------------------------------------------------------------------
| Reset Password
|--------------------------------------------------------------------------
*/

Route::get('/reset-password/{token}', function (string $token) {
    return view('auth.reset-password', [
        'request' => request(),
    ]);
})->name('password.reset');


/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Hanya untuk user yang:
| - Sudah login
| - Sudah verified
| - Memiliki role admin
|
*/

Route::middleware([
    'auth',
    'verified',
    'role:admin',
])->group(function () {

    // Admin Dashboard
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
        ->name('admin.dashboard');


    /*
    |--------------------------------------------------------------------------
    | Admin Gallery
    |--------------------------------------------------------------------------
    |
    | Mengelola foto dan video galeri:
    | - Menampilkan daftar media
    | - Menambahkan media
    | - Mengedit media
    | - Menghapus media
    |
    */

    Route::resource('/admin/gallery', AdminGalleryController::class)
        ->names('admin.gallery')
        ->except(['show']);

    /*
    |--------------------------------------------------------------------------
    | Admin Event
    |--------------------------------------------------------------------------
    |
    | Mengelola event: poster, jadwal, lokasi, harga, status registrasi.
    | Event aktif tampil otomatis di halaman /event.
    |
    */

    Route::resource('/admin/events', AdminEventController::class)
        ->names('admin.events')
        ->except(['show']);

    /*
    |--------------------------------------------------------------------------
    | Admin Program
    |--------------------------------------------------------------------------
    |
    | Mengelola kartu program: foto, level, nama, deskripsi, poin fitur.
    | Program aktif tampil otomatis di halaman /program.
    |
    */

    Route::resource('/admin/programs', AdminProgramController::class)
        ->names('admin.programs')
        ->except(['show']);

    /*
    |--------------------------------------------------------------------------
    | Admin Contact
    |--------------------------------------------------------------------------
    |
    | Informasi kontak (WhatsApp, email, jam) dan lokasi latihan.
    | Tampil otomatis di halaman /contact.
    |
    */

    Route::get('/admin/contact', [AdminContactController::class, 'index'])
        ->name('admin.contact.index');

    Route::put('/admin/contact', [AdminContactController::class, 'update'])
        ->name('admin.contact.update');

    Route::resource('/admin/contact/locations', AdminContactLocationController::class)
        ->names('admin.contact.locations')
        ->except(['index', 'show']);

    /*
    |--------------------------------------------------------------------------
    | Admin Customer
    |--------------------------------------------------------------------------
    |
    | Daftar member & customer offline, beserta riwayat booking.
    |
    */

    Route::get('/admin/customers', [AdminCustomerController::class, 'index'])
        ->name('admin.customers.index');

    Route::get('/admin/customers/offline', [AdminCustomerController::class, 'offline'])
        ->name('admin.customers.offline');

    Route::get('/admin/customers/{user}', [AdminCustomerController::class, 'show'])
        ->whereNumber('user')
        ->name('admin.customers.show');

    Route::get('/admin/customers/create', [AdminCustomerController::class, 'create'])
        ->name('admin.customers.create');

    Route::post('/admin/customers', [AdminCustomerController::class, 'store'])
        ->name('admin.customers.store');

    Route::post('/admin/customers/{user}/reset-password', [AdminCustomerController::class, 'resetPassword'])
        ->whereNumber('user')
        ->name('admin.customers.reset-password');

    Route::post('/admin/customers/{user}/verify-email', [AdminCustomerController::class, 'verifyEmail'])
        ->whereNumber('user')
        ->name('admin.customers.verify-email');

    Route::delete('/admin/customers/{user}', [AdminCustomerController::class, 'destroy'])
        ->whereNumber('user')
        ->name('admin.customers.destroy');

    /*
    |--------------------------------------------------------------------------
    | Admin Kelola Jadwal & Bukti Pembayaran
    |--------------------------------------------------------------------------
    */

    Route::resource('/admin/schedule-blocks', AdminScheduleBlockController::class)
        ->only(['index', 'store', 'destroy'])
        ->names('admin.schedule-blocks');

    Route::get('/admin/payments/{payment}/receipt', [AdminPaymentController::class, 'receipt'])
        ->name('admin.payments.receipt');

    Route::patch('/admin/payments/{payment}/verify', [AdminPaymentController::class, 'verify'])
        ->name('admin.payments.verify');

    Route::patch('/admin/payments/{payment}/reject', [AdminPaymentController::class, 'rejectVerification'])
        ->name('admin.payments.reject');

    Route::patch('/admin/payments/{payment}/cash', [AdminPaymentController::class, 'markCash'])
        ->name('admin.payments.cash');

    Route::get('/admin/payments/{payment}/proof', [AdminPaymentController::class, 'proof'])
        ->name('admin.payments.proof');


    /*
    |--------------------------------------------------------------------------
    | Admin Booking Actions
    |--------------------------------------------------------------------------
    |
    | Pending booking:
    | - Approve -> booked
    | - Reject  -> rejected
    |
    */

    Route::patch(
        '/admin/bookings/{booking}/approve',
        [AdminDashboardController::class, 'approve']
    )->name('admin.bookings.approve');

    Route::patch(
        '/admin/bookings/{booking}/reject',
        [AdminDashboardController::class, 'reject']
    )->name('admin.bookings.reject');

    // Admin reschedule booking customer
    Route::get('/admin/bookings/{booking}/reschedule', [AdminBookingRescheduleController::class, 'edit'])
        ->name('admin.bookings.reschedule');

    Route::put('/admin/bookings/{booking}/reschedule', [AdminBookingRescheduleController::class, 'update'])
        ->name('admin.bookings.reschedule.update');

    /*
    |--------------------------------------------------------------------------
    | Admin Settings
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/settings', [AdminSettingController::class, 'index'])
        ->name('admin.settings.index');

    Route::put('/admin/settings/general', [AdminSettingController::class, 'updateGeneral'])
        ->name('admin.settings.general');

    Route::put('/admin/settings/appearance', [AdminSettingController::class, 'updateAppearance'])
        ->name('admin.settings.appearance');

    Route::put('/admin/settings/profile', [AdminSettingController::class, 'updateProfile'])
        ->name('admin.settings.profile');

    Route::put('/admin/settings/password', [AdminSettingController::class, 'updatePassword'])
        ->name('admin.settings.password');

    Route::put('/admin/settings/booking', [AdminSettingController::class, 'updateBooking'])
        ->name('admin.settings.booking');

    Route::put('/admin/settings/payment', [AdminSettingController::class, 'updatePayment'])
        ->name('admin.settings.payment');

    Route::put('/admin/settings/whatsapp', [AdminSettingController::class, 'updateWhatsapp'])
        ->name('admin.settings.whatsapp');

    Route::post('/admin/settings/whatsapp/test', [AdminSettingController::class, 'testWhatsapp'])
        ->name('admin.settings.whatsapp.test');

    Route::put('/admin/settings/security', [AdminSettingController::class, 'updateSecurity'])
        ->name('admin.settings.security');

    Route::delete('/admin/settings/sessions', [AdminSecurityController::class, 'destroyOtherSessions'])
        ->name('admin.settings.sessions.others');

    Route::delete('/admin/settings/sessions/{session}', [AdminSecurityController::class, 'destroySession'])
        ->name('admin.settings.sessions.destroy');

    Route::post('/admin/settings/backups', [AdminSecurityController::class, 'createBackup'])
        ->name('admin.settings.backups.store');

    Route::get('/admin/settings/backups/{file}', [AdminSecurityController::class, 'downloadBackup'])
        ->name('admin.settings.backups.download');

    Route::delete('/admin/settings/backups/{file}', [AdminSecurityController::class, 'deleteBackup'])
        ->name('admin.settings.backups.destroy');


    /*
    |--------------------------------------------------------------------------
    | Admin Create Offline Booking
    |--------------------------------------------------------------------------
    |
    | Admin dapat membuat booking untuk:
    |
    | 1. Member terdaftar
    |    - user_id terisi
    |    - Booking masuk ke akun member
    |
    | 2. Customer offline
    |    - user_id = NULL
    |    - Tidak membuat akun baru
    |    - Data customer disimpan di booking
    |
    | Semua booking yang dibuat Admin:
    | - status = booked
    | - source = offline
    | - Tetap mengecek bentrok jadwal
    |
    */

    Route::get(
        '/admin/offline-booking/create',
        [AdminOfflineBookingController::class, 'create']
    )->name('admin.offline-booking.create');

    Route::post(
        '/admin/offline-booking',
        [AdminOfflineBookingController::class, 'store']
    )->name('admin.offline-booking.store');
});


/*
|--------------------------------------------------------------------------
| Notifications (admin & customer)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])
        ->name('notifications.open');

    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('notifications.read-all');

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::get('/notifications/{notification}', [NotificationController::class, 'show'])
        ->name('notifications.show');

    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])
        ->name('notifications.destroy');
});


/*
|--------------------------------------------------------------------------
| Settings
|--------------------------------------------------------------------------
*/

require __DIR__ . '/settings.php';
/*
| Chat member & Kelola Coach
*/
require __DIR__ . '/chat-coach.php';
