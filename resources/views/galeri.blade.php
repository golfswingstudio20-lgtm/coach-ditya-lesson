@extends('layouts.fw')

@section('title', 'Galeri')

@php
    use App\Models\Gallery;
    use App\Support\Icons;

    // Hanya media berstatus "active" yang tampil ke customer & visitor
    $items = Gallery::active()->ordered()->get();
    $photoCount = $items->where('type', '!=', 'video')->count();
    $videoCount = $items->where('type', 'video')->count();
    $isCustomer = auth()->check() && auth()->user()->role === 'customer';
@endphp

@push('head')
<style>
    .gl-tabs { margin-bottom: 18px; }
    .gl-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); grid-auto-rows: 210px; grid-auto-flow: dense; gap: 12px; }
    .gl-item { position: relative; padding: 0; border: 0; border-radius: 20px; overflow: hidden; background: linear-gradient(135deg, #1f4d33, #0c1f15); cursor: pointer; box-shadow: 0 10px 26px rgba(var(--d-shadow-rgb, 23, 46, 33), .14); text-align: left; font: inherit; }
    .gl-item.big { grid-column: span 2; grid-row: span 2; }
    .gl-item.wide { grid-column: span 2; }
    .gl-item.tall { grid-row: span 2; }
    .gl-item img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; filter: brightness(1.1) saturate(1.1); transition: transform .4s ease; }
    .gl-item:hover img { transform: scale(1.05); }
    .gl-item::after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(8, 20, 13, 0) 48%, rgba(8, 20, 13, .85) 100%); pointer-events: none; }
    .gl-item .cap { position: absolute; z-index: 1; left: 14px; right: 14px; bottom: 12px; color: #fff; }
    .gl-item .cap strong { display: block; font-size: 14px; font-weight: 600; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .gl-item .cap small { display: block; margin-top: 2px; color: rgba(255, 255, 255, .78); font-size: 12px; }
    .gl-item .badge { position: absolute; z-index: 1; top: 12px; left: 12px; padding: 4px 9px; border-radius: 9px; background: rgba(8, 20, 13, .55); color: #fff; font-size: 11px; font-weight: 600; -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px); }
    .gl-item .play { position: absolute; z-index: 1; top: 50%; left: 50%; width: 56px; height: 56px; margin: -28px 0 0 -28px; display: grid; place-items: center; border-radius: 50%; background: rgba(255, 255, 255, .95); color: #1f4d33; box-shadow: 0 8px 20px rgba(0, 0, 0, .35); transition: transform .2s; }
    .gl-item:hover .play { transform: scale(1.08); }
    .gl-item .play svg { width: 22px; height: 22px; margin-left: 3px; }
    .gl-ph { position: absolute; inset: 0; display: grid; place-items: center; color: rgba(255, 255, 255, .35); }
    .gl-ph svg { width: 40px; height: 40px; }
    .gl-item.ig { background: linear-gradient(135deg, #f9ce34 0%, #ee2a7b 50%, #6228d7 100%); }
    .gl-item.ig .gl-ph { color: rgba(255, 255, 255, .85); }
    .gl-item video.thumb { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; pointer-events: none; }
    .lb-media .ratio.portrait { aspect-ratio: auto; width: min(420px, 100%); height: min(82vh, 760px); margin: 0 auto; background: #fff; }
    .lb { position: fixed; inset: 0; z-index: 330; display: none; align-items: center; justify-content: center; padding: 24px; background: rgba(15, 26, 19, .88); }
    .lb.open { display: flex; }
    .lb-box { width: min(980px, 100%); }
    .lb-media { border-radius: 20px; overflow: hidden; background: #000; }
    .lb-media:has(.lb-open) { background: transparent; overflow: visible; }
    .lb-media:has(.lb-open) .ratio { border-radius: 20px; overflow: hidden; }
    .lb-box:has(.lb-open) .lb-cap { text-align: center; }
    .lb-media img { width: 100%; max-height: 78vh; object-fit: contain; display: block; margin: 0 auto; }
    .lb-media .ratio { position: relative; aspect-ratio: 16 / 9; }
    .lb-media iframe, .lb-media video { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
    .lb-cap { margin-top: 12px; color: #fff; }
    .lb-open { display: flex; width: fit-content; align-items: center; gap: 8px; margin: 12px auto 0; height: 44px; padding: 0 20px; border-radius: 99px; background: linear-gradient(90deg, #f58529, #dd2a7b 55%, #8134af); color: #fff; font-weight: 700; font-size: 14px; text-decoration: none; }
    .lb-open svg { width: 18px; height: 18px; }
    .lb-cap strong { display: block; font-size: 16px; }
    .lb-cap span { color: rgba(255, 255, 255, .7); font-size: 13.5px; }
    .lb-x { position: absolute; top: 16px; right: 16px; width: 44px; height: 44px; display: grid; place-items: center; border: 0; border-radius: 50%; background: rgba(var(--d-glass-rgb, 255, 255, 255), .14); color: #fff; cursor: pointer; }
    .lb-x svg { width: 20px; height: 20px; }
    @media (max-width: 960px) { .gl-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 820px) {
        .gl-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); grid-auto-rows: 120px; gap: 10px; }
        .gl-item { border-radius: 18px; }
        .gl-item .cap strong { font-size: 13px; }
        .gl-item:not(.big):not(.wide) .cap small { display: none; }
        .gl-item .play { width: 48px; height: 48px; margin: -24px 0 0 -24px; }
        .lb { padding: 12px; }
    }
</style>
@endpush

@section('content')
    <div class="fw-pagehead">
        <div class="fw-pagehead-title">
            <a href="{{ $isCustomer ? route('dashboard') : route('home') }}" class="fw-back" aria-label="Kembali">{!! Icons::svg('back') !!}</a>
            <div>
                <h1 class="fw-h1">Galeri</h1>
                <p class="fw-sub">Aktivitas latihan, suasana lapangan, dan video rekomendasi.</p>
            </div>
        </div>
    </div>

    <div class="fw-pills gl-tabs" id="glTabs" role="tablist">
        <button type="button" class="fw-pill on" data-type="">Semua <small>{{ $items->count() }}</small></button>
        <button type="button" class="fw-pill" data-type="image">Foto <small>{{ $photoCount }}</small></button>
        <button type="button" class="fw-pill" data-type="video">Video <small>{{ $videoCount }}</small></button>
    </div>

    @if ($items->isEmpty())
        <div class="fw-empty"><b>Galeri masih kosong</b>Foto & video akan segera ditambahkan.</div>
    @else
        <div class="gl-grid" id="glGrid">
            @foreach ($items as $g)
                @php
                    $isVideo = $g->type === 'video';
                    $thumb   = $isVideo ? $g->thumbnail_url : $g->image_url;
                    $shape   = [0 => 'big', 3 => 'tall', 4 => 'wide', 7 => 'big'][$loop->index % 8] ?? '';
                    if ($isVideo && $shape === '') { $shape = 'tall'; }
                    $platform = $g->video_platform;
                @endphp
                <button type="button" class="gl-item {{ $shape }} {{ $platform === 'instagram' ? 'ig' : '' }}" data-type="{{ $isVideo ? 'video' : 'image' }}"
                        data-title="{{ $g->title }}" data-desc="{{ $g->category ?: \Illuminate\Support\Str::limit($g->description, 90) }}"
                        @if ($isVideo)
                            data-embed="{{ $g->embed_url }}" data-platform="{{ $platform }}" data-link="{{ $platform === 'instagram' ? $g->video_url : '' }}" data-file="{{ $g->video_file_url }}" data-poster="{{ $g->image_url }}"
                        @else
                            data-src="{{ $g->image_url }}"
                        @endif
                        aria-label="{{ $g->title }}">
                    <span class="gl-ph">{!! Icons::svg($isVideo ? 'video' : 'image') !!}</span>
                    @if ($thumb)<img src="{{ $thumb }}" alt="" loading="lazy" onerror="this.remove()">
                    @elseif ($platform === 'file' && $g->video_file_url)<video class="thumb" src="{{ $g->video_file_url }}#t=1" muted playsinline preload="metadata"></video>@endif
                    @if ($isVideo)
                        <span class="badge">{{ ['youtube' => 'YouTube', 'instagram' => 'Instagram'][$platform] ?? 'Video' }}</span>
                        <span class="play">{!! Icons::svg('play') !!}</span>
                    @endif
                    <span class="cap"><strong>{{ $g->title }}</strong>@if ($g->category)<small>{{ $g->category }}</small>@endif</span>
                </button>
            @endforeach
        </div>
        <div class="fw-empty" id="glEmpty" hidden><b>Belum ada media di kategori ini</b></div>
    @endif

    <div class="lb" id="lb" role="dialog" aria-modal="true">
        <button type="button" class="lb-x" data-lb-close aria-label="Tutup">{!! Icons::svg('x') !!}</button>
        <div class="lb-box">
            <div class="lb-media" id="lbMedia"></div>
            <div class="lb-cap"><strong id="lbTitle"></strong><span id="lbDesc"></span></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var grid = document.getElementById('glGrid');
    if (!grid) return;
    document.querySelectorAll('#glTabs .fw-pill').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('#glTabs .fw-pill').forEach(function (x) { x.classList.remove('on'); });
            b.classList.add('on');
            var shown = 0;
            grid.querySelectorAll('.gl-item').forEach(function (it) {
                var ok = !b.dataset.type || it.dataset.type === b.dataset.type;
                it.hidden = !ok; if (ok) shown++;
            });
            document.getElementById('glEmpty').hidden = shown > 0;
        });
    });

    var lb = document.getElementById('lb'), media = document.getElementById('lbMedia');
    function close() { lb.classList.remove('open'); media.innerHTML = ''; document.body.style.overflow = ''; }
    grid.querySelectorAll('.gl-item').forEach(function (it) {
        it.addEventListener('click', function () {
            // Instagram di HP: langsung buka postingannya (aplikasi Instagram / tab baru).
            if (it.dataset.platform === 'instagram' && it.dataset.link && window.matchMedia('(max-width: 820px), (pointer: coarse)').matches) {
                window.open(it.dataset.link, '_blank', 'noopener');
                return;
            }
            media.innerHTML = '';
            if (it.dataset.type === 'video') {
                var wrap = document.createElement('div'); wrap.className = 'ratio' + (it.dataset.platform === 'instagram' ? ' portrait' : '');
                if (it.dataset.embed) {
                    var f = document.createElement('iframe');
                    f.src = it.dataset.embed + (it.dataset.embed.indexOf('?') === -1 ? '?' : '&') + 'autoplay=1';
                    f.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share'; f.allowFullscreen = true;
                    f.setAttribute('scrolling', 'no');
                    wrap.appendChild(f);
                } else if (it.dataset.file) {
                    var v = document.createElement('video'); v.controls = true; v.autoplay = true; v.src = it.dataset.file;
                    if (it.dataset.poster) v.poster = it.dataset.poster;
                    wrap.appendChild(v);
                }
                media.appendChild(wrap);
                if (it.dataset.link) {
                    var a = document.createElement('a');
                    a.className = 'lb-open'; a.href = it.dataset.link; a.target = '_blank'; a.rel = 'noopener';
                    a.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>Buka di Instagram';
                    media.appendChild(a);
                }
            } else {
                var img = document.createElement('img'); img.src = it.dataset.src; img.alt = it.dataset.title; media.appendChild(img);
            }
            document.getElementById('lbTitle').textContent = it.dataset.title || '';
            document.getElementById('lbDesc').textContent = it.dataset.desc || '';
            lb.classList.add('open'); document.body.style.overflow = 'hidden';
        });
    });
    lb.addEventListener('click', function (e) { if (e.target === lb || e.target.closest('[data-lb-close]')) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
})();
</script>
@endpush
