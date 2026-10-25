{{-- Golf Analyzer: pilih video lalu analisis di browser (MediaPipe Pose). --}}
@extends('layouts.fw')

@section('title', 'Analisis Swing Baru')
@section('no_footer', true)
@section('main_class', 'narrow')

@php use App\Support\Icons; @endphp
@include('swing._style')

@push('head')
<style>
    .sw-steps { display: flex; gap: 8px; }
    .sw-steps span { flex: 1; padding: 8px 4px; text-align: center; border: 2px solid #14141A; border-radius: 12px; background: #fff; font-size: 12.5px; font-weight: 700; color: #3F4A44; }
    .sw-steps span.on { background: #22A04B; color: #fff; }
    .sw-drop { display: grid; place-items: center; gap: 8px; padding: 26px 14px; border: 2.5px dashed #14141A; border-radius: 18px; background: #EAF8EF; text-align: center; cursor: pointer; }
    .sw-drop svg { width: 40px; height: 40px; color: #17803A; }
    .sw-drop strong { font-size: 16px; }
    .sw-drop small { color: #3F4A44; font-size: 13px; }
    .sw-pick { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    .sw-pick label.fw-btn { cursor: pointer; }
    .sw-pick input, .sw-drop input { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
    .sw-video { width: 100%; max-height: 360px; border-radius: 16px; border: 2.5px solid #14141A; background: #000; }
    .sw-file { font-size: 12.5px; color: #3F4A44; text-align: center; word-break: break-all; }
    .sw-field { display: grid; gap: 6px; }
    .sw-field span { font-weight: 700; font-size: 14px; }
    .sw-err { padding: 10px 12px; border: 2.5px solid #14141A; border-radius: 14px; background: #FFC7C2; font-size: 14px; font-weight: 600; }
    .sw-ring { --p: 0; width: 150px; height: 150px; margin: 6px auto; border-radius: 50%; display: grid; place-items: center;
        background: conic-gradient(#22A04B calc(var(--p) * 1%), #fff 0); border: 3px solid #14141A; transition: background .3s; }
    .sw-ring b { width: 112px; height: 112px; display: grid; place-items: center; border-radius: 50%; border: 3px solid #14141A; background: #DDF3E4; font-family: "Lilita One", system-ui, sans-serif; font-weight: 400; font-size: 32px; }
    .sw-status { text-align: center; font-weight: 600; }
    .sw-chk { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 12px; font-size: 14px; font-weight: 600; color: #6B766F; }
    .sw-chk i { width: 22px; height: 22px; flex: 0 0 22px; border-radius: 50%; border: 2px solid #9AA59E; display: grid; place-items: center; font-style: normal; font-size: 12px; }
    .sw-chk.now { color: #14141A; background: #EAF8EF; }
    .sw-chk.now i { border-color: #22A04B; border-top-color: transparent; animation: sw-spin .8s linear infinite; }
    .sw-chk.done { color: #14141A; }
    .sw-chk.done i { border-color: #14141A; background: #22A04B; color: #fff; }
    .sw-chk.done i::before { content: "✓"; }
    @keyframes sw-spin { to { transform: rotate(360deg); } }
</style>
@endpush

@section('content')
<div class="sw" data-swing-app data-store-url="{{ route('swing.store') }}">
    <div class="sw-head">
        <a href="{{ route('swing.index') }}" class="sw-back" aria-label="Kembali">{!! Icons::svg('back') !!}</a>
        <h1 class="cdl-title">ANALISIS<br>SWING</h1>
    </div>

    <div class="sw-steps">
        <span class="on" data-step="1">1. Upload</span>
        <span data-step="2">2. Analisis</span>
        <span data-step="3">3. Hasil</span>
    </div>

    <p class="sw-err" id="sw-error" hidden></p>

    <section class="sw-card cdl-ink-card" id="sw-step-upload">
        <label class="sw-drop" id="sw-empty">
            {!! Icons::svg('video') !!}
            <strong>Pilih Video Swing</strong>
            <small>MP4 / MOV, durasi 2–20 detik, seluruh badan terlihat</small>
        </label>

        <div id="sw-preview" hidden style="display:grid;gap:8px">
            <video id="sw-video" class="sw-video" controls playsinline muted preload="auto"></video>
            <p class="sw-file" id="sw-fileinfo"></p>
        </div>

        <div class="sw-pick">
            <label class="fw-btn ghost">{!! Icons::svg('upload') !!} Galeri<input type="file" id="sw-file" accept="video/*"></label>
            <label class="fw-btn ghost">{!! Icons::svg('video') !!} Rekam<input type="file" id="sw-record" accept="video/*" capture="environment"></label>
        </div>

        <label class="sw-field">
            <span>Posisi kamera</span>
            <select id="sw-view" class="fw-input">
                @foreach ($views as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
            </select>
        </label>
        <label class="sw-field">
            <span>Tangan</span>
            <select id="sw-hand" class="fw-input">
                @foreach ($hands as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
            </select>
        </label>

        <button type="button" id="sw-start" class="fw-btn block" disabled>Mulai Analisis</button>
        <p style="font-size:12.5px;text-align:center">{!! Icons::svg('lock', 'fw-ic-inline') !!} Video tetap di perangkat kamu. Yang disimpan hanya skor, saran, dan gambar kecil tiap tahapan.</p>
    </section>

    <section class="sw-card cdl-ink-card" id="sw-step-process" hidden>
        <h2 style="text-align:center">AI Sedang Menganalisis</h2>
        <div class="sw-ring" id="sw-ring"><b id="sw-pct">0%</b></div>
        <p class="sw-status" id="sw-status">Menyiapkan…</p>
        <div>
            <div class="sw-chk" data-chk="1"><i></i>Mendeteksi pose tubuh</div>
            <div class="sw-chk" data-chk="2"><i></i>Menganalisis gerakan swing</div>
            <div class="sw-chk" data-chk="3"><i></i>Menghitung sudut dan posisi</div>
            <div class="sw-chk" data-chk="4"><i></i>Menyiapkan hasil analisis</div>
        </div>
        <p style="font-size:12.5px;text-align:center">Jangan tutup halaman ini. Proses sekitar 20–60 detik.</p>
    </section>

    <canvas id="sw-canvas" hidden></canvas>
</div>
@endsection

@push('scripts')
<script>
    // Tombol area kosong membuka pilihan galeri.
    document.getElementById('sw-empty').addEventListener('click', function (e) { e.preventDefault(); document.getElementById('sw-file').click(); });
</script>
<script type="module" src="{{ asset('js/swing-analyzer.js') }}?v={{ @filemtime(public_path('js/swing-analyzer.js')) }}"></script>
@endpush
