{{--
    Menu bawah ala aplikasi untuk tampilan HP (<= 820px), tema Fairway.
    Dipasang otomatis di semua halaman oleh App\Http\Middleware\MobileResponsive.
--}}
@php
    use App\Support\Icons;

    $mtUser = auth()->user();
    $mtRole = $mtUser->role ?? 'guest';
    $mtIs   = fn (...$names) => request()->routeIs(...$names);
    $mtHas  = fn ($n) => \Illuminate\Support\Facades\Route::has($n);

    $mtUnread = 0;
    if ($mtUser) {
        try { $mtUnread = $mtUser->unreadNotifications()->count(); } catch (\Throwable $e) { $mtUnread = 0; }
    }

    $mtChat = 0;
    if ($mtUser && class_exists(\App\Models\ChatMessage::class) && $mtHas('chat')) {
        try {
            $mtChat = $mtRole === 'admin' ? \App\Models\ChatMessage::unreadForAdmin() : \App\Models\ChatMessage::unreadForMember($mtUser->id);
        } catch (\Throwable $e) { $mtChat = 0; }
    }

    // [label, url, icon, aktif, badge]
    if ($mtRole === 'admin') {
        $mtTabs = [
            ['Beranda', route('admin.dashboard'), 'home', $mtIs('admin.dashboard', 'admin.bookings.*'), 0],
            ['Jadwal', route('admin.schedule-blocks.index'), 'clock', $mtIs('admin.schedule-blocks.*'), 0],
            ['Booking', route('admin.offline-booking.create'), 'plus-c', $mtIs('admin.offline-booking.*'), 0],
            ['Chat', route('admin.chat.index'), 'chat', $mtIs('admin.chat.*'), $mtChat],
            ['Menu', $mtHas('admin.menu') ? route('admin.menu') : route('admin.settings.index'), 'menu', $mtIs('admin.menu', 'admin.settings.*', 'admin.customers.*', 'admin.gallery.*', 'admin.events.*', 'admin.programs.*', 'admin.contact.*', 'admin.coaches.*', 'notifications.*'), $mtUnread],
        ];
    } elseif ($mtRole === 'customer') {
        $mtTabs = [
            ['Home', route('dashboard'), 'home', $mtIs('dashboard', 'home'), 0],
            ['Lesson', route('booking'), 'calendar', $mtIs('booking', 'payment', 'payment.*'), 0],
            ['Analisis', $mtHas('swing.index') ? route('swing.index') : route('booking'), 'video', $mtIs('swing.*'), 0],
            ['Riwayat', $mtHas('jadwal') ? route('jadwal') : route('booking'), 'history', $mtIs('jadwal'), 0],
            ['Profil', $mtHas('menu') ? route('menu') : route('profile.edit'), 'user', $mtIs('menu', 'profile.*', 'notifications.*', 'chat', 'program', 'galeri', 'event', 'contact'), $mtUnread + $mtChat],
        ];
    } else {
        $mtTabs = [
            ['Home', route('home'), 'home', $mtIs('home'), 0],
            ['Program', route('program'), 'star', $mtIs('program'), 0],
            ['Booking', route('login'), 'plus-c', false, 0],
            ['Galeri', route('galeri'), 'image', $mtIs('galeri'), 0],
            ['Menu', $mtHas('menu') ? route('menu') : route('contact'), 'menu', $mtIs('menu', 'event', 'contact'), 0],
        ];
    }
@endphp

<style>
    .mt-bar { display: none; }

    @media (max-width: 820px) {
        body { padding-bottom: calc(78px + env(safe-area-inset-bottom)) !important; }

        .mt-bar {
            position: fixed; left: 0; right: 0; bottom: 0; z-index: 250;
            display: grid; grid-template-columns: repeat(5, 1fr); align-items: end;
            height: calc(70px + env(safe-area-inset-bottom)); padding: 0 6px env(safe-area-inset-bottom);
            border-top: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .07); border-radius: 22px 22px 0 0;
            background: rgba(var(--d-glass-rgb, 255, 255, 255), .97);
            -webkit-backdrop-filter: blur(14px); backdrop-filter: blur(14px);
            box-shadow: 0 -8px 30px rgba(var(--d-shadow-rgb, 23, 46, 33), .08);
            font-family: "Fredoka", system-ui, -apple-system, "Segoe UI", Arial, sans-serif;
        }
        .mt-item {
            position: relative; height: 70px; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; gap: 4px;
            padding-bottom: 10px; color: var(--d-muted, #8a958e); font-size: 11px; font-weight: 500; text-decoration: none;
            -webkit-tap-highlight-color: transparent;
        }
        .mt-ico { position: relative; width: 26px; height: 26px; display: grid; place-items: center; transition: transform .2s ease; }
        .mt-ico svg { width: 23px; height: 23px; }
        .mt-item.on { color: var(--d-ink-green, #1f4d33); font-weight: 600; }
        .mt-item.on .mt-ico {
            width: 46px; height: 46px; margin-top: -24px; border-radius: 50%;
            background: var(--d-btn, #1f4d33); color: #fff; box-shadow: 0 8px 18px rgba(var(--d-green-rgb, 31, 77, 51), .35), 0 0 0 5px var(--d-bg, #fff);
        }
        .mt-item.on .mt-ico svg { width: 22px; height: 22px; }
        .mt-item:active .mt-ico { transform: scale(.92); }
        .mt-dot {
            position: absolute; top: -5px; right: -9px; min-width: 17px; height: 17px; padding: 0 4px;
            display: grid; place-items: center; border-radius: 99px; background: #c9413a; color: #fff;
            font-size: 9.5px; font-weight: 700; box-shadow: 0 0 0 2px var(--d-surface, #fff);
        }
        .mt-item.on .mt-dot { top: -2px; right: -4px; }
    }

    @media print { .mt-bar { display: none !important; } }
</style>

<nav class="mt-bar" aria-label="Menu bawah">
    @foreach ($mtTabs as [$label, $url, $icon, $active, $badge])
        <a href="{{ $url }}" class="mt-item {{ $active ? 'on' : '' }}" @if ($active) aria-current="page" @endif>
            <span class="mt-ico">
                {!! Icons::svg($icon) !!}
                @if ($badge > 0)<span class="mt-dot">{{ $badge > 9 ? '9+' : $badge }}</span>@endif
            </span>
            <span>{{ $label }}</span>
        </a>
    @endforeach
</nav>
