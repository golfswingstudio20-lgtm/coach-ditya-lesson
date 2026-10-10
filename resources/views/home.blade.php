@extends('layouts.fw')

@section('main_class', 'fw-home-main')

@php
    use App\Support\Icons;
    use Illuminate\Support\Str;

    $hero  = \App\Support\Brand::hero();
    $nice  = fn ($s) => $s === mb_strtoupper($s) ? Str::title(mb_strtolower($s)) : $s;
    $photo = \App\Support\Brand::background('public');

    $programs  = class_exists(\App\Models\Program::class) ? \App\Models\Program::active()->ordered()->take(3)->get() : collect();
    $photos    = class_exists(\App\Models\Gallery::class) ? \App\Models\Gallery::active()->ordered()->take(5)->get() : collect();
    $events    = class_exists(\App\Models\Event::class) ? \App\Models\Event::active()->upcoming()->chronological()->take(2)->get() : collect();
    $locations = collect(\App\Support\BookingRules::activeLocations());
    $coach     = \App\Models\Coach::main();
    $price     = \App\Models\Payment::pricePerHour();

    $ctaUrl   = auth()->check() ? (auth()->user()->role === 'admin' ? route('admin.dashboard') : route('booking')) : route('login');
    $ctaLabel = auth()->check() && auth()->user()->role === 'admin' ? 'Buka Panel Admin' : 'Mulai Sekarang';
@endphp

@push('head')
<style>
    .fw-home-main { width: 100% !important; padding: 0 !important; }
    .hm-wrap { width: min(1180px, 100% - 48px); margin: 0 auto; }

    /* HERO: foto penuh, bayangan gelap, teks putih */
    .hm-hero { position: relative; overflow: hidden; min-height: min(78vh, 720px); display: flex; align-items: center; color: #fff; background: #0c1f15; }
    .hm-hero-bg { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: 62% 55%; filter: brightness(1.18) saturate(1.12); }
    .hm-hero::after { content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, rgba(8, 20, 13, .9) 0%, rgba(8, 20, 13, .66) 40%, rgba(8, 20, 13, .08) 75%), linear-gradient(180deg, rgba(8, 20, 13, .35) 0%, rgba(8, 20, 13, 0) 22%, rgba(8, 20, 13, 0) 78%, rgba(8, 20, 13, .35) 100%); }
    .hm-hero-grid { position: relative; z-index: 1; display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 48px; align-items: center; padding: 70px 0 110px; }
    .hm-label { display: inline-flex; align-items: center; gap: 8px; padding: 7px 14px; border-radius: 99px; background: rgba(255, 255, 255, .14); border: 1px solid rgba(255, 255, 255, .28); color: #fff; font-size: 12.5px; font-weight: 600; -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px); }
    .hm-label i { width: 7px; height: 7px; border-radius: 50%; background: #cde8a3; }
    .hm-title { margin-top: 18px; max-width: 15ch; font-family: var(--fw-serif); font-weight: 700; font-size: clamp(40px, 5.4vw, 66px); line-height: 1.04; letter-spacing: -1px; color: #fff; }
    .hm-title em { font-style: italic; color: #cde8a3; }
    .hm-text { margin-top: 18px; max-width: 46ch; color: rgba(255, 255, 255, .86); font-size: 17px; line-height: 1.65; }
    .hm-cta { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 28px; }
    .hm-cta .fw-btn { height: 52px; padding: 0 26px; font-size: 15px; }
    .hm-cta .fw-btn.lime { background: #cde8a3; color: #14301f; }
    .hm-cta .fw-btn.glass { background: rgba(255, 255, 255, .12); border: 1px solid rgba(255, 255, 255, .42); color: #fff; }
    .hm-cta .fw-btn.glass:hover { background: rgba(255, 255, 255, .22); }

    .hm-side { display: flex; flex-direction: column; gap: 12px; }
    .hm-float { display: flex; align-items: center; gap: 12px; padding: 14px; border-radius: 20px; background: rgba(255, 255, 255, .95); color: #17261d; box-shadow: 0 24px 50px rgba(0, 0, 0, .3); }
    html[data-theme="dark"] .hm-float { background: rgba(24, 35, 29, .95); color: #e8efe9; }
    .hm-float .ic { width: 44px; height: 44px; flex: 0 0 44px; display: grid; place-items: center; border-radius: 14px; background: var(--fw-tint); color: var(--d-ink-green, var(--fw-green)); }
    .hm-float .ic svg { width: 20px; height: 20px; }
    .hm-float .fw-av { width: 48px; height: 48px; border-radius: 14px; background-size: cover; background-position: center top; }
    .hm-float small { display: block; font-size: 10.5px; font-weight: 700; letter-spacing: 1.2px; color: var(--fw-green-3); text-transform: uppercase; }
    .hm-float strong { display: block; font-size: 15px; }
    .hm-float span { color: var(--fw-muted); font-size: 12.5px; }

    .hm-trust { position: relative; z-index: 2; margin-top: -54px; display: grid; grid-template-columns: repeat(var(--n, 3), minmax(0, 1fr)); max-width: 640px; padding: 16px 8px; border-radius: 22px; background: var(--fw-surface); box-shadow: 0 18px 40px rgba(var(--d-shadow-rgb, 23, 46, 33), .16); }
    .hm-trust div { text-align: center; border-right: 1px solid var(--fw-line); }
    .hm-trust div:last-child { border-right: 0; }
    .hm-trust strong { display: block; font-family: var(--fw-serif); font-size: 28px; font-weight: 700; color: var(--d-ink-green, var(--fw-green)); line-height: 1.1; }
    .hm-trust span { color: var(--fw-muted); font-size: 12.5px; }

    /* Section umum */
    .hm-sec { padding: 64px 0; }
    .hm-sec.alt { background: var(--fw-surface); border-top: 1px solid var(--fw-line); border-bottom: 1px solid var(--fw-line); }
    .hm-sec-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; margin-bottom: 26px; }
    .hm-sec-head p { max-width: 52ch; margin-top: 8px; color: var(--fw-muted); font-size: 15px; line-height: 1.65; }

    /* Fitur */
    .hm-feat { padding: 22px; border-radius: 20px; background: var(--fw-surface-2); border: 1px solid var(--fw-line); }
    .hm-sec.alt .hm-feat { background: var(--fw-bg); }
    .hm-feat .ic { width: 46px; height: 46px; display: grid; place-items: center; border-radius: 14px; background: var(--fw-green); color: #fff; }
    .hm-feat .ic svg { width: 22px; height: 22px; }
    .hm-feat h3 { margin-top: 14px; font-size: 16px; font-weight: 700; }
    .hm-feat p { margin-top: 6px; color: var(--fw-muted); font-size: 14px; line-height: 1.6; }

    /* Program (kartu foto) */
    .hm-prog { min-height: 340px; }

    /* Galeri (mosaik) */
    .hm-gal { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); grid-auto-rows: 180px; gap: 12px; }
    .hm-gal .fw-pc { border-radius: 20px; box-shadow: none; }
    .hm-gal .fw-pc:first-child { grid-column: span 2; grid-row: span 2; }
    .hm-gal .fw-pc::after { background: linear-gradient(180deg, rgba(8, 20, 13, 0) 50%, rgba(8, 20, 13, .82) 100%); }
    .hm-gal .cap { position: absolute; left: 14px; right: 14px; bottom: 12px; font-size: 13.5px; font-weight: 600; line-height: 1.3; }
    .hm-gal .fw-pc:not(:first-child) .cap { font-size: 12.5px; }

    /* Event */
    .hm-ev { display: flex; gap: 16px; padding: 12px; }
    .hm-ev-img { position: relative; width: 132px; flex: 0 0 132px; aspect-ratio: 4 / 5; border-radius: 16px; overflow: hidden; background: linear-gradient(135deg, #1f4d33, #0c1f15); }
    .hm-ev-img img { width: 100%; height: 100%; object-fit: cover; object-position: top; }
    .hm-ev-img .fw-pc-date { position: absolute; left: 8px; bottom: 8px; width: 50px; padding: 6px 0; border-radius: 12px; }
    .hm-ev-img .fw-pc-date b { font-size: 20px; }
    .hm-ev > div:last-child { flex: 1; min-width: 0; display: flex; flex-direction: column; padding: 4px 2px; }
    .hm-ev h3 { font-size: 17px; font-weight: 700; }
    .hm-ev p { margin-top: 6px; color: var(--fw-muted); font-size: 13.5px; display: flex; align-items: center; gap: 6px; }
    .hm-ev p svg { width: 15px; height: 15px; }
    .hm-ev .price { margin-top: auto; padding-top: 8px; font-weight: 700; color: var(--d-ink-green, var(--fw-green)); }

    /* CTA */
    .hm-band { position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; gap: 24px; padding: 40px 44px; border-radius: 28px; background: var(--fw-green); color: #fff; }
    .hm-band::before { content: ""; position: absolute; right: -60px; bottom: -120px; width: 380px; height: 380px; border-radius: 50%; background: rgba(205, 232, 163, .12); }
    .hm-band h2 { position: relative; font-family: var(--fw-serif); font-size: clamp(26px, 3vw, 36px); font-weight: 600; line-height: 1.15; }
    .hm-band p { position: relative; margin-top: 8px; color: rgba(255, 255, 255, .75); }
    .hm-band .fw-btn { position: relative; }

    @media (max-width: 960px) {
        .hm-hero-grid { grid-template-columns: 1fr; }
        .hm-side { display: none; }
        .hm-gal { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 820px) {
        .hm-wrap { width: calc(100% - 32px); }
        .hm-hero { min-height: calc(100svh - 62px - 60px); align-items: flex-end; }
        .hm-hero-bg { object-position: 72% 60%; }
        .hm-hero::after { background: linear-gradient(180deg, rgba(8, 20, 13, .45) 0%, rgba(8, 20, 13, .05) 28%, rgba(8, 20, 13, .3) 50%, rgba(8, 20, 13, .94) 100%); }
        .hm-hero-grid { padding: 40px 0 76px; }
        .hm-title { font-size: 38px; }
        .hm-text { font-size: 15px; }
        .hm-cta .fw-btn { flex: 1; height: 50px; padding: 0 14px; }
        .hm-trust { margin: -40px 16px 0; padding: 14px 4px; }
        .hm-trust strong { font-size: 24px; }
        .hm-trust span { font-size: 11.5px; }
        .hm-sec { padding: 40px 0; }
        .hm-sec-head { flex-direction: column; align-items: flex-start; }
        .hm-prog { min-height: 300px; }
        .hm-gal { grid-auto-rows: 120px; gap: 10px; }
        .hm-band { flex-direction: column; align-items: flex-start; padding: 28px 24px; }
        .hm-ev-img { width: 104px; flex-basis: 104px; }
    }
</style>
@endpush

@section('content')
    {{-- ================= HERO ================= --}}
    <section class="hm-hero">
        <img class="hm-hero-bg" src="{{ $photo }}" alt="" fetchpriority="high">
        <div class="hm-wrap hm-hero-grid">
            <div>
                <span class="hm-label"><i></i>{{ $nice($hero['hero_label']) }}</span>
                <h1 class="hm-title">{{ $nice($hero['hero_title_1']) }} {{ $nice($hero['hero_title_2']) }} <em>{{ $nice($hero['hero_highlight']) }}</em></h1>
                <p class="hm-text">{{ $hero['hero_text'] }}</p>
                <div class="hm-cta">
                    <a href="{{ $ctaUrl }}" class="fw-btn lime">{{ $ctaLabel }} {!! Icons::svg('arrow') !!}</a>
                    <a href="{{ route('program') }}" class="fw-btn glass">Lihat Program</a>
                </div>
            </div>

            <div class="hm-side">
                @if ($coach)
                    <a href="#coach" class="hm-float">
                        @if ($coach->photo_url)
                            <span class="fw-av" style="background-image:url('{{ $coach->photo_url }}')"></span>
                        @else
                            <span class="fw-av">{{ Icons::initials($coach->name) }}</span>
                        @endif
                        <div><small>Coach Anda</small><strong>{{ $coach->name }}</strong><span>{{ $coach->role ?: 'Coach profesional' }}</span></div>
                    </a>
                @endif
                <div class="hm-float">
                    <span class="ic">{!! Icons::svg('calendar') !!}</span>
                    <div><strong>Booking online</strong><span>Pilih jam, bayar, langsung main</span></div>
                </div>
                @if ($locations->isNotEmpty())
                    <div class="hm-float">
                        <span class="ic">{!! Icons::svg('pin') !!}</span>
                        <div><strong>{{ $locations->count() }} lokasi latihan</strong><span>{{ \Illuminate\Support\Str::limit($locations->pluck('name')->implode(', '), 40) }}</span></div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    @php
        $trust = array_values(array_filter([
            $coach?->years_experience ? [$coach->years_experience . '+', 'Tahun melatih'] : null,
            $locations->isNotEmpty() ? [$locations->count(), 'Lokasi latihan'] : null,
            $programs->isNotEmpty() ? [\App\Models\Program::active()->count(), 'Program'] : null,
        ]));
    @endphp
    @if ($trust)
        <div class="hm-wrap">
            <div class="hm-trust" style="--n: {{ count($trust) }}">
                @foreach ($trust as [$v, $l])<div><strong>{{ $v }}</strong><span>{{ $l }}</span></div>@endforeach
            </div>
        </div>
    @endif

    {{-- ================= KEUNGGULAN ================= --}}
    <section class="hm-sec alt">
        <div class="hm-wrap">
            <div class="hm-sec-head">
                <div>
                    <span class="fw-eyebrow">Kenapa kami</span>
                    <h2 class="fw-h2" style="margin-top:6px">Belajar golf jadi lebih mudah</h2>
                </div>
            </div>
            <div class="fw-grid c3">
                <div class="hm-feat"><span class="ic">{!! Icons::svg('calendar') !!}</span><h3>Atur jadwal sendiri</h3><p>Lihat jam kosong secara langsung dan booking lesson per jam dari HP.</p></div>
                <div class="hm-feat"><span class="ic">{!! Icons::svg('coach') !!}</span><h3>Coach berpengalaman</h3><p>Dilatih langsung oleh coach profesional dengan metode yang terstruktur.</p></div>
                <div class="hm-feat"><span class="ic">{!! Icons::svg('pin') !!}</span><h3>Pilih lokasi latihan</h3><p>Driving range atau course lesson di lapangan golf pilihan Anda.</p></div>
            </div>
        </div>
    </section>

    {{-- ================= PROGRAM ================= --}}
    @if ($programs->isNotEmpty())
        <section class="hm-sec">
            <div class="hm-wrap">
                <div class="hm-sec-head">
                    <div>
                        <span class="fw-eyebrow">Program</span>
                        <h2 class="fw-h2" style="margin-top:6px">Pilih program sesuai level Anda</h2>
                    </div>
                    <a href="{{ route('program') }}" class="fw-link">Semua program →</a>
                </div>
                <div class="fw-grid c3">
                    @foreach ($programs as $p)
                        <a href="{{ route('program') }}#program-{{ $p->id }}" class="fw-pc hm-prog">
                            <img class="ph" src="{{ $p->image_url }}" alt="" loading="lazy" style="object-position: {{ $p->image_css_position ?? 'center' }}" onerror="this.remove()">
                            <div class="fw-pc-top"><span class="fw-pc-label">{{ ucfirst($p->level) }}</span></div>
                            <div class="fw-pc-body">
                                <h3>{{ $p->name }}</h3>
                                <p>{{ Str::limit($p->description, 90) }}</p>
                                <div class="fw-pc-foot">
                                    <span class="fw-pc-price">{{ \App\Models\Payment::formatRupiah($price) }}<small> / jam</small></span>
                                    <span class="fw-pc-btn">Lihat {!! Icons::svg('arrow') !!}</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ================= ABOUT COACH ================= --}}
    @include('partials.home-coaches')

    {{-- ================= GALERI ================= --}}
    @if ($photos->isNotEmpty())
        <section class="hm-sec">
            <div class="hm-wrap">
                <div class="hm-sec-head">
                    <div>
                        <span class="fw-eyebrow">Galeri</span>
                        <h2 class="fw-h2" style="margin-top:6px">Suasana latihan kami</h2>
                    </div>
                    <a href="{{ route('galeri') }}" class="fw-link">Lihat galeri →</a>
                </div>
                <div class="hm-gal">
                    @foreach ($photos as $g)
                        <a href="{{ route('galeri') }}" class="fw-pc" aria-label="{{ $g->title }}">
                            @if ($g->thumbnail_url)<img class="ph" src="{{ $g->thumbnail_url }}" alt="" loading="lazy" onerror="this.remove()">@endif
                            @if ($g->type === 'video')
                                <span class="fw-pc-top"><span class="fw-pc-label dark">Video</span></span>
                                <span class="fw-pc-play">{!! Icons::svg('play') !!}</span>
                            @endif
                            @if ($g->title)<span class="cap">{{ \Illuminate\Support\Str::limit($g->title, 48) }}</span>@endif
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ================= EVENT ================= --}}
    @if ($events->isNotEmpty())
        <section class="hm-sec alt">
            <div class="hm-wrap">
                <div class="hm-sec-head">
                    <div>
                        <span class="fw-eyebrow">Event</span>
                        <h2 class="fw-h2" style="margin-top:6px">Event mendatang</h2>
                    </div>
                    <a href="{{ route('event') }}" class="fw-link">Semua event →</a>
                </div>
                <div class="fw-grid c2">
                    @foreach ($events as $ev)
                        <a href="{{ route('event') }}" class="fw-card hm-ev">
                            <div class="hm-ev-img">
                                @if ($ev->poster_url)<img src="{{ $ev->poster_url }}" alt="" loading="lazy" onerror="this.remove()">@endif
                                @if ($ev->event_date)
                                    <span class="fw-pc-date"><small>{{ $ev->event_date->locale('id')->translatedFormat('M') }}</small><b>{{ $ev->event_date->format('d') }}</b></span>
                                @endif
                            </div>
                            <div>
                                <span class="fw-badge orange-soft" style="align-self:flex-start">{{ $ev->status_label }}</span>
                                <h3 style="margin-top:8px">{{ $ev->title }}</h3>
                                @if ($ev->time_range)<p>{!! Icons::svg('clock') !!} {{ $ev->date_label }} · {{ $ev->time_range }}</p>@else<p>{!! Icons::svg('calendar') !!} {{ $ev->date_label }}</p>@endif
                                @if ($ev->location)<p>{!! Icons::svg('pin') !!} {{ $ev->location }}</p>@endif
                                @if ($ev->price_label)<span class="price">{{ $ev->price_label }}</span>@endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ================= CTA ================= --}}
    <section class="hm-sec">
        <div class="hm-wrap">
            <div class="hm-band">
                <div>
                    <h2>Latihan hari ini,<br>prestasi esok hari.</h2>
                    <p>Booking lesson pertama Anda sekarang, hanya butuh 1 menit.</p>
                </div>
                <a href="{{ $ctaUrl }}" class="fw-btn lime">{{ $ctaLabel }} {!! Icons::svg('arrow') !!}</a>
            </div>
        </div>
    </section>
@endsection
