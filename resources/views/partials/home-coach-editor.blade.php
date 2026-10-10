{{-- Tombol & panel edit About Coach langsung dari halaman Home (hanya admin/coach). Variabel: $coach --}}
@php
    $hceOpen = $errors->any() && old('return') === 'home';
@endphp
<style>
    .hce-btn { position: absolute; z-index: 20; top: 18px; right: max(24px, calc((100% - 1180px) / 2)); display: inline-flex; align-items: center; gap: 8px; height: 42px; padding: 0 18px; border: 0; border-radius: 99px; background: var(--d-btn, #1f4d33); color: #fff; font: 600 13.5px var(--fw-sans, Inter, Arial, sans-serif); cursor: pointer; box-shadow: 0 10px 24px rgba(var(--d-green-rgb, 31, 77, 51), .3); }
    .hce-btn:hover { background: var(--d-btn-hover, #2a6444); }
    .hce-off { position: absolute; z-index: 20; top: 18px; left: max(24px, calc((100% - 1180px) / 2)); padding: 10px 14px; border-radius: 12px; background: var(--d-orange-tint, #fdf1de); border: 1px solid rgba(233, 162, 59, .45); color: var(--d-orange-ink, #8a5608); font: 600 12.5px var(--fw-sans, Inter, Arial, sans-serif); }
    .hce-toast { position: fixed; z-index: 400; left: 50%; top: 84px; transform: translateX(-50%); padding: 12px 18px; border-radius: 14px; background: var(--d-btn, #1f4d33); color: #fff; font: 600 14px var(--fw-sans, Inter, Arial, sans-serif); box-shadow: 0 16px 40px rgba(var(--d-shadow-rgb, 23, 46, 33), .3); animation: hceToast 4s ease forwards; }
    @keyframes hceToast { 0%, 85% { opacity: 1; } 100% { opacity: 0; visibility: hidden; } }
    .hce-back { position: fixed; inset: 0; z-index: 300; background: rgba(23, 38, 29, .4); opacity: 0; visibility: hidden; transition: opacity .2s; }
    .hce-panel { position: fixed; z-index: 310; top: 0; right: 0; bottom: 0; width: min(560px, 100%); display: flex; flex-direction: column; background: var(--d-surface-2, #f8f7f2); box-shadow: -20px 0 60px rgba(var(--d-shadow-rgb, 23, 46, 33), .2); transform: translateX(100%); transition: transform .25s ease; color: var(--d-text, #17261d); font-family: var(--fw-sans, Inter, Arial, sans-serif); }
    .hce.open .hce-back { opacity: 1; visibility: visible; }
    .hce.open .hce-panel { transform: none; }
    .hce-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 16px 20px; background: var(--d-surface, #fff); border-bottom: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .08); }
    .hce-top strong { font-family: var(--fw-serif, Georgia, serif); font-size: 20px; font-weight: 600; }
    .hce-top small { display: block; color: var(--d-muted, #77837b); font-size: 12.5px; font-weight: 400; margin-top: 2px; }
    .hce-x { width: 40px; height: 40px; border: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .12); border-radius: 50%; background: var(--d-surface, #fff); color: var(--d-text, #17261d); font-size: 16px; cursor: pointer; }
    .hce-form { flex: 1; min-height: 0; display: flex; flex-direction: column; }
    .hce-scroll { flex: 1; overflow-y: auto; padding: 18px 20px 24px; overscroll-behavior: contain; }
    .hce-err { margin-bottom: 14px; padding: 10px 12px; border-radius: 12px; background: var(--d-red-tint, #fbe7e5); border: 1px solid rgba(201, 65, 58, .3); color: var(--d-red-ink, #8f2a24); font-size: 13px; }
    .hce-err ul { margin: 0; padding-left: 18px; }
    .hce-foot { display: flex; gap: 10px; padding: 14px 20px calc(14px + env(safe-area-inset-bottom)); border-top: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .08); background: var(--d-surface, #fff); }
    .hce-foot a, .hce-foot button { flex: 1; display: inline-flex; align-items: center; justify-content: center; height: 48px; border-radius: 99px; font: 600 14px var(--fw-sans, Inter, Arial, sans-serif); text-decoration: none; cursor: pointer; }
    .hce-foot a { border: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .16); color: var(--d-text, #17261d); background: var(--d-surface, #fff); }
    .hce-foot button { border: 0; background: var(--d-btn, #1f4d33); color: #fff; }
    .hce-foot button:disabled { opacity: .6; cursor: wait; }
    body.hce-lock { overflow: hidden !important; }
    @media (max-width: 820px) {
        .hce-btn { top: 10px; right: 16px; height: 38px; font-size: 12.5px; }
        .hce-off { top: 56px; left: 16px; right: 16px; }
        .hce-panel { width: 100%; }
        .hc { padding-top: 64px !important; }
    }
</style>

@if (session('coach_saved'))
    <div class="hce-toast" role="status">✓ {{ session('coach_saved') }}</div>
@endif

<button type="button" class="hce-btn" data-hce-open>✎ Edit About Coach</button>
@if ($coach->exists && ! $coach->is_active)
    <div class="hce-off">Bagian ini sedang disembunyikan dari pengunjung. Centang "Tampilkan" untuk menampilkan.</div>
@elseif (! $coach->exists)
    <div class="hce-off">Belum ada profil coach. Klik "Edit About Coach" untuk mengisi.</div>
@endif

<div class="hce {{ $hceOpen ? 'open' : '' }}" id="hce">
    <div class="hce-back" data-hce-close></div>
    <aside class="hce-panel" role="dialog" aria-modal="true" aria-labelledby="hceTitle">
        <div class="hce-top">
            <div>
                <strong id="hceTitle">Edit About Coach</strong>
                <small>Perubahan langsung tampil di halaman Home.</small>
            </div>
            <button type="button" class="hce-x" data-hce-close aria-label="Tutup">✕</button>
        </div>

        <form class="hce-form" method="POST" enctype="multipart/form-data" id="hceForm"
              action="{{ $coach->exists ? route('admin.coaches.update', $coach) : route('admin.coaches.store') }}" @if (\App\Support\Uploads::directEnabled()) data-direct-upload="{{ route('admin.uploads.sign') }}" @endif>
            @csrf
            @if ($coach->exists) @method('PUT') @endif
            <input type="hidden" name="return" value="home">

            <div class="hce-scroll">
                @if ($hceOpen)
                    <div class="hce-err"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                @include('admin.coaches._fields', ['coach' => $coach])
            </div>

            <div class="hce-foot">
                <a href="{{ route('admin.coaches.index') }}">Buka di admin</a>
                <button type="submit" id="hceSave">Simpan</button>
            </div>
        </form>
    </aside>
</div>

<script>
(function () {
    var box = document.getElementById('hce');
    if (!box) return;
    function set(open) { box.classList.toggle('open', open); document.body.classList.toggle('hce-lock', open); }
    if (box.classList.contains('open')) document.body.classList.add('hce-lock');
    document.querySelectorAll('[data-hce-open]').forEach(function (b) { b.addEventListener('click', function () { set(true); }); });
    box.querySelectorAll('[data-hce-close]').forEach(function (b) { b.addEventListener('click', function () { set(false); }); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') set(false); });
    document.getElementById('hceForm').addEventListener('submit', function () {
        var b = document.getElementById('hceSave'); b.disabled = true; b.textContent = 'Menyimpan...';
    });
})();
</script>
<script src="{{ asset('js/direct-upload.js') }}?v={{ @filemtime(public_path('js/direct-upload.js')) }}" defer></script>
