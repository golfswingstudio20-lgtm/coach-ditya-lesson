{{-- Golf Analyzer: riwayat analisis + grafik perkembangan skor. --}}
@extends('layouts.fw')

@section('title', 'Riwayat Analisis Swing')
@section('no_footer', true)
@section('main_class', 'narrow')

@php
    use App\Support\Icons;
    use App\Models\SwingAnalysis;

    // Grafik garis sederhana (SVG) dari 10 skor terakhir.
    $W = 320; $H = 140; $pad = 18;
    $n = $trend->count();
    $pts = $trend->values()->map(function ($a, $i) use ($n, $W, $H, $pad) {
        $x = $n > 1 ? $pad + $i * ($W - 2 * $pad) / ($n - 1) : $W / 2;
        $y = $H - $pad - ((int) $a->score / 100) * ($H - 2 * $pad);
        return ['x' => round($x, 1), 'y' => round($y, 1), 's' => (int) $a->score];
    });
    $line = $pts->map(fn ($p) => $p['x'] . ',' . $p['y'])->implode(' ');
@endphp
@include('swing._style')

@section('content')
<div class="sw">
    <div class="sw-head">
        <a href="{{ route('swing.index') }}" class="sw-back" aria-label="Kembali">{!! Icons::svg('back') !!}</a>
        <h1 class="cdl-title">RIWAYAT<br>ANALISIS</h1>
    </div>

    @if ($n > 1)
        <section class="sw-card cdl-ink-card">
            <h2>Perkembangan Skor</h2>
            <svg viewBox="0 0 {{ $W }} {{ $H }}" style="width:100%;height:auto;background:#fff;border:2px solid #14141A;border-radius:14px" role="img" aria-label="Grafik skor swing">
                @foreach ([50, 80] as $g)
                    @php $gy = $H - $pad - $g / 100 * ($H - 2 * $pad); @endphp
                    <line x1="{{ $pad }}" x2="{{ $W - $pad }}" y1="{{ $gy }}" y2="{{ $gy }}" stroke="#14141A" stroke-opacity=".15" stroke-dasharray="4 4"/>
                    <text x="{{ $W - $pad }}" y="{{ $gy - 4 }}" font-size="10" text-anchor="end" fill="#14141A">{{ $g }}</text>
                @endforeach
                <polyline points="{{ $line }}" fill="none" stroke="#22A04B" stroke-width="3.5" stroke-linejoin="round" stroke-linecap="round"/>
                @foreach ($pts as $p)
                    <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="5" fill="#fff" stroke="#14141A" stroke-width="2"/>
                    <text x="{{ $p['x'] }}" y="{{ $p['y'] - 9 }}" font-size="10.5" font-weight="700" text-anchor="middle" fill="#14141A">{{ $p['s'] }}</text>
                @endforeach
            </svg>
        </section>
    @endif

    <section class="sw-card cdl-ink-card">
        <h2>Semua Analisis</h2>
        <div class="sw-list">
            @forelse ($items as $a)
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
                <p class="sw-empty">Belum ada analisis.</p>
            @endforelse
        </div>
        @if ($items->hasPages())<div>{{ $items->links() }}</div>@endif
    </section>

    <a href="{{ route('swing.create') }}" class="fw-btn block">{!! Icons::svg('upload') !!} Analisis Swing Baru</a>
</div>
@endsection
