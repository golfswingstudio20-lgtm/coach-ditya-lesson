@extends('layouts.fw-auth')

@section('title', 'Masuk')

@section('content')
    <h1>Selamat datang</h1>
    <p class="fw-sub">Masuk untuk booking lesson & melihat jadwal Anda.</p>

    @if (session('status'))<div class="fw-alert ok">{{ session('status') }}</div>@endif
    @if (session('error'))<div class="fw-alert err">{{ session('error') }}</div>@endif
    @if ($errors->any())
        <div class="fw-alert err"><div>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div></div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="fw-field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="email" required autofocus>
        </div>
        <div class="fw-field">
            <label for="password">Password</label>
            <div class="fa-pw">
                <input id="password" type="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
                <button type="button" data-toggle-pw aria-label="Lihat password">{!! \App\Support\Icons::svg('eye') !!}</button>
            </div>
        </div>
        <div class="fa-row">
            <label><input type="checkbox" name="remember" value="1"> Ingat saya</label>
            @if (Route::has('password.request'))<a href="{{ route('password.request') }}" class="fw-link">Lupa password?</a>@endif
        </div>
        <button type="submit" class="fw-btn block dark">LOGIN</button>
    </form>

    @if (Route::has('register'))
        <p class="fa-alt">Belum punya akun?</p>
        <a href="{{ route('register') }}" class="fw-btn block ghost" style="margin-top: 10px">DAFTAR</a>
    @endif
@endsection
