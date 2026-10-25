{{-- Font & CSS tema Fairway. Taruh di <head>. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=Lilita+One&family=Playfair+Display:wght@500;600;700&display=swap">
<link rel="stylesheet" href="{{ asset('css/fw.css') }}?v={{ substr(md5((string) @filemtime(public_path('css/fw.css'))), 0, 8) }}">
<link rel="stylesheet" href="{{ asset('css/cdl.css') }}?v={{ substr(md5((string) @filemtime(public_path('css/cdl.css'))), 0, 8) }}">
<meta name="theme-color" content="#f3f1ea">
@auth
    {{-- Kompres foto otomatis sebelum upload (hanya untuk yang login) --}}
    <script src="{{ asset('js/compress-images.js') }}?v={{ @filemtime(public_path('js/compress-images.js')) }}"></script>
@endauth
@include('partials.theme-script')
@include('partials.brand-head')
