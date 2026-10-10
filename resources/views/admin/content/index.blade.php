@extends('admin.layouts.panel')

@section('title', 'Konten Website')

@php
    use App\Support\Content;

    [$pageTitle, $pageRoute, $sections] = $pages[$page];
    $direct = \App\Support\Uploads::directEnabled();
@endphp

@section('content')
    <style>
        .kt-tabs { display: flex; gap: 8px; overflow-x: auto; margin-bottom: 16px; padding-bottom: 4px; scrollbar-width: none; }
        .kt-tabs::-webkit-scrollbar { display: none; }
        .kt-tab { flex: 0 0 auto; display: inline-flex; align-items: center; height: 40px; padding: 0 16px; border: 1px solid rgba(var(--d-green-rgb, 31, 77, 51), .2); border-radius: 999px; background: var(--d-surface, #fff); color: var(--d-text-2, #3c4a42); font-size: 13px; font-weight: 700; text-decoration: none; }
        .kt-tab.active { background: var(--d-btn, #1f4d33); border-color: var(--d-btn, #1f4d33); color: #fff; }
        .kt-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; align-items: start; }
        .kt-card { scroll-margin-top: 90px; padding: 20px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); }
        .kt-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 16px; }
        .kt-card-head h2 { font-size: 16px; font-weight: 800; }
        .kt-card-head a { flex: 0 0 auto; color: var(--d-ink-green, #1f4d33); font-size: 12.5px; font-weight: 700; text-decoration: none; }
        .kt-actions { display: flex; justify-content: flex-end; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--line); }
        .kt-tip { margin-bottom: 16px; padding: 12px 16px; border-radius: 14px; background: var(--d-tint-2, #f0f5ef); color: var(--d-text-2, #3c4a42); font-size: 13px; line-height: 1.55; }
        @media (max-width: 1100px) { .kt-grid { grid-template-columns: 1fr; } }
    </style>

    <section class="page-head">
        <div>
            <h1>Konten Website</h1>
            <p>Semua teks, judul, tombol, dan foto di halaman website. Kosongkan isian untuk kembali ke teks bawaan.</p>
        </div>
        <div class="head-actions">
            <a href="{{ route($pageRoute) }}" target="_blank" rel="noopener" class="btn btn-outline">◉ Lihat halaman {{ $pageTitle }}</a>
        </div>
    </section>

    <nav class="kt-tabs" aria-label="Halaman">
        @foreach ($pages as $key => [$label])
            <a href="{{ route('admin.content.index', ['page' => $key]) }}" class="kt-tab {{ $page === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <div class="kt-tip">
        💡 Bisa juga langsung dari halaman website: buka halamannya saat login sebagai admin, lalu klik tombol <b>✎ Edit</b> di bagian yang ingin diubah.
        @if ($page === 'home') Profil, foto & artikel coach diatur di <a href="{{ route('admin.coaches.index') }}">About Coach</a>; kartu program di <a href="{{ route('admin.programs.index') }}">Program</a>. @endif
        @if ($page === 'coach') Profil, foto, artikel & riwayat coach diatur di <a href="{{ route('admin.coaches.index') }}">About Coach</a>. @endif
    </div>

    <div class="kt-grid">
        @foreach ($sections as $key => [$title, $anchor])
            <form method="POST" action="{{ route('admin.content.update', $key) }}" enctype="multipart/form-data" class="kt-card" id="sec-{{ $key }}" @if ($direct) data-direct-upload="{{ route('admin.uploads.sign') }}" @endif>
                @csrf
                @method('PUT')
                <div class="kt-card-head">
                    <h2>{{ $title }}</h2>
                    <a href="{{ route($pageRoute) }}#{{ $anchor }}" target="_blank" rel="noopener">Lihat ↗</a>
                </div>
                @include('partials.content-fields', ['key' => $key, 'section' => Content::section($key)])
                <div class="kt-actions"><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        @endforeach
    </div>
@endsection
