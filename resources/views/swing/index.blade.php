{{-- Golf Analyzer: halaman awal AI Swing Analysis. --}}
@extends('layouts.fw')

@section('title', 'AI Swing Analysis')
@section('no_footer', true)
@section('main_class', 'narrow')

@php use App\Support\Icons; use App\Models\SwingAnalysis; @endphp
@include('swing._style')

@section('content')
<div class="sw">
    <div class="sw-head">
        <a href="{{ route('dashboard') }}" class="sw-back" aria-label="Kembali">{!! Icons::svg('back') !!}</a>
        <h1 class="cdl-title">AI SWING<br>ANALYSIS</h1>
    </div>

    <section class="sw-card cdl-ink-card">
        <div class="sw-hero">
            <span class="ic">{!! Icons::svg('video') !!}</span>
            <div>
                <h2>Analisis Swing Dengan Teknologi AI</h2>
                <p>Upload video swing kamu, AI akan membaca gerakan tubuh dan memberi skor serta saran latihan.</p>
            </div>
        </div>
        <a href="{{ route('swing.create') }}" class="fw-btn block">{!! Icons::svg('upload') !!} Upload Video Swing</a>
        <p style="font-size:12.5px;text-align:center">{!! Icons::svg('lock', 'fw-ic-inline') !!} Video diproses di HP kamu dan tidak di-upload ke server.</p>
    </section>

    <div class="sw-stats">
        <div class="sw-stat cdl-ink-card"><b>{{ $total }}</b><span>Total Analisis</span></div>
        <div class="sw-stat cdl-ink-card"><b>{{ $total ? $best : '-' }}</b><span>Skor Terbaik</span></div>
    </div>

    <section class="sw-card cdl-ink-card">
        <h2>Tips Rekam Video</h2>
        <div class="sw-list">
            <div class="sw-item"><span class="num">1</span><span><strong>Gunakan kamera stabil</strong><small>Letakkan HP di tripod atau tempat datar, jangan dipegang.</small></span></div>
            <div class="sw-item"><span class="num">2</span><span><strong>Pastikan seluruh tubuh terlihat</strong><small>Kepala sampai kaki masuk frame, cahaya cukup terang.</small></span></div>
            <div class="sw-item"><span class="num">3</span><span><strong>Lakukan swing dengan normal</strong><small>Satu swing penuh, durasi video 2–20 detik.</small></span></div>
        </div>
    </section>

    <section class="sw-card cdl-ink-card">
        <div style="display:flex;justify-content:space-between;align-items:center">
            <h2>Analisis Terakhir</h2>
            @if ($total)<a href="{{ route('swing.history') }}" style="color:#17803A;font-weight:700;font-size:14px">Lihat Semua</a>@endif
        </div>
        @forelse ($recent as $a)
            @php $tone = SwingAnalysis::toneFor((int) $a->score); @endphp
            <a href="{{ route('swing.show', $a) }}" class="sw-item">
                <span class="sw-score {{ $tone }}">{{ $a->score }}</span>
                <span style="flex:1;min-width:0">
                    <strong>{{ $a->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</strong>
                    <small>{{ $a->view_label }}</small>
                </span>
                <span class="sw-tag {{ $tone }}">{{ $a->grade_label }}</span>
            </a>
        @empty
            <p class="sw-empty">Belum ada analisis. Yuk upload video swing pertamamu!</p>
        @endforelse
    </section>
</div>
@endsection
