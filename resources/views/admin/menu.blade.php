{{-- Menu admin (HP): semua halaman pengelolaan. --}}
@extends('admin.layouts.panel')

@section('title', 'Menu')

@php
    use App\Support\Icons;

    $pending  = \App\Models\Booking::where('status', 'pending')->count();
    $chatNew  = class_exists(\App\Models\ChatMessage::class) ? \App\Models\ChatMessage::unreadForAdmin() : 0;
    $unread   = auth()->user()->unreadNotifications()->count();
    $item = function ($url, $icon, $label, $count = 0) {
        return '<a href="' . e($url) . '"><span class="ic">' . Icons::svg($icon) . '</span><span class="lbl">' . e($label) . '</span>'
            . ($count > 0 ? '<span class="count">' . ($count > 99 ? '99+' : $count) . '</span>' : '')
            . Icons::svg('chev', 'fw-chev') . '</a>';
    };
@endphp

@section('content')
    <style>
        .am { max-width: 720px; }
        .am-profile { display: flex; align-items: center; gap: 14px; padding: 18px; margin-bottom: 18px; }
        .am-profile strong { display: block; font-size: 17px; font-weight: 600; }
        .am-group { margin-bottom: 18px; }
        .am-group h2 { margin: 0 4px 8px; color: var(--text-muted); font-size: 12px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; }
    </style>

    <div class="am">
        <section class="page-head"><div><h1>Menu</h1><p>Semua pengaturan website & booking ada di sini.</p></div></section>

        <div class="fw-card am-profile">
            <span class="fw-av lg" style="background:var(--lime);color:#fff">{{ Icons::initials(auth()->user()->name) }}</span>
            <span style="flex:1;min-width:0">
                <strong>{{ auth()->user()->name }}</strong>
                <span class="fw-badge green" style="margin-top:4px">Admin · Coach</span>
            </span>
        </div>

        <div class="am-group">
            <h2>Booking</h2>
            <div class="fw-menu">
                {!! $item(route('admin.dashboard') . '#booking-list', 'list', 'Semua Booking') !!}
                {!! $item(route('admin.dashboard', ['status' => 'pending']) . '#booking-list', 'clock', 'Booking Pending', $pending) !!}
                {!! $item(route('admin.offline-booking.create'), 'plus-c', 'Buat Booking Offline') !!}
                {!! $item(route('admin.schedule-blocks.index'), 'calendar', 'Kelola Jadwal') !!}
                {!! $item(route('admin.poster.index'), 'image', 'Poster Jadwal (IG & WhatsApp)') !!}
            </div>
        </div>

        <div class="am-group">
            <h2>Member</h2>
            <div class="fw-menu">
                {!! $item(route('admin.customers.index'), 'users', 'Customer') !!}
                {!! $item(route('admin.chat.index'), 'chat', 'Chat Member', $chatNew) !!}
                {!! $item(route('notifications.index'), 'bell', 'Notifikasi', $unread) !!}
            </div>
        </div>

        <div class="am-group">
            <h2>Konten website</h2>
            <div class="fw-menu">
                {!! $item(route('home'), 'edit', 'Edit langsung di Home') !!}
                {!! $item(route('admin.content.index'), 'layers', 'Konten Website (semua teks & foto)') !!}
                {!! $item(route('admin.coaches.index'), 'coach', 'About Coach') !!}
                {!! $item(route('admin.programs.index'), 'star', 'Program') !!}
                {!! $item(route('admin.gallery.index'), 'image', 'Galeri') !!}
                {!! $item(route('admin.gallery-albums.index'), 'grid', 'Folder Galeri') !!}
                {!! $item(route('admin.events.index'), 'flag', 'Event') !!}
                {!! $item(route('admin.contact.index'), 'phone', 'Kontak & Lokasi') !!}
            </div>
        </div>

        <div class="am-group">
            <h2>Sistem</h2>
            <div class="fw-menu">
                {!! $item(route('admin.auto.index'), 'clock', 'Jadwal Otomatis (agenda, pengingat, laporan)') !!}
                {!! $item(route('admin.storage.index'), 'layers', 'Penyimpanan (foto & video)') !!}
                {!! $item(route('admin.settings.index'), 'settings', 'Settings (nama, logo, harga, pembayaran)') !!}
                <button type="button" data-theme-toggle aria-label="Ganti mode terang/gelap"><span class="ic">{!! Icons::svg('moon') !!}</span><span class="lbl">Mode gelap</span><span class="fw-theme-switch" aria-hidden="true"></span></button>
                {!! $item(route('home'), 'eye', 'Lihat Website') !!}
                <form method="POST" action="{{ route('logout') }}" style="margin:0">
                    @csrf
                    <button type="submit" class="danger"><span class="ic">{!! Icons::svg('logout') !!}</span><span class="lbl">Keluar</span></button>
                </form>
            </div>
        </div>
    </div>
@endsection
