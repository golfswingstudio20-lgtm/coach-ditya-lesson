@extends('layouts.fw-auth')

@section('title', 'Password Baru')

@section('content')
    <h1>Buat password baru</h1>
    <p class="fw-sub">Minimal 8 karakter, berisi huruf dan angka, yang mudah Anda ingat.</p>

    @if ($errors->any())
        <div class="fw-alert err"><div>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div></div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="fw-field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" autocomplete="email" required>
        </div>
        <div class="fw-field">
            <label for="password">Password baru</label>
            <div class="fa-pw">
                <input id="password" type="password" name="password" autocomplete="new-password" required autofocus>
                <button type="button" data-toggle-pw aria-label="Lihat password">{!! \App\Support\Icons::svg('eye') !!}</button>
            </div>
        </div>
        <div class="fw-field">
            <label for="password_confirmation">Ulangi password baru</label>
            <div class="fa-pw">
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
                <button type="button" data-toggle-pw aria-label="Lihat password">{!! \App\Support\Icons::svg('eye') !!}</button>
            </div>
        </div>
        <button type="submit" class="fw-btn block">Simpan password</button>
    </form>

    <p class="fa-alt"><a href="{{ route('login') }}" class="fw-link">Kembali masuk</a></p>
@endsection
