{{-- Layout halaman masuk/daftar tema Biru Langit: animasi pegolf + form di kartu. --}}
<!DOCTYPE html>
<html lang="id" class="fw-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · {{ \App\Support\Brand::name() }}</title>
    @include('partials.fw-head')
    <style>
        .fa { min-height: 100svh; display: flex; flex-direction: column; align-items: center; padding: 18px 18px 40px; }
        .fa-home { align-self: flex-start; display: inline-flex; align-items: center; gap: 6px; height: 44px; padding: 0 14px; border: 2px solid #14141A; border-radius: 999px; background: #DDF3E4; color: #14141A; font-size: 14px; font-weight: 600; box-shadow: 2px 2px 0 #14141A; }
        .fa-home svg { width: 16px; height: 16px; }
        .fa-hero { width: min(440px, 100%); display: flex; flex-direction: column; align-items: center; text-align: center; margin-top: 4px; }
        .fa-hero .cdl-golfer { max-width: 300px; }
        .fa-brand { margin-top: 4px; font-size: clamp(38px, 10vw, 46px); line-height: .95; }
        .fa-tag { margin-top: 10px; font-family: "Lilita One", system-ui, sans-serif; font-size: 21px; color: #14141A; transform: rotate(-3deg); }
        .fa-card { width: min(440px, 100%); margin-top: 22px; padding: 22px 20px; }
        .fa-card h1 { font-family: "Lilita One", system-ui, sans-serif; font-weight: 400; font-size: 27px; letter-spacing: .01em; color: #14141A; }
        .fa-card .fw-sub { margin-bottom: 18px; color: #14141A; }
        .fa-card form { display: grid; gap: 15px; }
        .fa-pw { position: relative; }
        .fa-pw input { padding-right: 52px !important; }
        .fa-pw button { position: absolute; right: 4px; top: 50%; width: 44px; height: 44px; transform: translateY(-50%); display: grid; place-items: center; border: 0; border-radius: 50%; background: transparent; color: #14141A; cursor: pointer; }
        .fa-pw button svg { width: 19px; height: 19px; }
        .fa-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; font-size: 14px; }
        .fa-row label { display: inline-flex; align-items: center; gap: 8px; color: #14141A; cursor: pointer; }
        .fa-row input[type=checkbox] { width: 18px; height: 18px; accent-color: #22A04B; }
        .fa-alt { margin-top: 18px; text-align: center; color: #14141A; font-size: 14.5px; }
        .fa .fw-btn.block { height: 54px; font-size: 16.5px; letter-spacing: .04em; }
        @media (min-width: 900px) {
            .fa { padding-top: 26px; }
        }
    </style>
</head>
<body class="fw cdl">
    <div class="fa">
        <a href="{{ route('home') }}" class="fa-home">{!! \App\Support\Icons::svg('back') !!} Beranda</a>
        <div class="fa-hero">
            @include('partials.cdl-golfer')
            <p class="fa-brand cdl-title">COACH DITYA<br>LESSON</p>
            <p class="fa-tag">Better Swing, Better Game!</p>
        </div>
        <main class="fa-card cdl-ink-card">
            @yield('content')
        </main>
    </div>
    <script>
        document.querySelectorAll('[data-toggle-pw]').forEach(function (b) {
            b.addEventListener('click', function () {
                var i = b.parentNode.querySelector('input');
                i.type = i.type === 'password' ? 'text' : 'password';
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
