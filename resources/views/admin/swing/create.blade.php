{{-- Admin: coach menjalankan Golf Analyzer untuk customer (atau dirinya sendiri). --}}
@extends('admin.layouts.panel')

@section('title', 'Analisis Swing Baru')

@php use App\Support\Icons; @endphp

@push('styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .sw [hidden] { display: none !important; }
        .sw { display: grid; gap: 14px; max-width: 760px; }
        .sw-card { padding: 18px; display: grid; gap: 12px; }
        .sw-card h2 { font-size: 20px; }
        .sw-card p { font-size: 14px; line-height: 1.5; color: #14141A; }
        .sw-steps { display: flex; gap: 8px; }
        .sw-steps span { flex: 1; padding: 8px 4px; text-align: center; border: 2px solid #14141A; border-radius: 12px; background: #fff; font-size: 13px; font-weight: 700; color: #14141A; }
        .sw-steps span.on { background: #22A04B; color: #fff; }
        .sw-drop { display: grid; place-items: center; gap: 8px; padding: 26px 14px; border: 2.5px dashed #14141A; border-radius: 18px; background: #EAF8EF; text-align: center; cursor: pointer; color: #14141A; }
        .sw-drop svg { width: 44px; height: 44px; }
        .sw-drop strong { font-size: 16px; }
        .sw-drop small { font-size: 13px; }
        .sw-pick { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .sw-pick label.btn { cursor: pointer; height: 46px; }
        .sw-pick input { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
        .sw-video { width: 100%; max-height: 380px; border-radius: 16px; border: 2.5px solid #14141A; background: #000; }
        .sw-file { font-size: 12.5px; color: #14141A; text-align: center; word-break: break-all; }
        .sw-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .sw-err { padding: 10px 12px; border: 2.5px solid #14141A; border-radius: 14px; background: #FFC7C2; color: #14141A; font-size: 14px; font-weight: 600; }
        .sw-ring { --p: 0; width: 150px; height: 150px; margin: 6px auto; border-radius: 50%; display: grid; place-items: center; background: conic-gradient(#22A04B calc(var(--p) * 1%), #fff 0); border: 3px solid #14141A; transition: background .3s; }
        .sw-ring b { width: 112px; height: 112px; display: grid; place-items: center; border-radius: 50%; border: 3px solid #14141A; background: #DDF3E4; font-family: "Lilita One", system-ui, sans-serif; font-weight: 400; font-size: 32px; color: #14141A; }
        .sw-status { text-align: center; font-weight: 600; }
        .sw-chk { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 12px; font-size: 14px; font-weight: 600; color: #14141A; }
        .sw-chk i { width: 22px; height: 22px; flex: 0 0 22px; border-radius: 50%; border: 2px solid #9AA59E; display: grid; place-items: center; font-style: normal; font-size: 12px; }
        .sw-chk.now { background: #EAF8EF; }
        .sw-chk.now i { border-color: #22A04B; border-top-color: transparent; animation: sw-spin .8s linear infinite; }
        .sw-chk.done i { border-color: #14141A; background: #22A04B; color: #fff; }
        .sw-chk.done i::before { content: "✓"; }
        @keyframes sw-spin { to { transform: rotate(360deg); } }
        .sw-note { font-size: 12.5px !important; text-align: center; }
        .sw-note svg { width: 14px; height: 14px; vertical-align: -2px; }
        @media (max-width: 640px) { .sw-row { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
    <section class="page-head">
        <div>
            <h1>Analisis Swing Baru</h1>
            <p>Rekam atau pilih video swing, lalu AI menilai gerakannya. Hasilnya masuk ke riwayat customer yang dipilih dan bisa langsung diberi catatan coach.</p>
        </div>
        <div class="head-actions">
            <a href="{{ route('admin.swing.index') }}" class="btn btn-ghost">← Semua analisis</a>
        </div>
    </section>

    <div class="sw" data-swing-app data-store-url="{{ route('admin.swing.store') }}">
        <div class="sw-steps">
            <span class="on" data-step="1">1. Video</span>
            <span data-step="2">2. Analisis</span>
            <span data-step="3">3. Hasil</span>
        </div>

        <p class="sw-err" id="sw-error" hidden></p>

        <section class="sw-card section-card" id="sw-step-upload">
            <div class="field" style="margin:0">
                <label for="sw-user">Analisis untuk</label>
                <select id="sw-user" class="input">
                    <option value="">Coach sendiri (tanpa customer)</option>
                    @foreach ($customers as $cu)
                        <option value="{{ $cu->id }}" @selected($selected === $cu->id)>{{ $cu->name }} · {{ $cu->email }}</option>
                    @endforeach
                </select>
            </div>

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
                <label class="btn btn-ghost">{!! Icons::svg('upload') !!} Pilih file<input type="file" id="sw-file" accept="video/*"></label>
                <label class="btn btn-ghost">{!! Icons::svg('video') !!} Rekam<input type="file" id="sw-record" accept="video/*" capture="environment"></label>
            </div>

            <div class="sw-row">
                <div class="field" style="margin:0">
                    <label for="sw-view">Posisi kamera</label>
                    <select id="sw-view" class="input">
                        @foreach ($views as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div class="field" style="margin:0">
                    <label for="sw-hand">Tangan pemain</label>
                    <select id="sw-hand" class="input">
                        @foreach ($hands as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </select>
                </div>
            </div>

            <label style="display:flex;gap:10px;align-items:center;font-size:14px;font-weight:600;color:#14141A;cursor:pointer"><input type="checkbox" id="sw-keep" checked style="width:20px;height:20px;accent-color:#22A04B"> Simpan video supaya bisa diputar ulang (maks 50 MB)</label>
            <button type="button" id="sw-start" class="btn btn-primary" style="height:50px" disabled>Mulai Analisis</button>
            <p class="sw-note">{!! Icons::svg('lock') !!} Video diproses di perangkat ini dan tidak di-upload. Yang disimpan hanya skor, saran, dan gambar kecil tiap tahapan.</p>
        </section>

        <section class="sw-card section-card" id="sw-step-process" hidden>
            <h2 style="text-align:center">AI Sedang Menganalisis</h2>
            <div class="sw-ring" id="sw-ring"><b id="sw-pct">0%</b></div>
            <p class="sw-status" id="sw-status">Menyiapkan…</p>
            <div>
                <div class="sw-chk" data-chk="1"><i></i>Mendeteksi pose tubuh</div>
                <div class="sw-chk" data-chk="2"><i></i>Menganalisis gerakan swing</div>
                <div class="sw-chk" data-chk="3"><i></i>Menghitung sudut dan posisi</div>
                <div class="sw-chk" data-chk="4"><i></i>Menyiapkan hasil analisis</div>
            </div>
            <p class="sw-note">Jangan tutup halaman ini. Proses sekitar 20–60 detik.</p>
        </section>

        @include('swing._studio')

        <canvas id="sw-canvas" hidden></canvas>
    </div>
@endsection

@push('scripts')
<script>
    document.getElementById('sw-empty').addEventListener('click', function (e) { e.preventDefault(); document.getElementById('sw-file').click(); });
</script>
<script type="module" src="{{ asset('js/swing-analyzer.js') }}?v={{ @filemtime(public_path('js/swing-analyzer.js')) }}"></script>
@endpush
