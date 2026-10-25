{{-- Navbar atas tema Fairway (visitor, customer, admin yang membuka website). --}}
@php
    use App\Support\Icons;

    $fnUser  = auth()->user();
    $fnRole  = $fnUser->role ?? 'guest';
    $fnIs    = fn (...$n) => request()->routeIs(...$n);
    $fnHas   = fn ($n) => \Illuminate\Support\Facades\Route::has($n);

    if ($fnRole === 'customer') {
        $fnLinks = array_filter([
            ['Beranda', route('dashboard'), $fnIs('dashboard', 'home')],
            ['Booking', route('booking'), $fnIs('booking', 'payment', 'payment.*')],
            $fnHas('swing.index') ? ['AI Swing', route('swing.index'), $fnIs('swing.*')] : null,
            $fnHas('jadwal') ? ['Jadwal Saya', route('jadwal'), $fnIs('jadwal')] : null,
            ['Program', route('program'), $fnIs('program')],
            ['Galeri', route('galeri'), $fnIs('galeri')],
            ['Event', route('event'), $fnIs('event')],
            $fnHas('chat') ? ['Chat', route('chat'), $fnIs('chat')] : null,
        ]);
    } else {
        $fnLinks = [
            ['Home', route('home'), $fnIs('home')],
            ['Program', route('program'), $fnIs('program')],
            ['Galeri', route('galeri'), $fnIs('galeri')],
            ['Event', route('event'), $fnIs('event')],
            ['Kontak', route('contact'), $fnIs('contact')],
        ];
    }

    $fnHome = $fnRole === 'customer' ? route('dashboard') : route('home');
@endphp

<header class="fw-nav" id="fwNav">
    <a href="{{ $fnHome }}" class="fw-brand" aria-label="{{ \App\Support\Brand::name() }}">
        @include('partials.brand-logo', ['iconClass' => 'fw-brand-coin', 'textClass' => 'fw-brand-text', 'accentClass' => 'fw-acc'])
    </a>

    <nav class="fw-nav-links" aria-label="Menu utama">
        @foreach ($fnLinks as [$label, $url, $on])
            <a href="{{ $url }}" class="fw-nav-link {{ $on ? 'on' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <div class="fw-nav-right">
        <button type="button" class="fw-theme-btn" data-theme-toggle aria-label="Ganti mode terang/gelap" title="Mode terang/gelap">{!! Icons::svg('moon', 'i-moon') !!}{!! Icons::svg('sun', 'i-sun') !!}</button>
        @auth
            @includeIf('partials.notification-bell')

            @if ($fnRole === 'admin')
                <a href="{{ route('admin.dashboard') }}" class="fw-btn md fw-nav-hide-m">{!! Icons::svg('dashboard') !!} Panel Admin</a>
            @else
                <a href="{{ $fnHas('menu') ? route('menu') : route('profile.edit') }}" class="fw-nav-user" aria-label="Menu akun">
                    <span class="fw-av">{{ Icons::initials($fnUser->name) }}</span>
                    <span class="fw-nav-user-name">{{ \Illuminate\Support\Str::limit(strtok($fnUser->name, ' '), 14) }}</span>
                </a>
            @endif
        @else
            <a href="{{ route('login') }}" class="fw-btn md ghost">Masuk</a>
            <a href="{{ route('register') }}" class="fw-btn md fw-nav-hide-m">Daftar</a>
        @endauth
    </div>
</header>
