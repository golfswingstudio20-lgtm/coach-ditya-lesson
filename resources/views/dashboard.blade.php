{{-- Beranda customer (setelah login) - tema Biru Langit. --}}
@extends('layouts.fw')

@section('title', 'Beranda')
@section('no_footer', true)
@section('main_class', 'narrow')

@php
    use App\Support\Icons;
    use Carbon\Carbon;

    $user  = auth()->user();
    $today = now()->toDateString();
    $mine  = \App\Models\Booking::where('user_id', $user->id);

    $next = (clone $mine)->whereIn('status', ['pending', 'booked'])
        ->whereDate('booking_date', '>=', $today)
        ->orderBy('booking_date')->orderBy('start_time')->first();

    $pendingIds = (clone $mine)->where('status', 'pending')->whereDate('booking_date', '>=', $today)->pluck('id');
    $paidIds    = $pendingIds->isEmpty() ? collect() : \App\Models\Payment::whereIn('booking_id', $pendingIds)->where('status', 'paid')->pluck('booking_id');
    $unpaid     = $pendingIds->diff($paidIds)->count();

    $nextAmount = null;
    if ($next) {
        $nextAmount = \App\Models\Payment::where('booking_id', $next->id)->value('amount');
        if (! $nextAmount) {
            $mins = abs(Carbon::parse($next->start_time)->diffInMinutes(Carbon::parse($next->end_time)));
            $nextAmount = method_exists($next, 'isCourse') && $next->isCourse()
                ? \App\Support\BookingRules::coursePrice()
                : (int) round(max(60, $mins) / 60 * \App\Models\Payment::pricePerHour());
        }
    }

    $coach      = \App\Models\Coach::main();
    $firstName  = \Illuminate\Support\Str::of($user->name)->before(' ')->limit(18, '');
    $unread     = $user->unreadNotifications()->count();
    $hasSwing   = Route::has('swing.index');
    $lastSwing  = $hasSwing && class_exists(\App\Models\SwingAnalysis::class)
        ? rescue(fn () => \App\Models\SwingAnalysis::where('user_id', $user->id)->latest()->first(), null, false)
        : null;
@endphp

@push('head')
<style>
    .hm { display: grid; gap: 14px; }
    .hm-top { display: flex; align-items: center; gap: 10px; }
    .hm-logo { width: 52px; height: 52px; flex: 0 0 52px; display: grid; place-items: center; border-radius: 50%; border: 2.5px solid #14141A; background: #FFFFFF; overflow: hidden; color: #17803A; }
    .hm-logo img { width: 100%; height: 100%; object-fit: cover; }
    .hm-logo svg { width: 26px; height: 26px; }
    .hm-brand { flex: 1; font-size: 26px; line-height: .95; }
    .hm-bell { position: relative; width: 46px; height: 46px; display: grid; place-items: center; border-radius: 14px; border: 2.5px solid #14141A; background: #DDF3E4; color: #14141A; box-shadow: 2px 2px 0 #14141A; }
    .hm-bell svg { width: 22px; height: 22px; }
    .hm-bell b { position: absolute; top: -7px; right: -7px; min-width: 20px; height: 20px; padding: 0 5px; display: grid; place-items: center; border-radius: 99px; border: 2px solid #14141A; background: #E8453C; color: #fff; font-size: 11px; }

    .hm-hello { display: flex; align-items: center; gap: 12px; padding: 16px; }
    .hm-hello h1 { font-family: "Lilita One", system-ui, sans-serif; font-weight: 400; font-size: 25px; letter-spacing: .01em; }
    .hm-hello p { margin-top: 6px; font-size: 14.5px; font-weight: 500; line-height: 1.4; color: #2F3A34; }
    .hm-hello .pic { width: 88px; height: 96px; flex: 0 0 88px; border-radius: 16px; border: 2.5px solid #14141A; background: #FFFFFF center / cover no-repeat; display: grid; place-items: center; color: #17803A; }
    .hm-hello .pic svg { width: 40px; height: 40px; }

    .hm-big { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .hm-small { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }

    .hm-alert { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 2.5px solid #14141A; border-radius: 16px; background: #FFE9B8; color: #14141A; font-size: 14px; font-weight: 500; }
    .hm-alert svg { width: 22px; height: 22px; flex: 0 0 22px; }

    .hm-lesson { padding: 14px; display: grid; gap: 10px; }
    .hm-lesson h2 { font-family: "Lilita One", system-ui, sans-serif; font-weight: 400; font-size: 20px; letter-spacing: .01em; }
    .hm-lesson .row { display: flex; gap: 12px; align-items: center; padding: 10px; border: 2px solid rgba(20, 20, 26, .2); border-radius: 16px; background: #EAF8EF; }
    .hm-lesson .ph { width: 72px; height: 72px; flex: 0 0 72px; border-radius: 12px; border: 2px solid #14141A; background: #FFFFFF center / cover no-repeat; display: grid; place-items: center; color: #17803A; }
    .hm-lesson .ph svg { width: 30px; height: 30px; }
    .hm-lesson strong { display: block; font-family: "Lilita One", system-ui, sans-serif; font-weight: 400; font-size: 17px; letter-spacing: .01em; }
    .hm-lesson small { display: block; margin-top: 2px; color: #2F3A34; font-size: 13px; }
    .hm-lesson .cdl-price { display: block; margin-top: 3px; font-size: 18px; }
    .hm-lesson .foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .hm-lesson .more { display: inline-flex; align-items: center; gap: 4px; color: #17803A; font-size: 14px; font-weight: 700; }
    .hm-lesson .more svg { width: 16px; height: 16px; }

    .hm-swing { display: flex; align-items: center; gap: 12px; padding: 14px; }
    .hm-swing .ring { width: 58px; height: 58px; flex: 0 0 58px; display: grid; place-items: center; border-radius: 50%; border: 5px solid #22A04B; background: #FFFFFF; font-family: "Lilita One", system-ui, sans-serif; font-size: 20px; }
    .hm-swing strong { display: block; font-size: 15px; }
    .hm-swing small { color: #2F3A34; font-size: 13px; }

    @media (min-width: 821px) { .fw-main.narrow { width: min(560px, 100% - 48px); } }
</style>
@endpush

@section('content')
<div class="hm">
    <div class="hm-top">
        <span class="hm-logo">
            @if ($logo = \App\Support\Brand::logoUrl())<img src="{{ $logo }}" alt="">@else{!! Icons::svg('golf') !!}@endif
        </span>
        <span class="hm-brand cdl-title">COACH DITYA<br>LESSON</span>
        <a href="{{ Route::has('notifications.index') ? route('notifications.index') : route('menu') }}" class="hm-bell" aria-label="Notifikasi">
            {!! Icons::svg('bell') !!}@if ($unread)<b>{{ $unread > 9 ? '9+' : $unread }}</b>@endif
        </a>
    </div>

    @if (session('status') || session('success'))
        <div class="fw-alert ok">{{ session('status') ?? session('success') }}</div>
    @endif

    <section class="hm-hello cdl-ink-card">
        <div style="flex:1;min-width:0">
            <h1>Halo, {{ $firstName }}!</h1>
            <p>Saatnya tingkatkan permainan golf kamu bersama Coach Ditya.</p>
        </div>
        @if ($coach && $coach->photo_url)
            <span class="pic" style="background-image:url('{{ $coach->photo_url }}')" role="img" aria-label="Foto {{ $coach->name }}"></span>
        @else
            <span class="pic" aria-hidden="true">{!! Icons::svg('golf') !!}</span>
        @endif
    </section>

    <div class="hm-big">
        <a href="{{ route('booking') }}" class="cdl-tile big">{!! Icons::svg('calendar') !!}Booking Lesson</a>
        <a href="{{ $hasSwing ? route('swing.index') : route('booking') }}" class="cdl-tile big green">{!! Icons::svg('video') !!}AI Swing Analysis</a>
    </div>

    <div class="hm-small">
        <a href="{{ route('program') }}" class="cdl-tile small">{!! Icons::svg('star') !!}Program<br>Latihan</a>
        <a href="{{ $hasSwing ? route('swing.history') : route('jadwal') }}" class="cdl-tile small">{!! Icons::svg('search') !!}Riwayat<br>Analisis</a>
        <a href="{{ Route::has('menu') ? route('menu') : route('profile.edit') }}" class="cdl-tile small">{!! Icons::svg('user') !!}Profil<br>Saya</a>
    </div>

    @if ($unpaid > 0)
        <a href="{{ Route::has('jadwal') ? route('jadwal') : route('booking') }}" class="hm-alert">
            {!! Icons::svg('wallet') !!}
            <span style="flex:1">Ada <b>{{ $unpaid }}</b> booking menunggu pembayaran. Selesaikan sebelum batas waktu.</span>
            {!! Icons::svg('chev', 'fw-chev') !!}
        </a>
    @endif

    <section class="hm-lesson cdl-ink-card">
        <h2>Lesson Terdekat</h2>
        @if ($next)
            @php $nDate = Carbon::parse($next->booking_date); @endphp
            <a href="{{ $next->status === 'pending' && Route::has('payment.booking') ? route('payment.booking', $next) : (Route::has('jadwal') ? route('jadwal') : route('booking')) }}" class="row">
                <span class="ph" @if (\App\Support\Brand::background('public')) style="background-image:url('{{ \App\Support\Brand::background('public') }}')" @endif>@unless (\App\Support\Brand::background('public')){!! Icons::svg('golf') !!}@endunless</span>
                <span style="flex:1;min-width:0">
                    <strong>{{ $next->lesson_label }}</strong>
                    <small>{{ $coach->name ?? 'Coach Ditya' }} · {{ $nDate->locale('id')->translatedFormat('D, d M') }} · {{ substr($next->start_time, 0, 5) }}</small>
                    <span class="cdl-price">{{ \App\Models\Payment::formatRupiah((int) $nextAmount) }}</span>
                </span>
                <span class="fw-badge {{ $next->status === 'booked' ? 'green' : 'orange-soft' }}">{{ $next->status === 'booked' ? 'Disetujui' : 'Menunggu' }}</span>
            </a>
        @else
            <div class="row">
                <span class="ph">{!! Icons::svg('golf') !!}</span>
                <span style="flex:1;min-width:0">
                    <strong>Belum ada lesson</strong>
                    <small>Pilih jadwal latihan bersama Coach Ditya.</small>
                    <span class="cdl-price">{{ \App\Models\Payment::formatRupiah(\App\Models\Payment::pricePerHour()) }}/jam</span>
                </span>
            </div>
        @endif
        <div class="foot">
            @unless ($next)<a href="{{ route('booking') }}" class="fw-btn sm">Booking Sekarang</a>@else<span></span>@endunless
            <a href="{{ Route::has('jadwal') ? route('jadwal') : route('booking') }}" class="more">Lihat Semua {!! Icons::svg('chev') !!}</a>
        </div>
    </section>

    @if ($hasSwing)
        <a href="{{ $lastSwing ? route('swing.show', $lastSwing) : route('swing.index') }}" class="hm-swing cdl-ink-card">
            <span class="ring">{{ $lastSwing ? $lastSwing->score : '?' }}</span>
            <span style="flex:1;min-width:0">
                <strong>{{ $lastSwing ? 'Skor swing terakhir' : 'Coba AI Swing Analysis' }}</strong>
                <small>{{ $lastSwing ? $lastSwing->created_at->locale('id')->translatedFormat('d M Y') . ' · ' . $lastSwing->grade_label : 'Upload video swing, dapatkan skor & saran latihan.' }}</small>
            </span>
            {!! Icons::svg('chev', 'fw-chev') !!}
        </a>
    @endif
</div>
@endsection
