{{-- Layout utama tema Fairway untuk halaman visitor & customer. --}}
<!DOCTYPE html>
<html lang="id" class="fw-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') · @endif{{ \App\Support\Brand::name() }}</title>
    @include('partials.fw-head')
    @stack('head')
</head>
<body class="fw cdl @yield('body_class')">
    @unless (View::hasSection('no_nav'))
        @include('partials.fw-navbar')
    @endunless

    <main class="fw-main @yield('main_class')">
        @yield('content')
    </main>

    @unless (View::hasSection('no_footer'))
        @include('partials.fw-footer')
    @endunless

    @stack('scripts')
</body>
</html>
