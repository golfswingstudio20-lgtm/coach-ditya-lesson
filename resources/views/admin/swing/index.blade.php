{{-- Admin: daftar semua hasil AI Swing Analysis customer. --}}
@extends('admin.layouts.panel')

@section('title', 'AI Swing Analysis')

@push('styles')
    <style>
        .sw-score { width: 46px; height: 46px; display: inline-grid; place-items: center; border-radius: 50%; border: 4px solid #22A04B; background: #fff; color: #14141A; font-family: "Lilita One", system-ui, sans-serif; font-size: 17px; }
        .sw-score.mid { border-color: #E9A23B; }
        .sw-score.low { border-color: #E8453C; }
        .toolbar.sw { grid-template-columns: minmax(0, 1fr) 170px 170px auto; }
        .note-yes { color: #14141A; font-weight: 600; }
        .note-no { color: #14141A; font-weight: 600; }
        @media (max-width: 760px) {
            .toolbar.sw { grid-template-columns: 1fr 1fr; }
            .toolbar.sw .input:first-child { grid-column: 1 / -1; }
        }
    </style>
@endpush

@section('content')
    @php use App\Models\SwingAnalysis; @endphp

    <section class="page-head">
        <div>
            <h1>AI Swing Analysis</h1>
            <p>Hasil analisis swing customer dari Golf Analyzer. Buka hasilnya untuk melihat detail dan menambahkan catatan coach.</p>
        </div>
    </section>

    <section class="stat-row">
        <div class="stat-box"><span>Total analisis</span><strong>{{ $stats['total'] }}</strong><small>dari {{ $stats['players'] }} customer</small></div>
        <div class="stat-box"><span>Rata-rata skor</span><strong>{{ $stats['total'] ? $stats['avg'] : '—' }}</strong><small>dari 100</small></div>
        <div class="stat-box"><span>Belum ada catatan</span><strong>{{ $stats['no_note'] }}</strong><small>perlu dicek coach</small></div>
        <div class="stat-box"><span>Customer aktif</span><strong>{{ $stats['players'] }}</strong><small>pernah upload video</small></div>
    </section>

    <form method="GET" action="{{ route('admin.swing.index') }}" class="toolbar sw">
        <input type="search" name="q" value="{{ $q }}" class="input" placeholder="Cari nama atau email customer">
        <select name="nilai" class="input">
            <option value="">Semua nilai</option>
            <option value="baik" @selected($grade === 'baik')>Baik (80+)</option>
            <option value="cukup" @selected($grade === 'cukup')>Cukup (65–79)</option>
            <option value="evaluasi" @selected($grade === 'evaluasi')>Perlu Evaluasi (&lt;65)</option>
        </select>
        <select name="catatan" class="input">
            <option value="">Semua catatan</option>
            <option value="belum" @selected($note === 'belum')>Belum ada catatan</option>
            <option value="sudah" @selected($note === 'sudah')>Sudah ada catatan</option>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>

    <div class="table-card">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th class="num">Skor</th>
                        <th>Nilai</th>
                        <th>Kamera</th>
                        <th>Tanggal</th>
                        <th>Catatan coach</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $a)
                        @php $tone = SwingAnalysis::toneFor((int) $a->score); @endphp
                        <tr>
                            <td>
                                <div class="person">
                                    <div class="person-avatar">{{ \App\Support\Icons::initials($a->user->name ?? 'C') }}</div>
                                    <div style="min-width:0">
                                        <strong>{{ $a->user->name ?? 'Customer dihapus' }}</strong>
                                        <small>{{ $a->user->email ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="num"><span class="sw-score {{ $tone }}">{{ $a->score }}</span></td>
                            <td><span class="status-pill {{ $tone === 'good' ? 'booked' : ($tone === 'mid' ? 'pending' : 'rejected') }}">{{ $a->grade_label }}</span></td>
                            <td class="muted">{{ $a->view_label }}</td>
                            <td class="muted">{{ $a->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</td>
                            <td>@if ($a->coach_note)<span class="note-yes">✓ Sudah</span>@else<span class="note-no">Belum</span>@endif</td>
                            <td><div class="row-actions"><a href="{{ route('admin.swing.show', $a) }}">Lihat</a></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted" style="text-align:center;padding:40px 14px">Belum ada hasil analisis swing{{ $q || $grade || $note ? ' yang cocok dengan filter' : '' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.customers._pager', ['paginator' => $items])
    </div>
@endsection
