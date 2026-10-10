@extends('layouts.fw')

@section('title', 'Program Latihan')

@php
    use App\Support\Icons;
    use App\Support\BookingRules;

    $isCustomer = auth()->check() && auth()->user()->role === 'customer';
    $programs   = \App\Models\Program::active()->ordered()->get();
    $levels     = $programs->pluck('level')->filter()->map(fn ($l) => ucfirst(strtolower($l)))->unique()->values();
    $coach      = \App\Models\Coach::main();
    $rp         = fn ($n) => 'Rp' . number_format((int) $n, 0, ',', '.');
    $bookUrl    = fn ($type = 'driving') => $isCustomer ? route('booking', ['type' => $type]) : route('login');
    $locations  = BookingRules::activeLocations();
@endphp

@push('head')
<style>
    .pg-tools { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
    .pg-tools .fw-search { flex: 1; max-width: 360px; }
    .pg-card.fw-pc { min-height: 360px; cursor: pointer; transition: transform .2s, box-shadow .2s; }
    .pg-card.fw-pc:hover { transform: translateY(-3px); }
    .pg-card p { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .pg-meta { display: flex; flex-wrap: wrap; gap: 14px; color: var(--fw-muted); font-size: 12.5px; }
    .pg-meta span { display: inline-flex; align-items: center; gap: 5px; }
    .pg-meta svg { width: 15px; height: 15px; }
    .pg-price { position: relative; display: flex; flex-direction: column; gap: 12px; padding: 24px; overflow: hidden; }
    .pg-price.dark { background: var(--fw-green); color: #fff; border-color: transparent; }
    .pg-price .tag { align-self: flex-start; }
    .pg-price h3 { font-family: var(--fw-serif); font-size: 24px; font-weight: 600; }
    .pg-price .amount { font-family: var(--fw-serif); font-size: 34px; font-weight: 600; color: var(--d-ink-green, var(--fw-green)); }
    .pg-price.dark .amount { color: #cde8a3; }
    .pg-price .amount small { font-family: var(--fw-sans); font-size: 14px; color: var(--fw-muted); font-weight: 500; }
    .pg-price.dark .amount small { color: rgba(255, 255, 255, .7); }
    .pg-price ul { display: grid; gap: 8px; margin: 0; padding: 0; list-style: none; font-size: 14px; }
    .pg-price li { display: flex; gap: 10px; }
    .pg-price li::before { content: "✓"; flex: 0 0 20px; height: 20px; display: grid; place-items: center; border-radius: 50%; background: var(--fw-tint); color: var(--d-ink-green, var(--fw-green)); font-size: 11px; font-weight: 700; }
    .pg-price.dark li::before { background: rgba(var(--d-glass-rgb, 255, 255, 255), .14); color: var(--fw-lime); }
    .pg-price .fw-btn { margin-top: auto; align-self: flex-start; }

    /* Detail program (sheet) */
    .pd { position: fixed; inset: 0; z-index: 320; display: none; }
    .pd.open { display: block; }
    .pd-back { position: absolute; inset: 0; background: rgba(23, 38, 29, .45); }
    .pd-box { position: absolute; left: 50%; top: 50%; width: min(560px, calc(100% - 32px)); max-height: calc(100vh - 48px); transform: translate(-50%, -50%); overflow-y: auto; border-radius: 26px; background: var(--fw-surface); box-shadow: var(--fw-shadow-lg); }
    .pd-img { position: relative; aspect-ratio: 16 / 10; overflow: hidden; background: linear-gradient(135deg, #1f4d33, #0c1f15); }
    .pd-img > img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; filter: brightness(1.12) saturate(1.1); }
    .pd-img::after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(8, 20, 13, .3), rgba(8, 20, 13, 0) 40%); }
    .pd-top { z-index: 1; }
    .pd-top { position: absolute; left: 14px; right: 14px; top: 14px; display: flex; justify-content: space-between; }
    .pd-top button { width: 40px; height: 40px; display: grid; place-items: center; border: 0; border-radius: 50%; background: rgba(var(--d-glass-rgb, 255, 255, 255), .92); color: var(--fw-text); cursor: pointer; }
    .pd-top svg { width: 19px; height: 19px; }
    .pd-body { padding: 20px 22px 22px; }
    .pd-title { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .pd-title h2 { font-family: var(--fw-serif); font-size: 26px; font-weight: 600; }
    .pd-body > p { margin-top: 10px; color: var(--fw-text-2); font-size: 14.5px; line-height: 1.7; }
    .pd-feat { display: grid; gap: 8px; margin: 14px 0 0; padding: 0; list-style: none; font-size: 14px; }
    .pd-feat li { display: flex; gap: 10px; }
    .pd-feat li::before { content: "✓"; flex: 0 0 20px; height: 20px; display: grid; place-items: center; border-radius: 50%; background: var(--fw-tint); color: var(--d-ink-green, var(--fw-green)); font-size: 11px; font-weight: 700; }
    .pd-coach { display: flex; gap: 12px; margin-top: 18px; padding: 14px; border-radius: 16px; background: var(--fw-surface-2); }
    .pd-coach strong { display: block; font-size: 14.5px; }
    .pd-coach small { color: var(--fw-muted); font-size: 12.5px; }
    .pd-coach p { margin-top: 6px; color: var(--fw-text-2); font-size: 13px; line-height: 1.55; }
    .pd-body .fw-btn { margin-top: 18px; }
    @media (max-width: 820px) {
        .pg-card.fw-pc { min-height: 320px; }
        .pg-tools .fw-search { max-width: none; flex-basis: 100%; }
        .pd-box { left: 0; right: 0; top: auto; bottom: 0; width: 100%; max-height: 92vh; transform: none; border-radius: 26px 26px 0 0; }
    }
</style>
@endpush

@section('content')
    <div class="fw-pagehead">
        <div class="fw-pagehead-title">
            <a href="{{ $isCustomer ? route('dashboard') : route('home') }}" class="fw-back" aria-label="Kembali">{!! Icons::svg('back') !!}</a>
            <div>
                <h1 class="fw-h1">Program Latihan</h1>
                <p class="fw-sub">Program terstruktur bersama coach profesional, sesuai level permainan Anda.</p>
            </div>
        </div>
    </div>

    @if ($programs->isEmpty())
        <div class="fw-empty"><b>Program segera tersedia</b>Silakan cek kembali nanti.</div>
    @else
        <div class="pg-tools">
            <label class="fw-search">{!! Icons::svg('search') !!}<input type="search" id="pgSearch" placeholder="Cari program..."></label>
            <div class="fw-pills" id="pgFilter">
                <button type="button" class="fw-pill on" data-level="">Semua</button>
                @foreach ($levels as $lv)
                    <button type="button" class="fw-pill" data-level="{{ strtolower($lv) }}">{{ $lv }}</button>
                @endforeach
            </div>
        </div>

        <div class="fw-grid c3" id="pgGrid">
            @foreach ($programs as $p)
                <a href="#program-{{ $p->id }}" class="fw-pc pg-card" id="program-{{ $p->id }}" data-pd="{{ $p->id }}"
                        data-level="{{ strtolower($p->level) }}" data-q="{{ strtolower($p->name . ' ' . $p->description) }}">
                    <img class="ph" src="{{ $p->image_url }}" alt="" loading="lazy" style="object-position: {{ $p->image_css_position }}" onerror="this.remove()">
                    <span class="fw-pc-top"><span class="fw-pc-label">{{ ucfirst(strtolower($p->level)) }}</span></span>
                    <span class="fw-pc-body">
                        <h2 class="t">{{ $p->name }}</h2>
                        @if ($p->description)<p>{{ $p->description }}</p>@endif
                        <span class="fw-pc-chips">
                            <span>{!! Icons::svg('clock') !!} 60 menit</span>
                            <span>{!! Icons::svg('user') !!} 1 orang</span>
                        </span>
                        <span class="fw-pc-foot">
                            <span class="fw-pc-price">{{ $rp(BookingRules::pricePerHour()) }}<small> / jam</small></span>
                            <span class="fw-pc-btn">Detail {!! Icons::svg('arrow') !!}</span>
                        </span>
                    </span>
                </a>
                <template id="pd-{{ $p->id }}">
                    <div class="pd-img">
                        <img src="{{ $p->image_url }}" alt="" style="object-position: {{ $p->image_css_position }}" onerror="this.remove()">
                        <div class="pd-top"><button type="button" data-pd-close aria-label="Tutup">{!! Icons::svg('back') !!}</button></div>
                    </div>
                    <div class="pd-body">
                        <div class="pd-title"><h2>{{ $p->name }}</h2><span class="fw-badge green">{{ ucfirst(strtolower($p->level)) }}</span></div>
                        <div class="pg-meta" style="margin-top:8px;padding:0">
                            <span>{!! Icons::svg('clock') !!} 60 Menit</span>
                            <span>{!! Icons::svg('user') !!} 1 Orang</span>
                            <span>{!! Icons::svg('tag') !!} {{ $rp(BookingRules::pricePerHour()) }}/jam</span>
                        </div>
                        @if ($p->description)<p>{{ $p->description }}</p>@endif
                        @if (! empty($p->features))
                            <ul class="pd-feat">@foreach ($p->features as $f)<li>{{ $f }}</li>@endforeach</ul>
                        @endif
                        @if ($coach)
                            <div class="pd-coach">
                                @if ($coach->photo_url)<span class="fw-av" style="background-image:url('{{ $coach->photo_url }}')"></span>@else<span class="fw-av">{{ Icons::initials($coach->name) }}</span>@endif
                                <div>
                                    <small>Tentang Coach</small>
                                    <strong>{{ $coach->name }}</strong>
                                    <small>{{ $coach->role }}</small>
                                    @if ($coach->bio)<p>{{ \Illuminate\Support\Str::limit($coach->bio, 160) }}</p>@endif
                                </div>
                            </div>
                        @endif
                        <a href="{{ $bookUrl('driving') }}" class="fw-btn block">Booking Sekarang</a>
                    </div>
                </template>
            @endforeach
        </div>
        <div class="fw-empty" id="pgEmpty" hidden><b>Program tidak ditemukan</b>Coba kata kunci lain.</div>
    @endif

    {{-- Harga --}}
    <section class="fw-section" style="margin-top:44px">
        <div class="fw-section-head">
            <div>
                <span class="fw-eyebrow">Harga lesson</span>
                <h2 class="fw-h2" style="margin-top:6px">Pilih jenis lesson</h2>
            </div>
        </div>
        <div class="fw-grid c2">
            <article class="fw-card pg-price">
                <span class="fw-badge tag">Driving Range</span>
                <h3>Lesson Driving Range</h3>
                <div class="amount">{{ $rp(BookingRules::pricePerHour()) }} <small>/ jam</small></div>
                <ul>
                    <li>Pilih jam antara {{ BookingRules::openTime() }} – {{ BookingRules::closeTime() }}</li>
                    @if ($locations->isNotEmpty())<li>Lokasi: {{ $locations->pluck('name')->join(', ', ' atau ') }}</li>@endif
                    <li>Didampingi coach profesional</li>
                </ul>
                <a href="{{ $bookUrl('driving') }}" class="fw-btn">Booking Lesson</a>
            </article>
            @if (BookingRules::courseEnabled())
                <article class="fw-card pg-price dark">
                    <span class="fw-badge lime tag">On Course</span>
                    <h3>Course Lesson</h3>
                    <div class="amount">{{ $rp(BookingRules::coursePrice()) }} <small>/ sesi</small></div>
                    <ul>
                        <li>Sesi {{ BookingRules::courseStart() }} – {{ BookingRules::courseEnd() }}</li>
                        <li>Lapangan golf pilihan Anda sendiri</li>
                        <li>Praktik langsung di lapangan bersama coach</li>
                    </ul>
                    @if (BookingRules::courseNote())<p style="font-size:12.5px;color:rgba(255,255,255,.7)">* {{ BookingRules::courseNote() }}</p>@endif
                    <a href="{{ $bookUrl('course') }}" class="fw-btn lime">Booking Course Lesson</a>
                </article>
            @endif
        </div>
    </section>

    <div class="pd" id="pd" role="dialog" aria-modal="true">
        <div class="pd-back" data-pd-close></div>
        <div class="pd-box" id="pdBox"></div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var grid = document.getElementById('pgGrid');
    if (!grid) return;
    var level = '', q = '';
    function apply() {
        var shown = 0;
        grid.querySelectorAll('.pg-card').forEach(function (c) {
            var ok = (!level || c.dataset.level === level) && (!q || c.dataset.q.indexOf(q) !== -1);
            c.hidden = !ok; if (ok) shown++;
        });
        document.getElementById('pgEmpty').hidden = shown > 0;
    }
    document.querySelectorAll('#pgFilter .fw-pill').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('#pgFilter .fw-pill').forEach(function (x) { x.classList.remove('on'); });
            b.classList.add('on'); level = b.dataset.level; apply();
        });
    });
    document.getElementById('pgSearch').addEventListener('input', function () { q = this.value.trim().toLowerCase(); apply(); });

    var pd = document.getElementById('pd'), box = document.getElementById('pdBox');
    function open(id) {
        var t = document.getElementById('pd-' + id); if (!t) return;
        box.innerHTML = ''; box.appendChild(t.content.cloneNode(true));
        pd.classList.add('open'); document.body.style.overflow = 'hidden';
    }
    function close() { pd.classList.remove('open'); document.body.style.overflow = ''; history.replaceState(null, '', location.pathname); }
    grid.querySelectorAll('[data-pd]').forEach(function (c) { c.addEventListener('click', function (e) { e.preventDefault(); open(c.dataset.pd); history.replaceState(null, '', '#program-' + c.dataset.pd); }); });
    pd.addEventListener('click', function (e) { if (e.target.closest('[data-pd-close]')) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    var m = location.hash.match(/^#program-(\d+)$/); if (m) open(m[1]);
})();
</script>
@endpush
