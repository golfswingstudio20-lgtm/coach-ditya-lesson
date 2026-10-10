{{-- Footer tema Fairway (hanya laptop). --}}
@php
    $ffContact = class_exists(\App\Models\ContactSetting::class) ? \App\Models\ContactSetting::query()->first() : null;
@endphp
<footer class="fw-footer">
    <div class="fw-footer-grid">
        <div>
            <a href="{{ route('home') }}" class="fw-brand">
                @include('partials.brand-logo', ['iconClass' => 'fw-brand-coin', 'textClass' => 'fw-brand-text', 'accentClass' => 'fw-acc'])
            </a>
            <p style="margin-top:12px;max-width:42ch;line-height:1.7">{{ \App\Support\Brand::tagline() }}</p>
            @if (auth()->check() && auth()->user()->role === 'admin' && request()->routeIs('home'))<button type="button" class="ce-btn" data-ce-open="footer" style="margin-top:12px">✎ Edit footer</button>@endif
        </div>
        <div>
            <h4>{{ \App\Support\Content::get('footer_menu_title') }}</h4>
            <a href="{{ route('program') }}">Program</a>
            <a href="{{ route('galeri') }}">Galeri</a>
            <a href="{{ route('event') }}">Event</a>
            <a href="{{ route('contact') }}">Kontak</a>
        </div>
        <div>
            <h4>{{ \App\Support\Content::get('footer_contact_title') }}</h4>
            @if ($ffContact?->whatsapp)<a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D+/', '', $ffContact->whatsapp)) }}" target="_blank" rel="noopener">WhatsApp {{ $ffContact->whatsapp }}</a>@endif
            @if ($ffContact?->email)<a href="mailto:{{ $ffContact->email }}">{{ $ffContact->email }}</a>@endif
            <a href="{{ auth()->check() ? route('booking') : route('login') }}">Booking lesson</a>
        </div>
    </div>
    <div class="fw-footer-bottom">© {{ now()->year }} {{ \App\Support\Brand::name() }}</div>
</footer>
