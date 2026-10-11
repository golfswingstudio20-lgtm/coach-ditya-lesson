<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Halaman "Profil saya" untuk customer (tanpa Livewire/Vite, rapi di HP & laptop).
 * Admin diarahkan ke Settings admin.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return redirect()->route('admin.settings.index', ['tab' => 'account']);
        }

        $bookings = Booking::where('user_id', $user->id);

        $stats = [
            'total'    => (clone $bookings)->count(),
            'upcoming' => (clone $bookings)->whereIn('status', ['pending', 'booked'])
                ->whereDate('booking_date', '>=', now()->toDateString())->count(),
            'done'     => (clone $bookings)->where('status', 'booked')
                ->whereDate('booking_date', '<', now()->toDateString())->count(),
        ];

        return view('profile.index', compact('user', 'stats'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'name.required'  => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
            'email.unique'   => 'Email sudah dipakai akun lain.',
        ]);

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($user->email_verified_at === null && method_exists($user, 'sendEmailVerificationNotification')) {
            try { $user->sendEmailVerificationNotification(); } catch (\Throwable $e) { report($e); }
        }

        return redirect()->route('profile.edit')->with('success', 'Profil berhasil disimpan.');
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.required'         => 'Masukkan password saat ini.',
            'current_password.current_password' => 'Password saat ini salah.',
            'password.required'                 => 'Password baru wajib diisi.',
            'password.confirmed'                => 'Ulangi password baru tidak sama.',
            'password.min'                      => 'Password baru minimal 8 karakter.',
        ]);

        $request->user()->forceFill(['password' => $request->input('password')])->save();

        Auth::logoutOtherDevices($request->input('password'));
        $request->session()->regenerate();

        return redirect()->route('profile.edit')->with('success', 'Password berhasil diganti.');
    }
}
