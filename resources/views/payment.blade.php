{{-- Pembayaran event: bayar langsung ke penyelenggara (diatur admin per event) + kode referal coach. --}}
@extends('layouts.fw')

@section('title', 'Daftar Event')
@section('no_footer', true)

@php
    use App\Support\Icons;

    $event = request('event') ? \App\Models\Event::active()->find(request('event')) : \App\Models\Event::active()->upcoming()->chronological()->first();
    $user  = auth()->user();

    $waText = $event ? implode("\n", array_filter([
        'Halo, saya mau daftar event *' . $event->title . '* (' . $event->date_label . ').',
        'Nama: ' . ($user->name ?? ''),
        $event->referral_code ? 'Kode referal: *' . $event->referral_code . '*' : null,
        'Berikut bukti pembayarannya:',
    ])) : '';
    $waUrl = $event && $event->pay_whatsapp ? 'https://wa.me/' . $event->pay_whatsapp . '?text=' . rawurlencode($waText) : null;
@endphp

@push('head')
<style>
    .ep { display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); gap: 18px; align-items: start; }
    .ep-poster { position: relative; overflow: hidden; display: grid; place-items: center; padding: 18px; border-radius: 18px 18px 0 0; background: #0c1f15; isolation: isolate; }
    .ep-poster .blur { position: absolute; inset: -40px; z-index: -1; background: center / cover no-repeat; filter: blur(26px) brightness(.45); }
    .ep-poster img { display: block; max-width: 100%; max-height: 360px; border-radius: 12px; box-shadow: 0 16px 34px rgba(0, 0, 0, .45); }
    html[data-theme="dark"] .ep-poster img { filter: none; }
    .ep-sum { padding: 18px 20px 20px; display: grid; gap: 10px; }
    .ep-sum h2 { font-family: var(--fw-serif); font-size: 24px; font-weight: 600; }
    .ep-row { display: flex; gap: 10px; align-items: center; color: var(--fw-text-2); font-size: 14px; }
    .ep-row svg { width: 17px; height: 17px; flex: 0 0 17px; color: var(--fw-green-3); }
    .ep-total { display: flex; justify-content: space-between; align-items: baseline; margin-top: 6px; padding: 14px 16px; border-radius: 16px; background: var(--fw-green); color: #fff; }
    .ep-total strong { font-family: var(--fw-serif); font-size: 26px; font-weight: 600; }
    .ep-pay { padding: 22px; display: grid; gap: 16px; }
    .ep-pay h2 { font-family: var(--fw-serif); font-size: 22px; font-weight: 600; }
    .ep-org { padding: 12px 14px; border-radius: 14px; background: var(--fw-surface-2); color: var(--fw-text-2); font-size: 13.5px; line-height: 1.5; }
    .ep-org b { color: var(--fw-text); }

    .ep-ref { position: relative; padding: 18px; border-radius: 18px; background: linear-gradient(135deg, #1f4d33, #2f6d48); color: #fff; overflow: hidden; }
    .ep-ref::after { content: ""; position: absolute; right: -40px; top: -40px; width: 140px; height: 140px; border-radius: 50%; background: rgba(205, 232, 163, .14); }
    .ep-ref small { display: block; color: #cde8a3; font-size: 11.5px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase; }
    .ep-ref-code { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 8px; padding: 10px 10px 10px 16px; border: 2px dashed rgba(205, 232, 163, .55); border-radius: 14px; background: rgba(0, 0, 0, .15); }
    .ep-ref-code code { font-family: inherit; font-size: 24px; font-weight: 800; letter-spacing: 2px; word-break: break-all; }
    .ep-ref p { position: relative; z-index: 1; margin-top: 10px; color: rgba(255, 255, 255, .85); font-size: 13px; line-height: 1.5; }
    .ep-copy { flex: 0 0 auto; display: inline-flex; align-items: center; gap: 6px; height: 40px; padding: 0 14px; border: 0; border-radius: 99px; background: #cde8a3; color: #14301f; font: inherit; font-size: 13px; font-weight: 700; cursor: pointer; }
    .ep-copy svg { width: 16px; height: 16px; }
    .ep-copy.sm { height: 34px; padding: 0 12px; background: var(--fw-tint); color: var(--d-ink-green, var(--fw-green)); }

    .ep-step { display: flex; gap: 12px; align-items: flex-start; }
    .ep-step > i { width: 28px; height: 28px; flex: 0 0 28px; display: grid; place-items: center; border-radius: 50%; background: var(--fw-tint); color: var(--d-ink-green, var(--fw-green)); font-style: normal; font-weight: 800; font-size: 13px; }
    .ep-step > div { flex: 1; min-width: 0; }
    .ep-step h3 { font-size: 15px; font-weight: 700; margin-bottom: 8px; }
    .ep-qr { display: grid; justify-items: center; gap: 10px; padding: 16px; border-radius: 18px; background: #fff; border: 1px solid var(--fw-line); }
    .ep-qr img { width: min(260px, 100%); height: auto; border-radius: 10px; cursor: zoom-in; }
    .ep-qr a { color: #1f4d33; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
    .ep-qr a svg { width: 16px; height: 16px; }
    .ep-acc { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--fw-line); background: var(--fw-surface); }
    .ep-acc b { min-width: 58px; height: 36px; padding: 0 8px; display: grid; place-items: center; border-radius: 10px; background: var(--fw-blue-tint); color: var(--d-blue-ink, var(--fw-blue)); font-size: 11.5px; text-transform: uppercase; }
    .ep-acc div { flex: 1; min-width: 0; }
    .ep-acc code { display: block; font-family: inherit; font-size: 15px; font-weight: 700; letter-spacing: .5px; }
    .ep-acc small { display: block; color: var(--fw-muted); font-size: 12px; }
    .ep-note { white-space: pre-line; color: var(--fw-text-2); font-size: 14px; line-height: 1.65; }
    .ep-wa { background: #1fa855 !important; }
    .ep-toast { position: fixed; left: 50%; bottom: 100px; z-index: 400; transform: translate(-50%, 20px); padding: 12px 18px; border-radius: 14px; background: var(--fw-green); color: #fff; font-size: 14px; opacity: 0; pointer-events: none; transition: .25s; max-width: calc(100% - 32px); }
    .ep-toast.show { opacity: 1; transform: translate(-50%, 0); }
    .ep-zoom { position: fixed; inset: 0; z-index: 420; display: none; align-items: center; justify-content: center; padding: 20px; background: rgba(0, 0, 0, .85); }
    .ep-zoom.open { display: flex; }
    .ep-zoom img { max-width: 100%; max-height: 90vh; border-radius: 12px; background: #fff; }
    @media (max-width: 900px) { .ep { grid-template-columns: minmax(0, 1fr); } .ep-pay { padding: 18px; } }
</style>
@endpush

@section('content')
    <div class="fw-pagehead">
        <div class="fw-pagehead-title">
            <a href="{{ route('event') }}" class="fw-back" aria-label="Kembali">{!! Icons::svg('back') !!}</a>
            <div>
                <h1 class="fw-h1">Daftar Event</h1>
                <p class="fw-sub">Bayar langsung ke penyelenggara, jangan lupa pakai kode referal coach.</p>
            </div>
        </div>
    </div>

    @if (! $event)
        <div class="fw-empty"><b>Event tidak ditemukan</b>Event mungkin sudah selesai atau disembunyikan.</div>
    @else
        <div class="ep">
            <section class="fw-card">
                @if ($event->poster_url)
                    <div class="ep-poster"><span class="blur" style="background-image:url('{{ $event->poster_url }}')" aria-hidden="true"></span><img src="{{ $event->poster_url }}" alt="Poster {{ $event->title }}"></div>
                @endif
                <div class="ep-sum">
                    <span class="fw-eyebrow">Ringkasan</span>
                    <h2>{{ $event->title }}</h2>
                    <div class="ep-row">{!! Icons::svg('calendar') !!} {{ $event->date_label }}</div>
                    <div class="ep-row">{!! Icons::svg('clock') !!} {{ $event->time_range }}</div>
                    @if ($event->location)<div class="ep-row">{!! Icons::svg('pin') !!} {{ $event->location }}</div>@endif
                    <div class="ep-total"><span>Biaya</span><strong>{{ $event->price_label }}@if ($event->price > 0)<small style="font-family:var(--fw-sans);font-size:13px;font-weight:500;opacity:.75"> / {{ $event->price_unit }}</small>@endif</strong></div>
                </div>
            </section>

            <section class="fw-card ep-pay">
                <div>
                    <h2>Cara daftar &amp; bayar</h2>
                    <p class="fw-sub">Pembayaran dilakukan langsung ke penyelenggara acara.</p>
                </div>

                @if ($event->organizer)
                    <div class="ep-org">Penyelenggara: <b>{{ $event->organizer }}</b></div>
                @endif

                @if ($event->referral_code)
                    <div class="ep-ref">
                        <small>Kode referal coach</small>
                        <div class="ep-ref-code">
                            <code id="refCode">{{ $event->referral_code }}</code>
                            <button type="button" class="ep-copy" data-copy="{{ $event->referral_code }}">{!! Icons::svg('copy') !!} Salin</button>
                        </div>
                        <p>{{ $event->referral_note ?: 'Masukkan / sebutkan kode ini saat membayar atau mendaftar ke penyelenggara.' }}</p>
                    </div>
                @endif

                @if (! $event->hasPaymentInfo())
                    <div class="fw-alert warn" style="margin:0">{!! Icons::svg('info') !!} <span>Info pembayaran untuk event ini belum diisi. Silakan tanya lewat Chat Coach.</span></div>
                    @if (Route::has('chat') && $user && $user->role === 'customer')
                        <a href="{{ route('chat') }}" class="fw-btn block">{!! Icons::svg('chat') !!} Chat Coach</a>
                    @endif
                @else
                    @php $n = 0; @endphp

                    @if ($event->pay_qr_url || $event->pay_account_list)
                        <div class="ep-step">
                            <i>{{ ++$n }}</i>
                            <div style="display:grid;gap:10px">
                                <h3 style="margin:0">Bayar {{ $event->price > 0 ? $event->price_label : '' }}</h3>
                                @if ($event->pay_qr_url)
                                    <div class="ep-qr">
                                        <img src="{{ $event->pay_qr_url }}" alt="QR pembayaran" id="qrImg">
                                        <a href="{{ $event->pay_qr_url }}" download target="_blank" rel="noopener">{!! Icons::svg('download') !!} Simpan QR</a>
                                    </div>
                                @endif
                                @foreach ($event->pay_account_list as $acc)
                                    <div class="ep-acc">
                                        <b>{{ \Illuminate\Support\Str::limit($acc['bank'], 10, '') }}</b>
                                        <div><code>{{ $acc['number'] }}</code>@if ($acc['name'])<small>{{ $acc['name'] }}</small>@endif</div>
                                        @if ($acc['number'])<button type="button" class="ep-copy sm" data-copy="{{ preg_replace('/[\s-]+/', '', $acc['number']) }}" aria-label="Salin nomor {{ $acc['bank'] }}">{!! Icons::svg('copy') !!}</button>@endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($event->pay_instructions)
                        <div class="ep-step"><i>{{ ++$n }}</i><div><h3>Instruksi penyelenggara</h3><p class="ep-note">{{ $event->pay_instructions }}</p></div></div>
                    @endif

                    @if ($waUrl || $event->pay_link)
                        <div class="ep-step">
                            <i>{{ ++$n }}</i>
                            <div style="display:grid;gap:10px">
                                <h3 style="margin:0">{{ $waUrl ? 'Kirim bukti bayar' : 'Daftar di penyelenggara' }}</h3>
                                @if ($waUrl)
                                    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="fw-btn block ep-wa">{!! Icons::svg('whatsapp') !!} Kirim bukti + kode referal via WhatsApp</a>
                                @endif
                                @if ($event->pay_link)
                                    <a href="{{ $event->pay_link }}" target="_blank" rel="noopener" class="fw-btn block {{ $waUrl ? 'ghost' : '' }}">{!! Icons::svg('external') !!} Buka halaman pendaftaran penyelenggara</a>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif

                <a href="{{ route('event') }}" class="fw-btn block ghost">Kembali ke Event</a>
            </section>
        </div>

        <div class="ep-zoom" id="qrZoom" role="dialog" aria-label="QR pembayaran"><img src="{{ $event->pay_qr_url }}" alt="QR pembayaran"></div>
        <div class="ep-toast" id="toast" role="status"></div>
    @endif
@endsection

@push('scripts')
<script>
(function () {
    var toast = document.getElementById('toast');
    function say(t) { if (!toast) return; toast.textContent = t; toast.classList.add('show'); setTimeout(function () { toast.classList.remove('show'); }, 2200); }
    document.querySelectorAll('[data-copy]').forEach(function (b) {
        b.addEventListener('click', function () {
            var v = b.dataset.copy;
            (navigator.clipboard ? navigator.clipboard.writeText(v) : Promise.reject()).then(function () { say('Disalin: ' + v); }, function () {
                var t = document.createElement('textarea'); t.value = v; document.body.appendChild(t); t.select();
                try { document.execCommand('copy'); say('Disalin: ' + v); } catch (e) { say(v); }
                t.remove();
            });
        });
    });
    var qr = document.getElementById('qrImg'), zoom = document.getElementById('qrZoom');
    if (qr && zoom) {
        qr.addEventListener('click', function () { zoom.classList.add('open'); });
        zoom.addEventListener('click', function () { zoom.classList.remove('open'); });
    }
})();
</script>
@endpush
