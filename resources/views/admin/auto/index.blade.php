@extends('admin.layouts.panel')

@section('title', 'Jadwal Otomatis')

@php
    use App\Support\AutoReports as AR;
    use App\Support\WhatsApp;

    $waOn   = WhatsApp::enabled() && WhatsApp::adminNumbers();
    $cronOn = (string) env('CRON_SECRET', '') !== '';
    $mailOn = ! in_array(config('mail.default'), ['log', 'array', null], true);
    $tomorrow = \App\Models\Booking::whereDate('booking_date', now()->addDay()->toDateString())->where('status', 'booked')->count();
@endphp

@section('content')
    <style>
        .ao-status { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
        .ao-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 99px; font-size: 12px; font-weight: 700; text-decoration: none; }
        .ao-pill.ok { background: rgba(var(--d-green-rgb, 31, 77, 51), .12); color: var(--d-ink-green, #1f4d33); }
        .ao-pill.off { background: var(--d-orange-tint, #fdf1de); color: var(--d-orange-ink, #8a5608); }
        .ao-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; align-items: start; }
        .ao-card { padding: 18px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); }
        .ao-card.off { opacity: .65; }
        .ao-head { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
        .ao-head h2 { font-size: 15px; font-weight: 800; }
        .ao-head small { display: block; margin-top: 3px; color: var(--text-muted); font-size: 12px; }
        .ao-switch { flex: 0 0 auto; width: 46px; height: 26px; border: 0; border-radius: 99px; background: #c9cfc6; position: relative; cursor: pointer; }
        .ao-switch::after { content: ""; position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; border-radius: 50%; background: #fff; transition: left .2s; }
        .ao-switch.on { background: #3d7d57; } .ao-switch.on::after { left: 23px; }
        .ao-msg { margin-top: 12px; padding: 12px 14px; border-radius: 14px; background: #e7f5dc; color: #14141A; font-size: 12.5px; line-height: 1.55; white-space: pre-wrap; max-height: 260px; overflow: auto; font-family: inherit; }
        html[data-theme="dark"] .ao-msg { background: #1d3325; color: #e8efe9; }
        .ao-foot { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 12px; }
        .ao-last { color: var(--text-muted); font-size: 11.5px; line-height: 1.5; }
        @media (max-width: 1000px) { .ao-grid { grid-template-columns: 1fr; } }
    </style>

    <section class="page-head">
        <div>
            <h1>Jadwal Otomatis</h1>
            <p>Pesan yang dikirim otomatis setiap hari/minggu/bulan. Matikan yang tidak perlu, atau tekan "Kirim sekarang" untuk mencoba.</p>
        </div>
    </section>

    <div class="ao-status">
        <span class="ao-pill {{ $cronOn ? 'ok' : 'off' }}">{{ $cronOn ? '✓ Jadwal Vercel aktif' : 'CRON_SECRET belum diisi di Vercel' }}</span>
        @if ($waOn)
            <span class="ao-pill ok">✓ WhatsApp admin: {{ implode(', ', WhatsApp::adminNumbers()) }}</span>
        @else
            <a href="{{ route('admin.settings.index', ['tab' => 'whatsapp']) }}" class="ao-pill off">WhatsApp belum aktif: pesan hanya masuk ke lonceng admin →</a>
        @endif
        <span class="ao-pill {{ $mailOn ? 'ok' : 'off' }}">{{ $mailOn ? '✓ Email aktif' : 'Email belum diatur: pengingat murid lewat lonceng' }}</span>
    </div>

    <div class="ao-grid">
        @foreach (AR::JOBS as $job => [$title, $when])
            @php $on = AR::enabled($job); $last = AR::lastRun($job); @endphp
            <section class="ao-card {{ $on ? '' : 'off' }}">
                <div class="ao-head">
                    <div><h2>{{ $title }}</h2><small>{{ $when }}</small></div>
                    <form method="POST" action="{{ route('admin.auto.toggle', $job) }}">@csrf<button type="submit" class="ao-switch {{ $on ? 'on' : '' }}" aria-label="{{ $on ? 'Matikan' : 'Aktifkan' }}" title="{{ $on ? 'Aktif — klik untuk mematikan' : 'Mati — klik untuk mengaktifkan' }}"></button></form>
                </div>

                @if ($job === 'reminder')
                    <div class="ao-msg">Contoh untuk murid:
Halo Kanya! Besok {{ now()->addDay()->locale('id')->translatedFormat('l, j F Y') }} jam 08.00–09.00 ada Lesson Driving Range. Datang tepat waktu ya, dan konfirmasi ke coach jika berhalangan.

Booking "booked" besok: {{ $tomorrow }} murid.</div>
                @else
                    <div class="ao-msg">{{ $previews[$job] }}</div>
                @endif

                <div class="ao-foot">
                    <span class="ao-last">
                        @if ($last)
                            Terakhir: {{ \Carbon\Carbon::parse($last['at'])->locale('id')->diffForHumans() }}<br>{{ $last['detail'] }}
                        @else
                            Belum pernah jalan.
                        @endif
                    </span>
                    <form method="POST" action="{{ route('admin.auto.run', $job) }}" onsubmit="return confirm('Kirim {{ strtolower($title) }} sekarang?')">
                        @csrf
                        <button type="submit" class="btn btn-outline">▶ Kirim sekarang</button>
                    </form>
                </div>
            </section>
        @endforeach
    </div>
@endsection
