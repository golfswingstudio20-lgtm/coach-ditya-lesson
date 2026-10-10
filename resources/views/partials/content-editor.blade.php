{{--
    Panel edit konten langsung dari halaman website (hanya admin).
    Variabel: $keys = daftar kunci bagian (lihat App\Support\Content).
    Tombol pembuka: <button type="button" class="ce-btn" data-ce-open="kunci">✎ Edit</button>
--}}
@php
    $ceOpen = session('content_section');
    $ceOpen = in_array($ceOpen, $keys, true) && $errors->getBag('content')->any() ? $ceOpen : null;
    $ceDirect = \App\Support\Uploads::directEnabled();
@endphp
<style>
    .ce-btn { display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 14px; border: 0; border-radius: 99px; background: var(--d-btn, #1f4d33); color: #fff; font: 600 12.5px var(--fw-sans, Inter, Arial, sans-serif); cursor: pointer; box-shadow: 0 8px 20px rgba(var(--d-green-rgb, 31, 77, 51), .28); white-space: nowrap; }
    .ce-btn:hover { background: var(--d-btn-hover, #2a6444); }
    .ce-btn.light { background: #fff; color: #1f4d33; }
    .ce-btn.float { position: absolute; z-index: 5; top: 14px; right: 14px; }
    .ce-toast { position: fixed; z-index: 400; left: 50%; top: 84px; transform: translateX(-50%); padding: 12px 18px; border-radius: 14px; background: var(--d-btn, #1f4d33); color: #fff; font: 600 14px var(--fw-sans, Inter, Arial, sans-serif); box-shadow: 0 16px 40px rgba(var(--d-shadow-rgb, 23, 46, 33), .3); animation: ceToast 4s ease forwards; }
    @keyframes ceToast { 0%, 85% { opacity: 1; } 100% { opacity: 0; visibility: hidden; } }
    .cep-back { position: fixed; inset: 0; z-index: 300; background: rgba(23, 38, 29, .4); opacity: 0; visibility: hidden; transition: opacity .2s; }
    .cep-panel { position: fixed; z-index: 310; top: 0; right: 0; bottom: 0; width: min(520px, 100%); display: flex; flex-direction: column; background: var(--d-surface-2, #f8f7f2); box-shadow: -20px 0 60px rgba(var(--d-shadow-rgb, 23, 46, 33), .2); transform: translateX(100%); transition: transform .25s ease; color: var(--d-text, #17261d); font-family: var(--fw-sans, Inter, Arial, sans-serif); }
    .cep.open .cep-back { opacity: 1; visibility: visible; }
    .cep.open .cep-panel { transform: none; }
    .cep-form { flex: 1; min-height: 0; display: none; flex-direction: column; }
    .cep-form.on { display: flex; }
    .cep-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 16px 20px; background: var(--d-surface, #fff); border-bottom: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .08); }
    .cep-top strong { font-family: var(--fw-serif, Georgia, serif); font-size: 20px; font-weight: 600; }
    .cep-top small { display: block; margin-top: 2px; color: var(--d-muted, #77837b); font-size: 12.5px; }
    .cep-x { width: 40px; height: 40px; flex: 0 0 40px; border: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .12); border-radius: 50%; background: var(--d-surface, #fff); color: var(--d-text, #17261d); font-size: 16px; cursor: pointer; }
    .cep-scroll { flex: 1; overflow-y: auto; padding: 18px 20px 24px; overscroll-behavior: contain; }
    .cep-foot { display: flex; gap: 10px; padding: 14px 20px calc(14px + env(safe-area-inset-bottom)); border-top: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .08); background: var(--d-surface, #fff); }
    .cep-foot a, .cep-foot button { flex: 1; display: inline-flex; align-items: center; justify-content: center; height: 48px; border-radius: 99px; font: 600 14px var(--fw-sans, Inter, Arial, sans-serif); text-decoration: none; cursor: pointer; }
    .cep-foot a { border: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .16); color: var(--d-text, #17261d); background: var(--d-surface, #fff); }
    .cep-foot button { border: 0; background: var(--d-btn, #1f4d33); color: #fff; }
    .cep-foot button:disabled { opacity: .6; cursor: wait; }
    body.cep-lock { overflow: hidden !important; }
    @media (max-width: 820px) { .ce-btn { height: 34px; padding: 0 12px; font-size: 12px; } }
</style>

@if (session('content_saved'))
    <div class="ce-toast" role="status">✓ {{ session('content_saved') }}</div>
@endif

<div class="cep {{ $ceOpen ? 'open' : '' }}" id="cep">
    <div class="cep-back" data-cep-close></div>
    <aside class="cep-panel" role="dialog" aria-modal="true" aria-label="Edit konten">
        @foreach ($keys as $key)
            @php $sec = \App\Support\Content::section($key); @endphp
            @continue(! $sec)
            <form class="cep-form {{ $ceOpen === $key ? 'on' : '' }}" data-cep="{{ $key }}" method="POST" enctype="multipart/form-data"
                  action="{{ route('admin.content.update', $key) }}" @if ($ceDirect) data-direct-upload="{{ route('admin.uploads.sign') }}" @endif>
                @csrf
                @method('PUT')
                <input type="hidden" name="return" value="page">
                <div class="cep-top">
                    <div><strong>Edit: {{ $sec['title'] }}</strong><small>Perubahan langsung tampil di halaman ini.</small></div>
                    <button type="button" class="cep-x" data-cep-close aria-label="Tutup">✕</button>
                </div>
                <div class="cep-scroll">@include('partials.content-fields', ['key' => $key, 'section' => $sec])</div>
                <div class="cep-foot">
                    <a href="{{ route('admin.content.index', ['page' => $sec['page']]) }}#sec-{{ $key }}">Semua konten</a>
                    <button type="submit">Simpan</button>
                </div>
            </form>
        @endforeach
    </aside>
</div>

<script>
(function () {
    var box = document.getElementById('cep');
    if (!box) return;
    function set(key) {
        box.querySelectorAll('.cep-form').forEach(function (f) { f.classList.toggle('on', f.dataset.cep === key); });
        box.classList.toggle('open', !!key);
        document.body.classList.toggle('cep-lock', !!key);
    }
    if (box.classList.contains('open')) document.body.classList.add('cep-lock');
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-ce-open]');
        if (b) { e.preventDefault(); set(b.dataset.ceOpen); return; }
        if (e.target.closest('[data-cep-close]')) set(null);
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') set(null); });
    box.querySelectorAll('.cep-form').forEach(function (f) {
        f.addEventListener('submit', function () {
            setTimeout(function () { var b = f.querySelector('.cep-foot button'); if (b && !f.querySelector('[data-upload-status]')) { b.disabled = true; b.textContent = 'Menyimpan...'; } }, 0);
        });
    });
})();
</script>
@unless (isset($ceNoUploadJs))
    <script src="{{ asset('js/direct-upload.js') }}?v={{ @filemtime(public_path('js/direct-upload.js')) }}" defer></script>
@endunless
