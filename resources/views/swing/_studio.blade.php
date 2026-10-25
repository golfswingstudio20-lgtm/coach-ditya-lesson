{{-- Studio Video Golf Analyzer. $mode: 'create' (setelah analisis) atau 'replay' (putar ulang dari halaman hasil). --}}
@php use App\Support\Icons; $mode = $mode ?? 'create'; @endphp
<style>
    .st[hidden] { display: none !important; }
    .st { display: grid; gap: 12px; padding: 16px; background: #DDF3E4; border: 2.5px solid #14141A; border-radius: 20px; box-shadow: 3px 3px 0 #14141A; color: #14141A; }
    .st h2 { font-family: "Lilita One", system-ui, sans-serif; font-weight: 400; font-size: 21px; letter-spacing: .01em; }
    .st-sub { font-size: 13.5px; font-weight: 500; }
    .st-stage { position: relative; margin: 0 auto; max-width: 100%; border: 2.5px solid #14141A; border-radius: 16px; overflow: hidden; background: #000; touch-action: none; }
    .st-stage video, .st-stage canvas { position: absolute; inset: 0; width: 100%; height: 100%; display: block; }
    .st-stage video { object-fit: fill; }
    .st-stage canvas { cursor: pointer; }
    .st-bar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .st-bar input[type=range] { flex: 1 1 160px; accent-color: #22A04B; }
    .st-time { font-size: 12.5px; font-weight: 600; min-width: 110px; text-align: right; }
    .st-btn { height: 38px; padding: 0 12px; display: inline-flex; align-items: center; gap: 6px; border: 2px solid #14141A; border-radius: 12px; background: #FFFFFF; color: #14141A; font: inherit; font-size: 13px; font-weight: 600; cursor: pointer; box-shadow: 2px 2px 0 #14141A; }
    .st-btn svg { width: 17px; height: 17px; }
    .st-btn.on { background: #22A04B; color: #FFFFFF; }
    .st-btn.on svg.cdl-ic { padding: 2px; border-radius: 50%; background: #DDF3E4; box-shadow: 0 0 0 1.5px #14141A; }
    .st-btn.big { height: 44px; padding: 0 16px; font-size: 14px; }
    .st-btn.primary { background: #22A04B; color: #FFFFFF; }
    .st-btn:disabled { opacity: .6; cursor: wait; }
    .st-group { display: grid; gap: 6px; }
    .st-group > b { font-size: 12.5px; letter-spacing: .03em; }
    .st-msg { padding: 9px 12px; border: 2px solid #14141A; border-radius: 12px; background: #FFE9B8; font-size: 13px; font-weight: 600; }
    .st-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; }
    .st-cap { display: grid; gap: 4px; padding: 6px; border: 2px solid #14141A; border-radius: 12px; background: #FFFFFF; font-size: 12px; font-weight: 600; }
    .st-cap img { width: 100%; border-radius: 8px; display: block; }
    .st-cap a { color: #14141A; text-decoration: underline; }
    .st-foot { display: flex; gap: 10px; flex-wrap: wrap; }
    .st-foot a { text-decoration: none; }
    .st-btn[hidden] { display: none !important; }
    .st-status { padding: 8px 12px; border: 2px dashed #14141A; border-radius: 12px; background: #FFFFFF; font-size: 12.5px; font-weight: 600; }
</style>

<section class="st" data-studio hidden>
    <div>
        <h2>Studio Video Swing</h2>
        <p class="st-sub">Putar pelan, jeda, nyalakan garis yang dibutuhkan, gambar sendiri, lalu capture.</p>
    </div>

    <p class="st-status" id="st-status" hidden></p>

    <div class="st-stage" id="st-stage">
        <video id="st-video" playsinline muted preload="auto"></video>
        <canvas id="st-canvas"></canvas>
    </div>

    <div class="st-bar">
        <button type="button" class="st-btn" id="st-prev" title="Mundur 1 frame">◀︎ 1</button>
        <button type="button" class="st-btn big primary" id="st-play">▶ Putar</button>
        <button type="button" class="st-btn" id="st-next" title="Maju 1 frame">1 ▶︎</button>
        <input type="range" id="st-scrub" min="0" max="1" step="0.001" value="0" aria-label="Posisi video">
        <span class="st-time" id="st-time">0.00 s</span>
    </div>

    <div class="st-group">
        <b>KECEPATAN</b>
        <div class="st-bar">
            <button type="button" class="st-btn" data-speed="0.25">0.25x</button>
            <button type="button" class="st-btn on" data-speed="0.5">0.5x</button>
            <button type="button" class="st-btn" data-speed="1">1x Normal</button>
        </div>
    </div>

    <div class="st-group">
        <b>LOMPAT KE TAHAPAN</b>
        <div class="st-bar">
            <button type="button" class="st-btn" data-jump="setup">Setup</button>
            <button type="button" class="st-btn" data-jump="backswing">Backswing</button>
            <button type="button" class="st-btn" data-jump="top">Top</button>
            <button type="button" class="st-btn" data-jump="impact">Impact</button>
            <button type="button" class="st-btn" data-jump="finish">Finish</button>
        </div>
    </div>

    <div class="st-group">
        <b>GARIS (KLIK UNTUK MENYALAKAN)</b>
        <div class="st-bar">
            <button type="button" class="st-btn" data-show="coach" data-needs-pose>{!! Icons::svg('flag') !!} Garis Coach</button>
            <button type="button" class="st-btn" data-show="skel" data-needs-pose>{!! Icons::svg('user') !!} Rangka</button>
            <button type="button" class="st-btn" data-show="angles" data-needs-pose>{!! Icons::svg('target') !!} Sudut tubuh</button>
            <button type="button" class="st-btn" data-show="path" data-needs-pose>{!! Icons::svg('refresh') !!} Jejak tangan</button>
        </div>
    </div>

    <div class="st-group">
        <b>SHOT TRACER (JEJAK BOLA)</b>
        <div class="st-bar">
            <button type="button" class="st-btn" id="st-trace-auto">{!! Icons::svg('golf') !!} Tracer otomatis</button>
            <button type="button" class="st-btn" data-tool="trace-end">{!! Icons::svg('pin') !!} Tandai ujung bola</button>
            <button type="button" class="st-btn" data-tool="trace-apex">{!! Icons::svg('arrow') !!} Tandai puncak</button>
            <button type="button" class="st-btn" data-tool="ball">{!! Icons::svg('target') !!} Posisi bola</button>
            <button type="button" class="st-btn" data-show="tracer">{!! Icons::svg('eye') !!} Tampilkan</button>
            <button type="button" class="st-btn" id="st-trace-clear">{!! Icons::svg('trash') !!} Hapus tracer</button>
        </div>
    </div>

    <div class="st-group">
        <b>GAMBAR SAAT DIJEDA</b>
        <div class="st-bar">
            <button type="button" class="st-btn" data-tool="line">{!! Icons::svg('edit') !!} Garis</button>
            <button type="button" class="st-btn" data-tool="angle">{!! Icons::svg('target') !!} Sudut 3 titik</button>
            <button type="button" class="st-btn" data-tool="circle">{!! Icons::svg('plus-c') !!} Lingkaran</button>
            <button type="button" class="st-btn" id="st-undo">↶ Batal</button>
            <button type="button" class="st-btn" id="st-clear">{!! Icons::svg('trash') !!} Hapus gambar</button>
        </div>
    </div>

    <p class="st-msg" id="st-msg" hidden></p>

    <div class="st-foot">
        <button type="button" class="st-btn big primary" id="st-capture">{!! Icons::svg('image') !!} Capture</button>
        @if ($mode === 'create')
            <a href="#" class="st-btn big" id="st-result">{!! Icons::svg('star') !!} Lihat Hasil Lengkap</a>
            <a href="{{ url()->current() }}" class="st-btn big">{!! Icons::svg('video') !!} Analisis Video Lain</a>
        @endif
    </div>

    <div class="st-gallery" id="st-gallery" hidden></div>
</section>
