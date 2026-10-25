{{-- Admin: detail satu hasil AI Swing Analysis + catatan coach. --}}
@extends('admin.layouts.panel')

@section('title', 'Hasil Analisis Swing')

@php
    use App\Models\SwingAnalysis;

    $score = (int) $analysis->score;
    $tone  = SwingAnalysis::toneFor($score);
    $color = ['good' => '#22A04B', 'mid' => '#E9A23B', 'low' => '#E8453C'][$tone];
@endphp

@push('styles')
    <style>
        .sa-grid { display: grid; grid-template-columns: 320px minmax(0, 1fr); gap: 16px; align-items: start; }
        .sa-ring { width: 130px; height: 130px; margin: 4px auto 12px; border-radius: 50%; border: 3px solid #14141A; display: grid; place-items: center; }
        .sa-ring b { width: 98px; height: 98px; border-radius: 50%; border: 3px solid #14141A; background: #fff; display: grid; place-items: center; text-align: center; color: #14141A; font-family: "Lilita One", system-ui, sans-serif; font-weight: 400; font-size: 36px; line-height: .9; }
        .sa-ring b small { display: block; font-family: "Fredoka", system-ui, sans-serif; font-size: 12px; }
        .sa-metric { padding: 10px 0; border-top: 1px solid var(--line); }
        .sa-metric:first-of-type { border-top: 0; }
        .sa-metric .top { display: flex; justify-content: space-between; gap: 10px; font-size: 13.5px; }
        .sa-metric .top strong { font-weight: 600; }
        .sa-bar { height: 9px; margin-top: 6px; border-radius: 99px; border: 1.5px solid #14141A; background: #fff; overflow: hidden; }
        .sa-bar i { display: block; height: 100%; background: #22A04B; }
        .sa-bar i.mid { background: #E9A23B; } .sa-bar i.low { background: #E8453C; }
        .sa-metric small { display: block; margin-top: 5px; color: var(--text-muted); font-size: 12px; }
        .sa-phases { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; }
        .sa-phase { border: 2px solid #14141A; border-radius: 14px; overflow: hidden; background: #fff; }
        .sa-phase .img { aspect-ratio: 3 / 4; background: #14141A center / cover no-repeat; display: grid; place-items: center; color: #9AA59E; font-size: 12px; }
        .sa-phase .txt { padding: 8px 9px; }
        .sa-phase .txt strong { display: flex; justify-content: space-between; gap: 6px; font-size: 12.5px; color: #14141A; }
        .sa-phase .txt small { display: block; margin-top: 3px; color: var(--text-muted); font-size: 11.5px; line-height: 1.3; }
        .sa-more summary { cursor: pointer; padding: 10px 12px; border: 2px solid #14141A; border-radius: 14px; background: #FFFFFF; font-weight: 600; list-style: none; }
        .sa-more summary::-webkit-details-marker { display: none; }
        .sa-more[open] summary { margin-bottom: 10px; background: #EAF8EF; }
        .sa-insight { padding: 12px 14px; border: 2px solid #14141A; border-radius: 14px; background: #FFE9B8; color: #14141A; font-size: 13.5px; line-height: 1.5; }
        .sa-tips { display: grid; gap: 8px; }
        .sa-tips div { padding: 10px 12px; border: 1.5px solid var(--border); border-radius: 12px; background: var(--d-surface-2, #f8f7f2); font-size: 13px; }
        .sa-tips strong { display: block; margin-bottom: 3px; }
        .sa-hist { display: flex; flex-wrap: wrap; gap: 6px; }
        .sa-hist a { min-width: 46px; padding: 6px 8px; border: 1.5px solid var(--border); border-radius: 10px; text-align: center; text-decoration: none; color: var(--text); font-size: 12px; background: var(--panel); }
        .sa-hist a b { display: block; font-size: 16px; }
        .sa-hist a.on { border-color: #14141A; background: #22A04B; color: #fff; }
        @media (max-width: 1100px) { .sa-phases { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 900px) { .sa-grid { grid-template-columns: 1fr; } }
        @media (max-width: 560px) { .sa-phases { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    </style>
@endpush

@section('content')
    <section class="page-head">
        <div>
            <h1>Hasil Analisis Swing</h1>
            <p>{{ $analysis->user->name ?? 'Customer' }} · {{ $analysis->created_at->locale('id')->translatedFormat('l, d M Y H:i') }} · {{ $analysis->view_label }} · {{ \App\Models\SwingAnalysis::HANDS[$analysis->handedness] ?? $analysis->handedness }}</p>
        </div>
        <div class="head-actions">
            <a href="{{ route('admin.swing.index') }}" class="btn btn-ghost">← Semua analisis</a>
            <a href="{{ route('admin.swing.create', ['user' => $analysis->user_id]) }}" class="btn btn-primary">{!! \App\Support\Icons::svg('video') !!} Analisis lagi</a>
            @if ($analysis->user)
                <a href="{{ route('admin.customers.show', $analysis->user) }}" class="btn btn-outline">Profil customer</a>
            @endif
        </div>
    </section>

    <div class="sa-grid">
        <div>
            <div class="profile-card" style="text-align:center">
                <div class="sa-ring" style="background: conic-gradient({{ $color }} {{ round($score * 3.6) }}deg, #fff 0)"><b>{{ $score }}<small>/100</small></b></div>
                <span class="status-pill {{ $tone === 'good' ? 'booked' : ($tone === 'mid' ? 'pending' : 'rejected') }}">{{ $analysis->grade_label }}</span>
                <p class="muted" style="margin-top:10px;font-size:12.5px">{{ $analysis->frames }} frame dianalisis · video {{ number_format($analysis->duration_ms / 1000, 1, ',', '') }} detik</p>
            </div>

            @if ($history->count() > 1)
                <div class="section-card" style="margin-top:16px">
                    <div class="section-card-head"><h2>Riwayat skor customer</h2></div>
                    <div class="sa-hist">
                        @foreach ($history->reverse() as $h)
                            <a href="{{ route('admin.swing.show', $h->id) }}" class="{{ $h->id === $analysis->id ? 'on' : '' }}"><b>{{ $h->score }}</b>{{ $h->created_at->format('d/m') }}</a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="section-card" style="margin-top:16px">
                <div class="section-card-head">
                    <div>
                        <h2>Catatan Coach</h2>
                        <p>Tampil di halaman hasil milik customer.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.swing.note', $analysis) }}">
                    @csrf
                    @method('PUT')
                    <div class="field">
                        <textarea name="coach_note" class="input" rows="6" style="height:150px" maxlength="1500" placeholder="Contoh: Backswing sudah bagus. Minggu ini fokus tahan posisi finish 3 detik, nanti kita latih di lesson berikutnya.">{{ old('coach_note', $analysis->coach_note) }}</textarea>
                        @error('coach_note')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%">Simpan catatan</button>
                </form>
            </div>
        </div>

        <div>
            <div style="margin-bottom:16px;display:grid;gap:16px">
                @include('swing._replay', [
                    'captureUrl'     => route('admin.swing.capture', $analysis),
                    'deleteVideoUrl' => route('admin.swing.video.destroy', $analysis),
                    'deleteUrl'      => route('admin.swing.destroy', $analysis),
                ])
            </div>

            @if ($analysis->insight)
                <div class="sa-insight" style="margin-bottom:16px"><b>Insight AI:</b> {{ $analysis->insight }}</div>
            @endif

            @if (! empty($analysis->phases))
                <div class="section-card">
                    <div class="section-card-head"><h2>Tahapan swing</h2></div>
                    <details class="sa-more">
                    <summary>Lihat gambar tahapan swing</summary>
                    <div class="sa-phases">
                        @foreach ($analysis->phases as $ph)
                            @php $img = SwingAnalysis::imageUrl($ph['image'] ?? null); @endphp
                            <div class="sa-phase">
                                <div class="img" @if ($img) style="background-image:url('{{ $img }}')" @endif>@unless ($img)<span>Tanpa gambar</span>@endunless</div>
                                <div class="txt">
                                    <strong><span>{{ $ph['name'] }}</span><span>{{ (int) $ph['score'] }}</span></strong>
                                    @if (! empty($ph['note']))<small>{{ $ph['note'] }}</small>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    </details>
                </div>
            @endif

            @if (! empty($analysis->captures))
                <div class="section-card">
                    <div class="section-card-head"><h2>Capture dari Studio Video</h2></div>
                    <details class="sa-more">
                    <summary>Lihat {{ count($analysis->captures) }} capture</summary>
                    <div class="sa-phases" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr))">
                        @foreach ($analysis->captures as $cap)
                            @php $cimg = SwingAnalysis::imageUrl($cap['image'] ?? null); @endphp
                            @if ($cimg)
                                <a href="{{ $cimg }}" target="_blank" rel="noopener" class="sa-phase" style="display:block"><img src="{{ $cimg }}" alt="Capture swing" style="width:100%;display:block" loading="lazy"></a>
                            @endif
                        @endforeach
                    </div>
                    </details>
                </div>
            @endif

            <div class="section-card">
                <div class="section-card-head"><h2>Detail penilaian</h2></div>
                @foreach ($analysis->metrics ?? [] as $m)
                    @php $mt = SwingAnalysis::toneFor((int) $m['score']); @endphp
                    <div class="sa-metric">
                        <div class="top">
                            <strong>{{ $m['label'] }}@if (isset($m['value']) && $m['value'] !== null && ! empty($m['unit'])) <span class="muted" style="font-weight:500">· {{ rtrim(rtrim(number_format((float) $m['value'], 1, ',', ''), '0'), ',') }}{{ $m['unit'] }}</span>@endif</strong>
                            <span>{{ (int) $m['score'] }}/100</span>
                        </div>
                        <div class="sa-bar"><i class="{{ $mt }}" style="width: {{ (int) $m['score'] }}%"></i></div>
                        @if (! empty($m['note']))<small>{{ $m['note'] }}</small>@endif
                    </div>
                @endforeach
            </div>

            @if (! empty($analysis->tips))
                <div class="section-card">
                    <div class="section-card-head"><h2>Saran latihan dari AI</h2></div>
                    <div class="sa-tips">
                        @foreach ($analysis->tips as $tip)
                            <div><strong>{{ $tip['title'] }}</strong>{{ $tip['text'] }}</div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
