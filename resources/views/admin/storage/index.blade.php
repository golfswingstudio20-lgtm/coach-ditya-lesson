@extends('admin.layouts.panel')

@section('title', 'Penyimpanan')

@php
    use App\Support\StorageCleaner as SC;

    $limit   = SC::limitBytes();
    $pct     = $limit ? min(100, round($scan['bytes'] / $limit * 100)) : 0;
    $tone    = $pct >= 90 ? 'bad' : ($pct >= 70 ? 'warn' : 'ok');
    $old     = collect($scan['orphans'])->where('old', true);
    $fresh   = collect($scan['orphans'])->where('old', false);
    $cronOn  = (string) env('CRON_SECRET', '') !== '';
@endphp

@section('content')
    <style>
        .sg-card { padding: 20px; margin-bottom: 16px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); }
        .sg-card h2 { font-size: 16px; font-weight: 800; }
        .sg-sub { margin: 4px 0 14px; color: var(--text-muted); font-size: 12.5px; line-height: 1.55; }
        .sg-big { display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap; }
        .sg-big b { font-size: 30px; font-weight: 800; color: var(--text); }
        .sg-big span { color: var(--text-muted); font-size: 14px; }
        .sg-bar { height: 14px; margin-top: 12px; border-radius: 99px; background: var(--d-surface-2, #f0eee6); overflow: hidden; }
        .sg-bar i { display: block; height: 100%; border-radius: 99px; background: #3d7d57; }
        .sg-bar.warn i { background: #e0a43b; } .sg-bar.bad i { background: #c9413a; }
        .sg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; }
        .sg-f { padding: 12px 14px; border: 1px solid var(--line); border-radius: 14px; }
        .sg-f strong { display: block; font-size: 13px; }
        .sg-f span { color: var(--text-muted); font-size: 12px; }
        .sg-f em { display: inline-block; margin-top: 6px; padding: 2px 8px; border-radius: 99px; background: var(--d-orange-tint, #fdf1de); color: var(--d-orange-ink, #8a5608); font-size: 11px; font-style: normal; font-weight: 700; }
        .sg-list { display: grid; gap: 6px; max-height: 320px; overflow: auto; }
        .sg-list div { display: flex; justify-content: space-between; gap: 10px; padding: 8px 12px; border-radius: 10px; background: var(--d-surface-2, #f8f7f2); font-size: 12px; }
        .sg-list code { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .sg-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 99px; font-size: 12px; font-weight: 700; }
        .sg-pill.ok { background: rgba(var(--d-green-rgb, 31, 77, 51), .12); color: var(--d-ink-green, #1f4d33); }
        .sg-pill.off { background: var(--d-orange-tint, #fdf1de); color: var(--d-orange-ink, #8a5608); }
    </style>

    <section class="page-head">
        <div>
            <h1>Penyimpanan</h1>
            <p>Pemakaian penyimpanan foto & video (Supabase Storage) dan pembersihan otomatis file yang sudah tidak dipakai.</p>
        </div>
    </section>

    @if ($scan['error'])
        <div class="error-box">{{ $scan['error'] }} — file di folder yang gagal dibaca tidak akan dihapus.</div>
    @endif

    <section class="sg-card">
        <h2>Terpakai</h2>
        <div class="sg-big"><b>{{ SC::size($scan['bytes']) }}</b><span>dari {{ SC::size($limit) }} ({{ $pct }}%) · {{ $scan['files'] }} file</span></div>
        <div class="sg-bar {{ $tone }}"><i style="width: {{ max(1, $pct) }}%"></i></div>
        <p class="sg-sub" style="margin-top:10px;margin-bottom:0">
            @if ($pct >= 90) ⚠️ Hampir penuh. Hapus video lama di Galeri atau pakai link YouTube/Instagram untuk video.
            @elseif ($pct >= 70) Mulai banyak. Untuk video, lebih hemat memakai link YouTube/Instagram.
            @else Masih lega. @endif
            Kuota tonton (egress) dilihat di dashboard Supabase → Usage.
        </p>
    </section>

    <section class="sg-card">
        <h2>Per folder</h2>
        <p class="sg-sub">Bukti pembayaran disimpan terpisah (private) dan tidak ikut dihitung atau dibersihkan.</p>
        <div class="sg-grid">
            @foreach ($scan['folders'] as $f)
                <div class="sg-f">
                    <strong>{{ $f['label'] }}</strong>
                    <span>{{ $f['files'] }} file · {{ SC::size($f['bytes']) }}</span>
                    @if (! empty($f['error']))<br><em>⚠ tidak bisa dibaca</em>@endif
                    @if ($f['orphans'])<br><em>{{ $f['orphans'] }} tidak terpakai · {{ SC::size($f['orphan_bytes']) }}</em>@endif
                </div>
            @endforeach
        </div>
    </section>

    <section class="sg-card">
        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start">
            <div>
                <h2>Bersihkan file tidak terpakai</h2>
                <p class="sg-sub">File yang tidak dipakai data mana pun: foto/video yang sudah diganti atau dihapus, upload yang batal disimpan, poster jadwal lama. File yang masih tampil di website tidak pernah dihapus, dan file berumur kurang dari {{ SC::GRACE_HOURS }} jam dilewati dulu.</p>
            </div>
            <form method="POST" action="{{ route('admin.storage.clean') }}" onsubmit="return confirm('Hapus {{ $old->count() }} file tidak terpakai? File yang dihapus tidak bisa dikembalikan.')">
                @csrf
                <button type="submit" class="btn btn-primary" @disabled($old->isEmpty())>🧹 Bersihkan sekarang ({{ $old->count() }} file · {{ SC::size($old->sum('size')) }})</button>
            </form>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
            @if ($cronOn)
                <span class="sg-pill ok">✓ Otomatis setiap hari ±02:00 WIB</span>
            @else
                <span class="sg-pill off">Otomatis belum aktif: isi CRON_SECRET di Vercel</span>
            @endif
            @if ($last)
                <span class="sg-pill ok">Terakhir: {{ \Carbon\Carbon::parse($last['at'])->locale('id')->diffForHumans() }} · {{ $last['deleted'] }} file dihapus ({{ SC::size($last['freed']) }})</span>
            @endif
        </div>

        @if ($scan['orphans'])
            <div class="sg-list">
                @foreach (collect($scan['orphans'])->sortByDesc('size')->take(100) as $o)
                    <div><code title="{{ $o['path'] }}">{{ $o['path'] }}</code><span>{{ SC::size($o['size']) }}@unless ($o['old']) · baru, dilewati @endunless</span></div>
                @endforeach
            </div>
            @if ($fresh->isNotEmpty())<p class="sg-sub" style="margin:10px 0 0">{{ $fresh->count() }} file baru (kurang dari {{ SC::GRACE_HOURS }} jam) menunggu dan baru dihapus di pembersihan berikutnya jika tetap tidak dipakai.</p>@endif
        @else
            <p class="sg-sub" style="margin:0">✓ Tidak ada file tidak terpakai. Penyimpanan sudah rapi.</p>
        @endif
    </section>
@endsection
