{{-- Beranda customer (setelah login). --}}
@extends('layouts.fw')

@section('title', 'Beranda')
@section('no_footer', true)

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

    $stats = [
        ['Total booking', (clone $mine)->count(), 'calendar', ''],
        ['Akan datang', (clone $mine)->whereIn('status', ['pending', 'booked'])->whereDate('booking_date', '>=', $today)->count(), 'clock', 'orange'],
        ['Lesson selesai', (clone $mine)->where('status', 'booked')->whereDate('booking_date', '<', $today)->count(), 'check-c', 'blue'],
        ['Belum dibayar', $unpaid, 'wallet', 'red'],
    ];
    $price = \App\Models\Payment::pricePerHour();

    $coach   = \App\Models\Coach::main();
    $photo   = \App\Support\Brand::background('public');
    $hour    = (int) now()->format('H');
    $greet   = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
    $chatNew = class_exists(\App\Models\ChatMessage::class) ? \App\Models\ChatMessage::unreadForMember($user->id) : 0;
    $events  = \App\Models\Event::active()->upcoming()->chronological()->take(1)->get();
    $programs = \App\Models\Program::active()->ordered()->take(4)->get();
@endphp

@push('head')
<style>
    .bd-hello { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
    .bd-hello small { color: var(--fw-muted); font-size: 14px; }
    .bd-hello h1 { margin-top: 2px; font-family: var(--fw-serif); font-size: clamp(26px, 3.4vw, 36px); font-weight: 600; letter-spacing: -.4px; }
    .bd-hello p { margin-top: 4px; color: var(--fw-muted); font-size: 14px; }

    .bd-top { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); gap: 18px; margin-top: 22px; }
    .bd-hero.fw-pc { min-height: 220px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; gap: 14px; }
    .bd-hero .row { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
    .bd-hero h2 { font-family: var(--fw-serif); font-size: clamp(24px, 3vw, 30px); font-weight: 700; line-height: 1.15; color: #fff; }
    .bd-hero .fw-pc-chips { margin-top: 10px; }
    .bd-hero .fw-pc-btn { align-self: flex-start; }

    .bd-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }

    .bd-quick { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 10px; }
    .bd-quick a { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 14px 6px; border-radius: 18px; background: var(--fw-surface); border: 1px solid var(--fw-line); box-shadow: var(--fw-shadow); color: var(--fw-text-2); font-size: 12.5px; font-weight: 500; text-align: center; }
    .bd-quick a:hover { border-color: rgba(var(--d-green-rgb, 31, 77, 51), .35); color: var(--d-ink-green, var(--fw-green)); }
    .bd-quick .ic { position: relative; width: 46px; height: 46px; display: grid; place-items: center; border-radius: 50%; background: var(--fw-tint); color: var(--d-ink-green, var(--fw-green)); }
    .bd-quick .ic svg { width: 21px; height: 21px; }
    .bd-quick .ic b { position: absolute; top: -3px; right: -3px; min-width: 18px; height: 18px; padding: 0 4px; display: grid; place-items: center; border-radius: 99px; background: var(--fw-red); color: #fff; font-size: 10px; box-shadow: 0 0 0 2px var(--d-surface, #fff); }

    .bd-cols { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); gap: 18px; }
    .bd-coach { display: flex; gap: 16px; align-items: center; padding: 18px; }
    .bd-coach .fw-av { width: 72px; height: 72px; flex-basis: 72px; border-radius: 20px; font-size: 24px; }
    .bd-coach strong { display: block; font-size: 16px; }
    .bd-coach small { color: var(--fw-muted); font-size: 13px; }
    .bd-coach p { margin-top: 6px; color: var(--fw-text-2); font-size: 13.5px; line-height: 1.55; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    .bd-prog { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .bd-prog .fw-pc { height: 230px; border-radius: 22px; }
    .bd-prog .fw-pc h3 { font-size: 18px; }

    .bd-ev { display: flex; gap: 12px; padding: 10px; }
    .bd-ev-img { position: relative; flex: 0 0 96px; height: 116px; border-radius: 14px; overflow: hidden; background: linear-gradient(135deg, #1f4d33, #0c1f15); }
    .bd-ev-img img { width: 100%; height: 100%; object-fit: cover; object-position: top; }
    .bd-ev > div:last-child { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 5px; padding: 3px 2px; }
    .bd-ev strong { font-size: 15.5px; }
    .bd-ev small { color: var(--fw-muted); font-size: 12.5px; }
    .bd-ev .foot { margin-top: auto; display: flex; justify-content: space-between; align-items: center; font-size: 13px; }
    .bd-ev .foot b { color: var(--d-ink-green, var(--fw-green)); font-weight: 600; }

    @media (max-width: 960px) { .bd-top, .bd-cols { grid-template-columns: minmax(0, 1fr); } }
    @media (max-width: 820px) {
        .bd-hello h1 { font-size: 24px; }
        .bd-hello .fw-av { width: 52px; height: 52px; flex-basis: 52px; }
        .bd-top { margin-top: 16px; gap: 12px; }
        .bd-hero.fw-pc { min-height: 214px; padding: 18px; border-radius: 24px; }
        .bd-prog { display: flex; gap: 12px; overflow-x: auto; margin: 0 -16px; padding: 0 16px 6px; scroll-snap-type: x mandatory; scrollbar-width: none; }
        .bd-prog::-webkit-scrollbar { display: none; }
        .bd-prog .fw-pc { flex: 0 0 210px; height: 250px; scroll-snap-align: start; }
        .bd-stats .fw-stat2 { padding: 12px; gap: 10px; }
        .bd-stats .fw-stat2 .ic { width: 40px; height: 40px; flex-basis: 40px; }
        .bd-quick { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; }
        .bd-quick a { padding: 10px 2px; border: 0; box-shadow: none; background: transparent; font-size: 11.5px; }
        .bd-quick a:nth-child(n+5) { display: none; }
    }
</style>
@endpush

@section('content')
    <div class="bd-hello">
        <div>
            <small>Halo,</small>
            <h1>{{ $user->name }}</h1>
            <p>{{ $greet }}! Saatnya tingkatkan permainan golf Anda.</p>
        </div>
        <a href="{{ Route::has('menu') ? route('menu') : route('profile.edit') }}" class="fw-av lg" aria-label="Menu akun">{{ Icons::initials($user->name) }}</a>
    </div>

    @if (session('status') || session('success'))
        <div class="fw-alert ok" style="margin-top:16px">{{ session('status') ?? session('success') }}</div>
    @endif

    <div class="bd-top">
        @if ($next)
            @php
                $nDate = Carbon::parse($next->booking_date);
                $isToday = $nDate->isToday();
            @endphp
            <div class="bd-hero fw-pc side">
                <img class="ph" src="{{ $photo }}" alt="" onerror="this.remove()">
                <div>
                    <div class="row">
                        <span class="fw-pc-label lime">{{ $isToday ? 'Lesson hari ini' : 'Lesson berikutnya' }}</span>
                        <span class="fw-pc-label dark">{{ $next->status === 'booked' ? 'Disetujui' : 'Menunggu' }}</span>
                    </div>
                    <h2 style="margin-top:12px">{{ $next->lesson_label }}</h2>
                    <div class="fw-pc-chips">
                        <span>{!! Icons::svg('calendar') !!} {{ $nDate->locale('id')->translatedFormat('D, d M') }}</span>
                        <span>{!! Icons::svg('clock') !!} {{ substr($next->start_time, 0, 5) }} – {{ substr($next->end_time, 0, 5) }}</span>
                        @if ($next->place_label)<span>{!! Icons::svg('pin') !!} {{ \Illuminate\Support\Str::limit($next->place_label, 26) }}</span>@endif
                    </div>
                </div>
                @if ($next->status === 'pending' && Route::has('payment.booking'))
                    <a href="{{ route('payment.booking', $next) }}" class="fw-pc-btn">Selesaikan Pembayaran {!! Icons::svg('arrow') !!}</a>
                @else
                    <a href="{{ Route::has('jadwal') ? route('jadwal') : route('booking') }}" class="fw-pc-btn white">Lihat Jadwal {!! Icons::svg('arrow') !!}</a>
                @endif
            </div>
        @else
            <div class="bd-hero fw-pc side">
                <img class="ph" src="{{ $photo }}" alt="" onerror="this.remove()">
                <div>
                    <span class="fw-pc-label lime">Lesson golf per jam</span>
                    <h2 style="margin-top:12px">Better Swing,<br>Better You</h2>
                    <p style="margin-top:8px">Pilih jam kosong dan booking lesson sekarang.</p>
                </div>
                <a href="{{ route('booking') }}" class="fw-pc-btn">Booking Sekarang {!! Icons::svg('arrow') !!}</a>
            </div>
        @endif

        <div class="bd-stats">
            @foreach ($stats as [$label, $value, $icon, $tone])
                <div class="fw-stat2"><span class="ic {{ $tone }}">{!! Icons::svg($icon) !!}</span><div><strong>{{ $value }}</strong><span>{{ $label }}</span></div></div>
            @endforeach
        </div>
    </div>

    @if ($unpaid > 0)
        <a href="{{ Route::has('jadwal') ? route('jadwal') : route('booking') }}" class="fw-alert warn" style="margin:16px 0 0;align-items:center">
            {!! Icons::svg('wallet') !!}
            <span style="flex:1">Ada <b>{{ $unpaid }}</b> booking menunggu pembayaran. Selesaikan sebelum batas waktu agar jadwal tidak hilang.</span>
            {!! Icons::svg('chev', 'fw-chev') !!}
        </a>
    @endif

    <section class="fw-section">
        <div class="fw-section-head"><h2>Quick Action</h2></div>
        <div class="bd-quick">
            <a href="{{ Route::has('jadwal') ? route('jadwal') : route('booking') }}"><span class="ic">{!! Icons::svg('cal-check') !!}</span>Jadwal</a>
            <a href="{{ route('booking') }}"><span class="ic">{!! Icons::svg('plus-c') !!}</span>Booking</a>
            <a href="{{ route('chat') }}"><span class="ic">{!! Icons::svg('chat') !!}@if ($chatNew)<b>{{ $chatNew }}</b>@endif</span>Chat Coach</a>
            <a href="{{ Route::has('menu') ? route('menu') : route('profile.edit') }}"><span class="ic">{!! Icons::svg('menu') !!}</span>Menu</a>
            <a href="{{ route('program') }}"><span class="ic">{!! Icons::svg('star') !!}</span>Program</a>
            <a href="{{ route('galeri') }}"><span class="ic">{!! Icons::svg('image') !!}</span>Galeri</a>
        </div>
    </section>

    <div class="bd-cols fw-section">
        <section>
            <div class="fw-section-head"><h2>Coach Anda</h2><a href="{{ route('home') }}#coach" class="fw-link">Profil lengkap</a></div>
            @if ($coach)
                <a href="{{ route('home') }}#coach" class="fw-card bd-coach">
                    @if ($coach->photo_url)
                        <span class="fw-av" style="background-image:url('{{ $coach->photo_url }}')"></span>
                    @else
                        <span class="fw-av">{{ Icons::initials($coach->name) }}</span>
                    @endif
                    <div style="min-width:0">
                        <strong>{{ $coach->name }}</strong>
                        <small>{{ $coach->role ?: 'Coach profesional' }}@if ($coach->years_experience) · {{ $coach->years_experience }}+ tahun @endif</small>
                        @if ($coach->bio)<p>{{ $coach->bio }}</p>@endif
                    </div>
                    {!! Icons::svg('chev', 'fw-chev') !!}
                </a>
            @endif

            @if ($events->isNotEmpty())
                @php $ev = $events->first(); @endphp
                <div class="fw-section-head" style="margin-top:22px"><h2>Event Terdekat</h2><a href="{{ route('event') }}" class="fw-link">Semua</a></div>
                <a href="{{ route('event') }}" class="fw-card bd-ev">
                    <span class="bd-ev-img">@if ($ev->poster_url)<img src="{{ $ev->poster_url }}" alt="" loading="lazy" onerror="this.remove()">@endif</span>
                    <div>
                        <span class="fw-badge orange-soft" style="align-self:flex-start">{{ $ev->status_label }}</span>
                        <strong>{{ $ev->title }}</strong>
                        @if ($ev->location)<small>{{ $ev->location }}</small>@endif
                        <span class="foot"><b>{{ $ev->event_date?->locale('id')->translatedFormat('d M') }}@if ($ev->time_range) · {{ \Illuminate\Support\Str::before($ev->time_range, ' ') }}@endif</b><strong style="font-size:13.5px">{{ $ev->price_label }}</strong></span>
                    </div>
                </a>
            @endif
        </section>

        @if ($programs->isNotEmpty())
            <section>
                <div class="fw-section-head"><h2>Program Latihan</h2><a href="{{ route('program') }}" class="fw-link">Lihat semua</a></div>
                <div class="bd-prog">
                    @foreach ($programs as $p)
                        <a href="{{ route('program') }}#program-{{ $p->id }}" class="fw-pc">
                            <img class="ph" src="{{ $p->image_url }}" alt="" loading="lazy" style="object-position: {{ $p->image_css_position }}" onerror="this.remove()">
                            <div class="fw-pc-top"><span class="fw-pc-label">{{ ucfirst($p->level) }}</span></div>
                            <div class="fw-pc-body">
                                <h3>{{ $p->name }}</h3>
                                <p style="font-size:12px">60 menit · Private</p>
                                <span class="fw-pc-label lime" style="align-self:flex-start">{{ \App\Models\Payment::formatRupiah($price) }}/jam</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
