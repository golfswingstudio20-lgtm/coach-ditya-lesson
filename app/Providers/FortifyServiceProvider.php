<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Custom Login Response
        |--------------------------------------------------------------------------
        */

        $this->app->instance(
            LoginResponseContract::class,
            new LoginResponse()
        );

        /*
        |--------------------------------------------------------------------------
        | Custom Logout Response
        |--------------------------------------------------------------------------
        */

        $this->app->instance(
            LogoutResponseContract::class,
            new LogoutResponse()
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Login
        |--------------------------------------------------------------------------
        */

        Fortify::loginView(function () {
            return view('auth.login');
        });

        /*
        |--------------------------------------------------------------------------
        | Register
        |--------------------------------------------------------------------------
        */

        Fortify::registerView(function () {
            return view('auth.register');
        });

        /*
        |--------------------------------------------------------------------------
        | Forgot Password
        |--------------------------------------------------------------------------
        */

        Fortify::requestPasswordResetLinkView(function () {
            return view('auth.forgot-password');
        });

        /*
        |--------------------------------------------------------------------------
        | Reset Password
        |--------------------------------------------------------------------------
        */

        Fortify::resetPasswordView(function ($request) {
            return view('auth.reset-password', [
                'request' => $request,
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Create New User
        |--------------------------------------------------------------------------
        */

        Fortify::createUsersUsing(CreateNewUser::class);

        // Simpan password baru dari link "Lupa password" (tanpa ini muncul error 500)
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Isi email dalam Bahasa Indonesia
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url(route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()], false));

            return (new MailMessage)
                ->subject('Buat password baru - ' . \App\Support\Brand::name())
                ->greeting('Halo ' . $notifiable->name . ',')
                ->line('Kami menerima permintaan untuk membuat password baru akun Anda.')
                ->action('Buat password baru', $url)
                ->line('Link ini berlaku ' . config('auth.passwords.users.expire', 60) . ' menit.')
                ->line('Jika Anda tidak meminta ini, abaikan saja email ini. Password Anda tidak berubah.')
                ->salutation('Salam, ' . \App\Support\Brand::name());
        });

        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject('Verifikasi email - ' . \App\Support\Brand::name())
                ->greeting('Halo ' . $notifiable->name . ',')
                ->line('Terima kasih sudah mendaftar. Klik tombol di bawah untuk memverifikasi email Anda, lalu Anda bisa langsung booking lesson.')
                ->action('Verifikasi email', $url)
                ->line('Jika Anda tidak mendaftar, abaikan email ini.')
                ->salutation('Salam, ' . \App\Support\Brand::name());
        });

        /*
        |--------------------------------------------------------------------------
        | Email Verification
        |--------------------------------------------------------------------------
        */

        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });
    }
}