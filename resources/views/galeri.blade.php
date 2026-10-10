@extends('layouts.fw')

@section('title', 'Galeri')

@php
    use App\Models\Gallery;
    use App\Support\Icons;

    // Hanya media berstatus "active" yang tampil ke customer & visitor
    $items = Gallery::active()->ordered()->get();
    $photoCount = $items->where('type', '!=', 'video')->count();
    $videoCount = $items->where('type', 'video')->count();

    // Folder: isi, jumlah, dan foto sampul (item pertama yang punya gambar)
    $folders = collect(Gallery::ALBUMS)->map(function ($meta, $key) use ($items) {
        $list  = $items->filter(fn ($g) => $g->album_key === $key);
        $cover = $list->first(fn ($g) => $g->type === 'video' ? $g->thumbnail_url : $g->image_url);

        return [
            'key'   => $key,
            'label' => $meta[0],
            'desc'  => $meta[1],
            'count' => $list->count(),
            'cover' => $cover ? ($cover->type === 'video' ? $cover->thumbnail_url : $cover->image_url) : null,
        ];
    })->filter(fn ($f) => $f['count'] > 0)->values();
    $folderIcons = ['latihan' => 'image', 'prestasi' => 'star', 'youtube' => 'play', 'instagram' => 'video'];
    $isCustomer = auth()->check() && auth()->user()->role === 'customer';
@endphp

@push('head')
<style>
    .gl-tabs { margin-bottom: 18px; }
    .gl-folders { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 14px; }
    .gl-folder { position: relative; height: 128px; padding: 0; border: 2px solid transparent; border-radius: 20px; overflow: hidden; background: linear-gradient(135deg, #1f4d33, #0c1f15); color: #fff; text-align: left; font: inherit; cursor: pointer; box-shadow: 0 8px 22px rgba(var(--d-shadow-rgb, 23, 46, 33), .14); transition: transform .2s, border-color .2s; }
    .gl-folder.ig { background: linear-gradient(135deg, #f9ce34, #ee2a7b 50%, #6228d7); }
    .gl-folder.yt { background: linear-gradient(135deg, #b31217, #3a0b0d); }
    .gl-folder img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; filter: brightness(1.05) saturate(1.1); }
    .gl-folder::after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(8, 20, 13, .15), rgba(8, 20, 13, .85)); }
    .gl-folder:hover { transform: translateY(-2px); }
    .gl-folder.on { border-color: #cde8a3; box-shadow: 0 0 0 3px rgba(205, 232, 163, .45), 0 10px 26px rgba(var(--d-shadow-rgb, 23, 46, 33), .2); }
    .gl-folder .fi { position: absolute; z-index: 1; top: 12px; left: 12px; width: 34px; height: 34px; display: grid; place-items: center; border-radius: 11px; background: rgba(255, 255, 255, .92); color: #1f4d33; }
    .gl-folder .fi svg { width: 17px; height: 17px; }
    .gl-folder .fc { position: absolute; z-index: 1; top: 12px; right: 12px; padding: 3px 9px; border-radius: 99px; background: rgba(8, 20, 13, .55); font-size: 12px; font-weight: 700; }
    .gl-folder .fn { position: absolute; z-index: 1; left: 14px; right: 14px; bottom: 12px; }
    .gl-folder .fn b { display: block; font-size: 15px; }
    .gl-folder .fn small { display: block; margin-top: 1px; color: rgba(255, 255, 255, .78); font-size: 11.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .gl-now { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin: 6px 0 12px; }
    .gl-now h2 { margin: 0; font-family: var(--fw-serif); font-size: 21px; }
    .gl-now button { height: 34px; padding: 0 14px; border: 1px solid var(--fw-line-2); border-radius: 99px; background: var(--fw-surface); color: var(--fw-text); font: inherit; font-size: 13px; font-weight: 600; cursor: pointer; }
    @media (max-width: 960px) { .gl-folders { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 820px) { .gl-folder { height: 108px; border-radius: 18px; } .gl-folder .fn small { display: none; } .gl-now h2 { font-size: 19px; } }
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
    .ig-box { width: min(400px, 100%); max-height: calc(100svh - 190px); margin: 0 auto; overflow-y: auto; border-radius: 16px; background: #fff; -webkit-overflow-scrolling: touch; }
    .ig-box .instagram-media { margin: 0 !important; min-width: 0 !important; width: 100% !important; max-width: 100% !important; border: 0 !important; border-radius: 0 !important; box-shadow: none !important; }
    .ig-box iframe { position: static !important; width: 100% !important; min-width: 0 !important; }
    .ig-loading { padding: 60px 20px; text-align: center; color: #555; font-size: 14px; }
    .lb:has(.ig-box) { align-items: flex-start; padding-top: 64px; overflow-y: auto; }
    .lb-media img { width: 100%; max-height: 78vh; object-fit: contain; display: block; margin: 0 auto; }
    .lb-media .ratio { position: relative; aspect-ratio: 16 / 9; }
    .lb-media .ratio iframe, .lb-media .ratio video { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
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

    @if ($folders->count() > 1)
        <div class="gl-folders" id="glFolders" aria-label="Folder galeri">
            @foreach ($folders as $f)
                <button type="button" class="gl-folder {{ $f['key'] === 'instagram' ? 'ig' : ($f['key'] === 'youtube' ? 'yt' : '') }}" data-album="{{ $f['key'] }}" data-label="{{ $f['label'] }}" aria-pressed="false">
                    @if ($f['cover'])<img src="{{ $f['cover'] }}" alt="" loading="lazy" onerror="this.remove()">@endif
                    <span class="fi">{!! Icons::svg($folderIcons[$f['key']]) !!}</span>
                    <span class="fc">{{ $f['count'] }}</span>
                    <span class="fn"><b>{{ $f['label'] }}</b><small>{{ $f['desc'] }}</small></span>
                </button>
            @endforeach
        </div>
        <div class="gl-now"><h2 id="glNow">Semua <small class="fw-muted" style="font-family:var(--fw-sans);font-size:13px">{{ $items->count() }}</small></h2><button type="button" id="glAll" hidden>Lihat semua</button></div>
    @endif

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
                <button type="button" class="gl-item {{ $shape }} {{ $platform === 'instagram' ? 'ig' : '' }}" data-album="{{ $g->album_key }}" data-type="{{ $isVideo ? 'video' : 'image' }}"
                        data-title="{{ $g->title }}" data-desc="{{ $g->category ?: \Illuminate\Support\Str::limit($g->description, 90) }}"
                        @if ($isVideo)
                            data-embed="{{ $g->embed_url }}" data-platform="{{ $platform }}" data-link="{{ $g->social_link[0] ?? '' }}" data-link-label="{{ $g->social_link[1] ?? '' }}" data-file="{{ $g->video_file_url }}" data-poster="{{ $g->image_url }}"
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
    var folders = document.querySelectorAll('#glFolders .gl-folder'), now = document.getElementById('glNow'), all = document.getElementById('glAll');
    function show(album, label) {
        var shown = 0;
        grid.querySelectorAll('.gl-item').forEach(function (it) {
            var ok = !album || it.dataset.album === album;
            it.hidden = !ok; if (ok) shown++;
        });
        folders.forEach(function (f) { var on = f.dataset.album === album; f.classList.toggle('on', on); f.setAttribute('aria-pressed', on ? 'true' : 'false'); });
        if (now) now.innerHTML = '';
        if (now) { now.appendChild(document.createTextNode((label || 'Semua') + ' ')); var c = document.createElement('small'); c.className = 'fw-muted'; c.style.cssText = 'font-family:var(--fw-sans);font-size:13px'; c.textContent = shown; now.appendChild(c); }
        if (all) all.hidden = !album;
        document.getElementById('glEmpty').hidden = shown > 0;
    }
    folders.forEach(function (f) {
        f.addEventListener('click', function () {
            var off = f.classList.contains('on');
            show(off ? '' : f.dataset.album, off ? '' : f.dataset.label);
            if (!off && window.innerWidth <= 820) now.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
    if (all) all.addEventListener('click', function () { show('', ''); });
    var start = new URLSearchParams(location.search).get('folder');
    if (start) { var sf = document.querySelector('#glFolders .gl-folder[data-album="' + start + '"]'); if (sf) show(start, sf.dataset.label); }

    var lb = document.getElementById('lb'), media = document.getElementById('lbMedia');
    function close() { lb.classList.remove('open'); media.innerHTML = ''; document.body.style.overflow = ''; }
    grid.querySelectorAll('.gl-item').forEach(function (it) {
        it.addEventListener('click', function () {
            media.innerHTML = '';
            if (it.dataset.platform === 'instagram' && it.dataset.link) {
                // Pemutar resmi Instagram (embed.js): ukuran menyesuaikan, video diputar di halaman ini.
                var box = document.createElement('div'); box.className = 'ig-box';
                var q = document.createElement('blockquote');
                q.className = 'instagram-media';
                q.setAttribute('data-instgrm-permalink', it.dataset.link.split('?')[0]);
                q.setAttribute('data-instgrm-version', '14');
                q.innerHTML = '<div class="ig-loading">Memuat video Instagram…</div>';
                box.appendChild(q); media.appendChild(box);
                var a = document.createElement('a');
                a.className = 'lb-open'; a.href = it.dataset.link; a.target = '_blank'; a.rel = 'noopener';
                a.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>Buka di Instagram';
                media.appendChild(a);
                if (window.instgrm && window.instgrm.Embeds) { window.instgrm.Embeds.process(); }
                else if (!document.getElementById('igEmbedJs')) {
                    var sc = document.createElement('script'); sc.id = 'igEmbedJs'; sc.async = true; sc.src = 'https://www.instagram.com/embed.js';
                    document.body.appendChild(sc);
                }
            } else if (it.dataset.type === 'video') {
                var wrap = document.createElement('div'); wrap.className = 'ratio' + (it.dataset.platform === 'instagram' ? ' portrait' : '');
                if (it.dataset.embed) {
                    var f = document.createElement('iframe');
                    // HP hanya mengizinkan autoplay tanpa suara; ketuk ikon speaker untuk suara.
                    f.src = it.dataset.embed + (it.dataset.embed.indexOf('?') === -1 ? '?' : '&') + 'autoplay=1&mute=1&playsinline=1&rel=0';
                    f.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share'; f.allowFullscreen = true;
                    f.setAttribute('scrolling', 'no');
                    wrap.appendChild(f);
                } else if (it.dataset.file) {
                    var v = document.createElement('video'); v.controls = true; v.autoplay = true; v.src = it.dataset.file;
                    if (it.dataset.poster) v.poster = it.dataset.poster;
                    v.playsInline = true;
                    // Video tegak (reel): kotak ikut menyesuaikan supaya tidak kecil.
                    v.addEventListener('loadedmetadata', function () {
                        if (v.videoHeight > v.videoWidth) {
                            wrap.style.aspectRatio = v.videoWidth + ' / ' + v.videoHeight;
                            wrap.style.width = 'min(100%, calc((100svh - 200px) * ' + (v.videoWidth / v.videoHeight).toFixed(4) + '))';
                            wrap.style.margin = '0 auto';
                        }
                    });
                    wrap.appendChild(v);
                    var pl = v.play(); if (pl && pl.catch) pl.catch(function () { v.muted = true; v.play().catch(function () {}); });
                }
                media.appendChild(wrap);
                if (it.dataset.link) {
                    var a = document.createElement('a');
                    a.className = 'lb-open'; a.href = it.dataset.link; a.target = '_blank'; a.rel = 'noopener';
                    a.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>';
                    a.appendChild(document.createTextNode('Lihat di ' + (it.dataset.linkLabel || 'Instagram')));
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
