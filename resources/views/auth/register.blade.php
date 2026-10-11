@extends('layouts.fw-auth')

@section('title', 'Daftar')

@section('content')
    <h1>Buat akun</h1>
    <p class="fw-sub">Daftar gratis, lalu booking lesson golf pertama Anda.</p>

    @if (session('error'))<div class="fw-alert err">{{ session('error') }}</div>@endif
    @if ($errors->any())
        <div class="fw-alert err"><div>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div></div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="fw-field">
            <label for="name">Nama lengkap</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Nama Anda" autocomplete="name" required autofocus>
        </div>
        <div class="fw-field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="email" required>
        </div>
        <div class="fw-field">
            <label for="password">Password</label>
            <div class="fa-pw">
                <input id="password" type="password" name="password" placeholder="Min. 8 karakter, huruf & angka" autocomplete="new-password" required>
                <button type="button" data-toggle-pw aria-label="Lihat password">{!! \App\Support\Icons::svg('eye') !!}</button>
            </div>
        </div>
        <div class="fw-field">
            <label for="password_confirmation">Ulangi password</label>
            <div class="fa-pw">
                <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Ketik ulang password" autocomplete="new-password" required>
                <button type="button" data-toggle-pw aria-label="Lihat password">{!! \App\Support\Icons::svg('eye') !!}</button>
            </div>
        </div>
        <button type="submit" class="fw-btn block">Daftar {!! \App\Support\Icons::svg('arrow') !!}</button>
    </form>

    <p class="fa-alt">Sudah punya akun? <a href="{{ route('login') }}" class="fw-link">Masuk</a></p>
@endsection
