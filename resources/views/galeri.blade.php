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
    $folders = collect(Gallery::albums())->filter(fn ($meta) => $meta[3])->map(function ($meta, $key) use ($items) {
        $list  = $items->filter(fn ($g) => $g->album_key === $key);
        $first = $list->first(fn ($g) => $g->type !== 'video' && $g->image_url);

        return [
            'key'   => $key,
            'label' => $meta[0],
            'desc'  => $meta[1],
            'kind'  => $meta[2],
            'count' => $list->count(),
            // Sampul: foto sampul folder dari admin, kalau kosong foto pertama di folder
            'cover' => $meta[4] ?: ($meta[2] === 'image' ? $first?->image_url : null),
        ];
    })->filter(fn ($f) => $f['count'] > 0)->values();
    // Item di folder yang disembunyikan admin juga tidak tampil
    $hiddenAlbums = collect(Gallery::albums())->reject(fn ($meta) => $meta[3])->keys()->all();
    $items = $items->reject(fn ($g) => in_array($g->album_key, $hiddenAlbums, true))->values();
    $folderIcons = ['latihan' => 'image', 'prestasi' => 'star', 'youtube' => 'play', 'instagram' => 'video'];
    $isCustomer = auth()->check() && auth()->user()->role === 'customer';
@endphp

@push('head')
<style>
    .gl-tabs { margin-bottom: 18px; }
    /* Folder: gaya "Sorotan" Instagram */
    .gl-hl { display: flex; gap: 18px; margin: 4px -24px 18px; padding: 4px 24px 6px; overflow-x: auto; scrollbar-width: none; }
    .gl-hl::-webkit-scrollbar { display: none; }
    .gl-hl button { flex: 0 0 auto; display: flex; flex-direction: column; align-items: center; gap: 7px; width: 78px; padding: 0; border: 0; background: none; color: var(--fw-text); font: inherit; cursor: pointer; }
    .gl-hl .ring { position: relative; width: 70px; height: 70px; padding: 3px; border-radius: 50%; background: var(--fw-line-2); transition: transform .15s; }
    .gl-hl button:hover .ring { transform: scale(1.04); }
    .gl-hl button.on .ring { background: linear-gradient(135deg, #cde8a3, #3d7d57); }
    .gl-hl button.on.k-instagram .ring { background: linear-gradient(135deg, #f9ce34, #ee2a7b 55%, #6228d7); }
    .gl-hl button.on.k-youtube .ring { background: linear-gradient(135deg, #ff4e45, #b31217); }
    .gl-hl .in { width: 100%; height: 100%; display: grid; place-items: center; overflow: hidden; border-radius: 50%; border: 3px solid var(--fw-bg); background: var(--fw-tint); color: var(--d-ink-green, var(--fw-green)); }
    .gl-hl .in { position: relative; }
    .gl-hl .in img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .gl-hl .in svg { width: 24px; height: 24px; }
    .gl-hl .k-instagram .in { background: linear-gradient(135deg, #f9ce34, #ee2a7b 55%, #6228d7); color: #fff; }
    .gl-hl .k-youtube .in { background: #c4302b; color: #fff; }
    .gl-hl .lbl { max-width: 78px; font-size: 12px; font-weight: 600; line-height: 1.2; text-align: center; }
    .gl-hl .lbl small { display: block; color: var(--fw-muted); font-weight: 500; font-size: 11px; }
    .gl-hl button.on .lbl { color: var(--d-ink-green, var(--fw-green)); }
    @media (min-width: 821px) { .gl-hl { margin: 4px 0 22px; padding: 4px 0 6px; gap: 26px; } .gl-hl button { width: 88px; } .gl-hl .ring { width: 80px; height: 80px; } }
    .gl-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
    .gl-item { aspect-ratio: 4 / 5; position: relative; padding: 0; border: 0; border-radius: 20px; overflow: hidden; background: linear-gradient(135deg, #1f4d33, #0c1f15); cursor: pointer; box-shadow: 0 10px 26px rgba(var(--d-shadow-rgb, 23, 46, 33), .14); text-align: left; font: inherit; }
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
    .gl-item.vid .gl-ph { display: none; }
    .gl-item .badge svg { width: 13px; height: 13px; }
    .gl-item .badge { display: inline-flex; align-items: center; gap: 5px; }
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
        .gl-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        .gl-item { border-radius: 18px; }
        .gl-item .cap strong { font-size: 13px; }
        .gl-item .cap small { display: none; }
        .gl-item .cap { left: 10px; right: 10px; bottom: 9px; }
        .gl-item .badge { top: 8px; left: 8px; }
        .gl-item .play { width: 40px; height: 40px; margin: -20px 0 0 -20px; }
        .gl-item .play svg { width: 16px; height: 16px; }
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
        <div class="gl-hl" id="glFolders" aria-label="Folder galeri">
            <button type="button" class="on k-all" data-album="" data-label="Semua" aria-pressed="true">
                <span class="ring"><span class="in">{!! Icons::svg('grid') !!}</span></span>
                <span class="lbl">Semua<small>{{ $items->count() }}</small></span>
            </button>
            @foreach ($folders as $f)
                <button type="button" class="k-{{ $f['key'] }}" data-album="{{ $f['key'] }}" data-label="{{ $f['label'] }}" aria-pressed="false">
                    <span class="ring"><span class="in">
                        {!! Icons::svg($folderIcons[$f['key']] ?? ($f['kind'] === 'video' ? 'video' : 'image')) !!}
                        @if ($f['cover'])<img src="{{ $f['cover'] }}" alt="" loading="lazy" onerror="this.remove()">@endif
                    </span></span>
                    <span class="lbl">{{ $f['label'] }}<small>{{ $f['count'] }}</small></span>
                </button>
            @endforeach
        </div>
    @endif

    @if ($items->isEmpty())
        <div class="fw-empty"><b>Galeri masih kosong</b>Foto & video akan segera ditambahkan.</div>
    @else
        <div class="gl-grid" id="glGrid">
            @foreach ($items as $g)
                @php
                    $isVideo = $g->type === 'video';
                    $thumb   = $isVideo ? $g->thumbnail_url : $g->image_url;
                    $platform = $g->video_platform;
                @endphp
                <button type="button" class="gl-item {{ $isVideo ? 'vid' : '' }} {{ $platform === 'instagram' ? 'ig' : '' }}" data-album="{{ $g->album_key }}" data-type="{{ $isVideo ? 'video' : 'image' }}"
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
                        <span class="badge">{!! Icons::svg($platform === 'youtube' ? 'play' : 'video') !!}{{ ['youtube' => 'YouTube', 'instagram' => 'Instagram'][$platform] ?? 'Video' }}</span>
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
    var folders = document.querySelectorAll('#glFolders button');
    function show(album) {
        var shown = 0;
        grid.querySelectorAll('.gl-item').forEach(function (it) {
            var ok = !album || it.dataset.album === album;
            it.hidden = !ok; if (ok) shown++;
        });
        folders.forEach(function (f) { var on = f.dataset.album === album; f.classList.toggle('on', on); f.setAttribute('aria-pressed', on ? 'true' : 'false'); });
        document.getElementById('glEmpty').hidden = shown > 0;
    }
    folders.forEach(function (f) { f.addEventListener('click', function () { show(f.dataset.album); }); });
    var start = new URLSearchParams(location.search).get('folder');
    if (start && document.querySelector('#glFolders button[data-album="' + start + '"]')) show(start);

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
