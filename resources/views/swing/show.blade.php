{{-- Golf Analyzer: hasil satu analisis swing. --}}
@extends('layouts.fw')

@section('title', 'Hasil Analisis Swing')
@section('no_footer', true)
@section('main_class', 'narrow')

@php
    use App\Support\Icons;
    use App\Models\SwingAnalysis;

    $score = (int) $analysis->score;
    $tone  = SwingAnalysis::toneFor($score);
    $deg   = round($score * 3.6);
    $color = ['good' => '#22A04B', 'mid' => '#E9A23B', 'low' => '#E8453C'][$tone];
    $diff  = $previous ? $score - (int) $previous->score : null;
    $isOwner = auth()->id() === $analysis->user_id;
@endphp
@include('swing._style')

@push('head')
<style>
    .sw-result { display: flex; align-items: center; gap: 16px; }
    .sw-big { width: 120px; height: 120px; flex: 0 0 120px; border-radius: 50%; border: 3px solid #14141A; display: grid; place-items: center; }
    .sw-big b { width: 90px; height: 90px; border-radius: 50%; border: 3px solid #14141A; background: #fff; display: grid; place-items: center; text-align: center; font-family: "Lilita One", system-ui, sans-serif; font-weight: 400; font-size: 34px; line-height: .9; }
    .sw-big b small { display: block; font-family: "Fredoka", system-ui, sans-serif; font-size: 12px; color: #14141A; }
    .sw-diff { font-size: 13px; font-weight: 700; }
    .sw-diff.up, .sw-diff.down { color: #14141A; }
    .sw-bar { height: 10px; border-radius: 99px; border: 2px solid #14141A; background: #fff; overflow: hidden; margin-top: 6px; }
    .sw-bar i { display: block; height: 100%; background: #22A04B; }
    .sw-bar i.mid { background: #E9A23B; } .sw-bar i.low { background: #E8453C; }
    .sw-metric { padding: 10px 12px; border: 2px solid rgba(20, 20, 26, .2); border-radius: 16px; background: #EAF8EF; }
    .sw-metric .top { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
    .sw-metric strong { font-size: 15px; }
    .sw-metric small { display: block; margin-top: 6px; color: #14141A; font-size: 13px; }
    .sw-phases { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    .sw-phase { border: 2.5px solid #14141A; border-radius: 16px; overflow: hidden; background: #fff; }
    .sw-phase .img { aspect-ratio: 3 / 4; background: #14141A center / cover no-repeat; display: grid; place-items: center; color: #9AA59E; }
    .sw-phase .img svg { width: 32px; height: 32px; }
    .sw-phase .txt { padding: 8px 10px; display: grid; gap: 3px; }
    .sw-phase .txt strong { font-size: 13.5px; display: flex; justify-content: space-between; gap: 6px; }
    .sw-phase .txt small { font-size: 12px; color: #14141A; line-height: 1.3; }
    .sw-insight { background: #FFE9B8; }
    .sw-coach { background: #EAF8EF; }
</style>
@endpush

@section('content')
<div class="sw">
    <div class="sw-head">
        <a href="{{ $isOwner ? route('swing.index') : url()->previous() }}" class="sw-back" aria-label="Kembali">{!! Icons::svg('back') !!}</a>
        <h1 class="cdl-title">HASIL<br>ANALISIS</h1>
    </div>

    <section class="sw-card cdl-ink-card">
        <div class="sw-result">
            <span class="sw-big" style="background: conic-gradient({{ $color }} {{ $deg }}deg, #fff 0)">
                <b>{{ $score }}<small>/100</small></b>
            </span>
            <div style="display:grid;gap:6px">
                <span class="sw-tag {{ $tone }}" style="justify-self:start">{{ $analysis->grade_label }}</span>
                <h2>Skor Swing</h2>
                <p style="font-size:13px">{{ $analysis->created_at->locale('id')->translatedFormat('d M Y, H:i') }} · {{ $analysis->view_label }}</p>
                @if ($diff !== null)
                    <span class="sw-diff {{ $diff >= 0 ? 'up' : 'down' }}">{{ $diff >= 0 ? '▲ +' . $diff : '▼ ' . $diff }} dari analisis sebelumnya</span>
                @endif
            </div>
        </div>
    </section>

    @if ($analysis->insight)
        <section class="sw-card cdl-ink-card sw-insight">
            <h2>{!! Icons::svg('info', 'fw-ic-inline') !!} Insight AI</h2>
            <p>{{ $analysis->insight }}</p>
        </section>
    @endif

    <section class="sw-card cdl-ink-card">
        <h2>Detail Penilaian</h2>
        @foreach ($analysis->metrics ?? [] as $m)
            @php $mt = SwingAnalysis::toneFor((int) $m['score']); @endphp
            <div class="sw-metric">
                <div class="top">
                    <strong>{{ $m['label'] }}@if (isset($m['value']) && $m['value'] !== null && $m['unit']) <span style="font-weight:500;color:#14141A">· {{ rtrim(rtrim(number_format((float) $m['value'], 1, ',', ''), '0'), ',') }}{{ $m['unit'] }}</span>@endif</strong>
                    <span class="sw-tag {{ $mt }}">{{ $m['score'] >= 80 ? 'Baik' : 'Perlu Evaluasi' }}</span>
                </div>
                <div class="sw-bar"><i class="{{ $mt }}" style="width: {{ (int) $m['score'] }}%"></i></div>
                @if (! empty($m['note']))<small>{{ $m['note'] }}</small>@endif
            </div>
        @endforeach
    </section>

    @if (! empty($analysis->phases))
        <section class="sw-card cdl-ink-card">
            <h2>Tahapan Swing</h2>
            <div class="sw-phases">
                @foreach ($analysis->phases as $ph)
                    @php $img = SwingAnalysis::imageUrl($ph['image'] ?? null); @endphp
                    <div class="sw-phase">
                        <div class="img" @if ($img) style="background-image:url('{{ $img }}')" @endif>@unless ($img){!! Icons::svg('image') !!}@endunless</div>
                        <div class="txt">
                            <strong><span>{{ $ph['name'] }}</span><span>{{ (int) $ph['score'] }}</span></strong>
                            @if (! empty($ph['note']))<small>{{ $ph['note'] }}</small>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if (! empty($analysis->tips))
        <section class="sw-card cdl-ink-card">
            <h2>Rekomendasi Latihan</h2>
            <div class="sw-list">
                @foreach ($analysis->tips as $i => $tip)
                    <div class="sw-item"><span class="num">{{ $i + 1 }}</span><span><strong>{{ $tip['title'] }}</strong><small>{{ $tip['text'] }}</small></span></div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($analysis->coach_note)
        <section class="sw-card cdl-ink-card sw-coach">
            <h2>{!! Icons::svg('coach', 'fw-ic-inline') !!} Catatan Coach</h2>
            <p>{{ $analysis->coach_note }}</p>
        </section>
    @endif

    @if ($isOwner)
        <div class="sw-actions">
            <a href="{{ route('swing.create') }}" class="fw-btn">Analisis Lagi</a>
            <a href="{{ route('swing.history') }}" class="fw-btn ghost">Riwayat</a>
        </div>
        <a href="{{ route('booking') }}" class="fw-btn block dark">Booking Lesson dengan Coach</a>
    @endif

    <p style="font-size:12px;text-align:center;color:#14141A">Hasil AI adalah perkiraan dari video 2D. Untuk evaluasi lengkap, latihan langsung bersama Coach Ditya.</p>
</div>
@endsection
