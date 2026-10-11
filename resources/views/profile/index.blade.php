@extends('notifications.customer-layout')

@section('title', 'Profil saya')
@section('no_footer', true)

@section('content')
<style>
    html { scroll-padding-bottom: 110px; }
    .pf { display: grid; gap: 16px; max-width: 760px; margin: 0 auto; }
    .pf h1 { font-family: var(--fw-serif); font-size: clamp(26px, 4vw, 36px); font-weight: 600; letter-spacing: -.4px; }
    .pf h1 span { font-style: italic; color: var(--fw-green-3); }
    .pf-sub { margin-top: 4px; color: var(--fw-muted); font-size: 14px; }
    .pf-alert { padding: 12px 16px; border-radius: 14px; font-size: 14px; font-weight: 500; }
    .pf-alert.ok { background: var(--fw-tint); border: 1px solid rgba(var(--d-green-rgb, 31, 77, 51), .2); color: var(--d-ink-green, var(--fw-green)); }
    .pf-alert.err { background: var(--fw-red-tint); border: 1px solid rgba(201, 65, 58, .3); color: var(--d-red-ink, #8f2a24); }
    .pf-card { padding: 20px; border: 1px solid var(--fw-line); border-radius: 20px; background: var(--fw-surface); box-shadow: var(--fw-shadow); }
    .pf-hero { display: flex; align-items: center; gap: 14px; }
    .pf-av { flex: 0 0 68px; height: 68px; display: grid; place-items: center; border-radius: 50%; background: var(--fw-green); color: #fff; font-size: 24px; font-weight: 700; }
    .pf-who { min-width: 0; }
    .pf-who strong { display: block; font-size: 19px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pf-who span { display: block; color: var(--fw-muted); font-size: 13.5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pf-tag { display: inline-flex !important; align-items: center; gap: 6px; margin-top: 6px; padding: 3px 10px; border-radius: 99px; background: var(--fw-tint); color: var(--d-ink-green, var(--fw-green)) !important; font-size: 11.5px !important; font-weight: 600; }
    .pf-tag.warn { background: var(--fw-orange-tint); color: var(--d-orange-ink, #a2650c) !important; }
    .pf-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 16px; }
    .pf-stats div { padding: 12px; border-radius: 14px; background: var(--fw-surface-2); text-align: center; }
    .pf-stats strong { display: block; font-family: var(--fw-serif); font-size: 24px; font-weight: 600; color: var(--d-ink-green, var(--fw-green)); }
    .pf-stats span { color: var(--fw-muted); font-size: 12px; }
    .pf-links { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 12px; }
    .pf-links a { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 12px 6px; border-radius: 14px; border: 1px solid var(--fw-line); color: var(--fw-text-2); font-size: 12.5px; font-weight: 600; text-align: center; }
    .pf-links a:hover { border-color: rgba(var(--d-green-rgb, 31, 77, 51), .35); color: var(--d-ink-green, var(--fw-green)); }
    .pf-links i { font-style: normal; width: 38px; height: 38px; display: grid; place-items: center; border-radius: 50%; background: var(--fw-tint); color: var(--d-ink-green, var(--fw-green)); }
    .pf-links i svg { width: 19px; height: 19px; }
    .pf-card h2 { font-size: 16px; font-weight: 700; }
    .pf-card h2 + p { margin: 3px 0 14px; color: var(--fw-muted); font-size: 13px; }
    .pf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .pf-field { display: grid; gap: 6px; }
    .pf-field label { color: var(--fw-text-2); font-size: 13px; font-weight: 600; }
    .pf-field input { width: 100%; height: 48px; padding: 0 16px; border: 1px solid var(--fw-line-2); border-radius: 14px; background: var(--fw-surface); color: var(--fw-text); font-size: 16px; outline: none; }
    .pf-field input:focus { border-color: var(--fw-green); box-shadow: 0 0 0 4px rgba(var(--d-green-rgb, 31, 77, 51), .1); }
    .pf-field small { color: var(--fw-red); font-size: 12px; }
    .pf-actions { display: flex; justify-content: flex-end; margin-top: 14px; }
    .pf-btn { height: 46px; padding: 0 22px; border: 0; border-radius: 99px; background: var(--fw-green); color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; }
    .pf-btn:hover { background: var(--fw-green-2); }
    .pf-out { width: 100%; height: 48px; border: 1px solid rgba(201, 65, 58, .35); border-radius: 99px; background: var(--fw-surface); color: var(--fw-red); font-size: 14px; font-weight: 600; cursor: pointer; }
    .pf-out:hover { background: var(--fw-red-tint); }
    @media (max-width: 600px) {
        .pf-card { padding: 16px; }
        .pf-grid { grid-template-columns: 1fr; }
        .pf-btn { width: 100%; }
    }
</style>

@php
    $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: 'U';
@endphp

<div class="pf">
    <div>
        <div class="fw-pagehead-title"><a href="{{ Route::has('menu') ? route('menu') : route('home') }}" class="fw-back" aria-label="Kembali">{!! \App\Support\Icons::svg('back') !!}</a><h1>Profil <span>saya</span></h1></div>
        <p class="pf-sub">Kelola data akun dan password Anda.</p>
    </div>

    @if (session('success'))
        <div class="pf-alert ok">✓ {{ session('success') }}</div>
    @endif

    <section class="pf-card">
        <div class="pf-hero">
            <div class="pf-av">{{ $initials }}</div>
            <div class="pf-who">
                <strong>{{ $user->name }}</strong>
                <span>{{ $user->email }}</span>
                @if ($user->email_verified_at)
                    <span class="pf-tag">✓ Email terverifikasi</span>
                @else
                    <span class="pf-tag warn">! Email belum diverifikasi</span>
                @endif
            </div>
        </div>

        <div class="pf-stats">
            <div><strong>{{ $stats['total'] }}</strong><span>Total booking</span></div>
            <div><strong>{{ $stats['upcoming'] }}</strong><span>Akan datang</span></div>
            <div><strong>{{ $stats['done'] }}</strong><span>Selesai</span></div>
        </div>

        <div class="pf-links">
            @if (Route::has('booking'))<a href="{{ route('booking') }}"><i>{!! \App\Support\Icons::svg('calendar') !!}</i>Booking</a>@endif
            @if (Route::has('chat'))<a href="{{ route('chat') }}"><i>{!! \App\Support\Icons::svg('chat') !!}</i>Chat coach</a>@endif
            @if (Route::has('notifications.index'))<a href="{{ route('notifications.index') }}"><i>{!! \App\Support\Icons::svg('bell') !!}</i>Notifikasi</a>@endif
        </div>
    </section>

    <section class="pf-card">
        <h2>Data akun</h2>
        <p>Nama dan email yang dipakai untuk booking dan login.</p>

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')
            <div class="pf-grid">
                <div class="pf-field">
                    <label for="pfName">Nama lengkap</label>
                    <input id="pfName" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name">
                    @error('name')<small>{{ $message }}</small>@enderror
                </div>
                <div class="pf-field">
                    <label for="pfEmail">Email</label>
                    <input id="pfEmail" type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
                    @error('email')<small>{{ $message }}</small>@enderror
                </div>
            </div>
            <div class="pf-actions"><button class="pf-btn" type="submit">Simpan profil</button></div>
        </form>
    </section>

    <section class="pf-card" id="password">
        <h2>Ganti password</h2>
        <p>Minimal 8 karakter, berisi huruf dan angka. Perangkat lain akan otomatis keluar.</p>

        <form method="POST" action="{{ route('profile.password') }}">
            @csrf
            @method('PUT')
            <div class="pf-grid">
                <div class="pf-field" style="grid-column: 1 / -1;">
                    <label for="pfCur">Password saat ini</label>
                    <input id="pfCur" type="password" name="current_password" required autocomplete="current-password">
                    @error('current_password', 'password')<small>{{ $message }}</small>@enderror
                </div>
                <div class="pf-field">
                    <label for="pfNew">Password baru</label>
                    <input id="pfNew" type="password" name="password" required autocomplete="new-password">
                    @error('password', 'password')<small>{{ $message }}</small>@enderror
                </div>
                <div class="pf-field">
                    <label for="pfNew2">Ulangi password baru</label>
                    <input id="pfNew2" type="password" name="password_confirmation" required autocomplete="new-password">
                </div>
            </div>
            <div class="pf-actions"><button class="pf-btn" type="submit">Ganti password</button></div>
        </form>
    </section>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="pf-out" type="submit">Keluar dari akun</button>
    </form>
</div>
@endsection
