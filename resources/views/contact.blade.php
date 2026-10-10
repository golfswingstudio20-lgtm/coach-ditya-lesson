@extends('layouts.fw')

@section('title', 'Kontak')

@php
    use App\Support\Icons;

    $isCustomer = auth()->check() && auth()->user()->role === 'customer';

    // Data kontak dikelola admin di menu Contact
    $setting   = \App\Models\ContactSetting::current();
    $locations = \App\Models\ContactLocation::active()->ordered()->get();
    $first     = $locations->first();
@endphp

@push('head')
<style>
    .kt-grid { display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); gap: 22px; align-items: start; }
    .kt-loc { width: 100%; display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 16px; background: var(--fw-surface); border: 1.5px solid var(--fw-line); text-align: left; cursor: pointer; font: inherit; color: inherit; }
    .kt-loc.active { border-color: var(--fw-green); background: var(--fw-tint-2); }
    .kt-loc .num { width: 34px; height: 34px; flex: 0 0 34px; display: grid; place-items: center; border-radius: 50%; background: var(--fw-tint); color: var(--d-ink-green, var(--fw-green)); font-weight: 700; }
    .kt-loc.active .num { background: var(--fw-green); color: #fff; }
    .kt-loc strong { display: block; font-size: 14.5px; font-weight: 600; }
    .kt-loc small { color: var(--fw-muted); font-size: 12.5px; }
    .kt-map { overflow: hidden; }
    .kt-map iframe { width: 100%; height: 320px; border: 0; display: block; }
    .kt-map-foot { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 16px; }
    .kt-form { padding: 20px; margin-top: 16px; display: grid; gap: 12px; }
    .kt-form h2 { font-family: var(--fw-serif); font-size: 22px; font-weight: 600; }
    @media (max-width: 900px) { .kt-grid { grid-template-columns: minmax(0, 1fr); } .kt-map iframe { height: 240px; } }
</style>
@endpush

@section('content')
    <div class="fw-pagehead">
        <div class="fw-pagehead-title">
            <a href="{{ $isCustomer ? route('dashboard') : route('home') }}" class="fw-back" aria-label="Kembali">{!! Icons::svg('back') !!}</a>
            <div>
                <h1 class="fw-h1">{!! \App\Support\Content::html('contact_title') !!}</h1>
                @if ($setting->description)<p class="fw-sub">{{ $setting->description }}</p>@endif
            </div>
        </div>
            @if ((auth()->check() && auth()->user()->role === 'admin'))<button type="button" class="ce-btn" data-ce-open="contact_head">✎ Edit</button>@endif
    </div>

    <div class="kt-grid">
        <section>
            @if ($locations->isNotEmpty())
                <div class="fw-section-head"><h2>{{ \App\Support\Content::get('contact_loc_title') }}</h2></div>
                <div class="fw-list">
                    @foreach ($locations as $index => $location)
                        <button type="button" class="kt-loc location-tab {{ $index === 0 ? 'active' : '' }}"
                                data-name="{{ $location->name }}" data-embed="{{ $location->map_embed_url }}" data-link="{{ $location->map_link }}"
                                aria-pressed="{{ $index === 0 ? 'true' : 'false' }}">
                            <span class="num">{{ $index + 1 }}</span>
                            <span><strong>{{ $location->name }}</strong>@if ($location->area)<small>{{ $location->area }}</small>@endif</span>
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="fw-section-head" style="margin-top:22px"><h2>{{ \App\Support\Content::get('contact_reach_title') }}</h2></div>
            <div class="fw-menu">
                @if ($setting->whatsapp_url)
                    <a href="{{ $setting->whatsapp_url }}" target="_blank" rel="noopener"><span class="ic">{!! Icons::svg('whatsapp') !!}</span><span class="lbl">{{ $setting->whatsapp }} <small class="fw-muted">(WhatsApp)</small></span>{!! Icons::svg('chev', 'fw-chev') !!}</a>
                @endif
                @if ($setting->email)
                    <a href="mailto:{{ $setting->email }}"><span class="ic">{!! Icons::svg('mail') !!}</span><span class="lbl">{{ $setting->email }}</span>{!! Icons::svg('chev', 'fw-chev') !!}</a>
                @endif
                @if ($setting->opening_hours)
                    <a href="#" onclick="return false"><span class="ic">{!! Icons::svg('clock') !!}</span><span class="lbl">{{ $setting->opening_hours }}</span></a>
                @endif
                @if ($isCustomer && Route::has('chat'))
                    <a href="{{ route('chat') }}"><span class="ic">{!! Icons::svg('chat') !!}</span><span class="lbl">Chat langsung dengan coach</span>{!! Icons::svg('chev', 'fw-chev') !!}</a>
                @endif
            </div>
        </section>

        <section>
            @if ($first)
                <div class="fw-card kt-map">
                    <iframe id="mapFrame" src="{{ $first->map_embed_url }}" title="Peta {{ $first->name }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                    <div class="kt-map-foot">
                        <span class="fw-small fw-muted" id="mapName">{{ $first->name }}</span>
                        <a href="{{ $first->map_link }}" id="mapRoute" target="_blank" rel="noopener" class="fw-btn sm">{!! Icons::svg('pin') !!} Buka rute</a>
                    </div>
                </div>
            @endif

            <form class="fw-card kt-form" id="contactForm">
                <h2>{{ \App\Support\Content::get('contact_form_title') }}</h2>
                <div class="fw-form-grid">
                    <div class="fw-field"><label for="contactName">Nama</label><input type="text" id="contactName" autocomplete="name" required value="{{ auth()->user()->name ?? '' }}"></div>
                    <div class="fw-field"><label for="contactEmail">Email</label><input type="email" id="contactEmail" autocomplete="email" required value="{{ auth()->user()->email ?? '' }}"></div>
                </div>
                <div class="fw-field"><label for="contactMessage">Pesan</label><textarea id="contactMessage" placeholder="Tulis pertanyaan Anda..." required></textarea></div>
                <button type="submit" class="fw-btn">{!! Icons::svg('whatsapp') !!} Kirim via WhatsApp</button>
            </form>
        </section>
    </div>
    @if ((auth()->check() && auth()->user()->role === 'admin'))
        @include('partials.content-editor', ['keys' => ['contact_head']])
    @endif
@endsection

@push('scripts')
<script>
    (() => {
        const phoneNumber = @json($setting->whatsapp_number);
        const tabs     = document.querySelectorAll('.location-tab');
        const mapFrame = document.getElementById('mapFrame');
        const mapRoute = document.getElementById('mapRoute');
        const mapName  = document.getElementById('mapName');
        let activeLocation = tabs[0]?.dataset.name ?? '';

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                tabs.forEach((item) => { item.classList.toggle('active', item === tab); item.setAttribute('aria-pressed', String(item === tab)); });
                if (mapFrame) { mapFrame.src = tab.dataset.embed; mapFrame.title = 'Peta ' + tab.dataset.name; mapRoute.href = tab.dataset.link; mapName.textContent = tab.dataset.name; }
                activeLocation = tab.dataset.name;
            });
        });

        document.getElementById('contactForm').addEventListener('submit', (event) => {
            event.preventDefault();
            const value = (id) => document.getElementById(id).value.trim();
            const text = ['Halo {{ \App\Support\Brand::name() }},', '', 'Nama   : ' + value('contactName'), 'Email  : ' + value('contactEmail'), 'Lokasi : ' + (activeLocation || '-'), '', value('contactMessage')].join('\n');
            if (!phoneNumber) { alert('Nomor WhatsApp belum diatur.'); return; }
            window.open('https://wa.me/' + phoneNumber + '?text=' + encodeURIComponent(text), '_blank', 'noopener,noreferrer');
        });
    })();
</script>
@endpush
