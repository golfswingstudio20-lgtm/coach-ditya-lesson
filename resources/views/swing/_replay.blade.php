{{-- Video swing tersimpan: putar ulang di Studio Video, hapus video, atau hapus seluruh analisis.
     Variabel: $analysis, $captureUrl, $deleteVideoUrl, $deleteUrl --}}
@php
    use App\Support\Icons;

    $payload = $analysis->studioPayload($captureUrl);
    if ($payload) {
        $payload['csrf'] = csrf_token();
    }
    $studioJs = asset('js/swing-studio.js') . '?v=' . (int) @filemtime(public_path('js/swing-studio.js'));
@endphp
<style>
    .rp { display: grid; gap: 10px; padding: 16px; background: #DDF3E4; border: 2.5px solid #14141A; border-radius: 20px; box-shadow: 3px 3px 0 #14141A; color: #14141A; }
    .rp h2 { font-family: "Lilita One", system-ui, sans-serif; font-weight: 400; font-size: 20px; letter-spacing: .01em; }
    .rp p { font-size: 13.5px; font-weight: 500; color: #14141A; }
    .rp-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    .rp-row form { margin: 0; }
    .rp .st-btn { height: 40px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px; border: 2px solid #14141A; border-radius: 12px; background: #FFFFFF; color: #14141A; font: inherit; font-size: 13.5px; font-weight: 600; cursor: pointer; box-shadow: 2px 2px 0 #14141A; }
    .rp .st-btn.big { height: 46px; padding: 0 16px; font-size: 14.5px; }
    .rp .st-btn.primary { background: #22A04B; color: #FFFFFF; }
    .rp .st-btn svg { width: 17px; height: 17px; }
    .rp .st-btn:disabled { opacity: .7; }
    .rp .st-btn.danger { background: #FFC7C2; }
</style>

<section class="rp" id="sw-video-card">
    <h2>{!! Icons::svg('video', 'fw-ic-inline') !!} Video Swing</h2>
    @if ($payload)
        <p>Putar ulang dengan slow motion, garis coach, shot tracer, gambar garis/sudut, dan capture.</p>
        <div class="rp-row">
            <button type="button" class="st-btn big primary" id="sw-replay">▶ Putar Video &amp; Studio</button>
            <form method="POST" action="{{ $deleteVideoUrl }}" onsubmit="return confirm('Hapus video swing ini? Hasil analisis tetap tersimpan.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="st-btn big">{!! Icons::svg('trash') !!} Hapus video</button>
            </form>
        </div>
    @else
        <p>Video untuk analisis ini tidak tersimpan, jadi tidak bisa diputar ulang. Hasil dan gambar tahapan tetap ada.</p>
    @endif
    <div class="rp-row">
        <form method="POST" action="{{ $deleteUrl }}" onsubmit="return confirm('Hapus seluruh analisis ini (hasil, gambar, capture, dan video)? Tidak bisa dibatalkan.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="st-btn danger">{!! Icons::svg('trash') !!} Hapus analisis ini</button>
        </form>
    </div>
</section>

@if ($payload)
    @include('swing._studio', ['mode' => 'replay'])
    <script type="application/json" id="sw-replay-data">@json($payload)</script>
    <script type="module">
        const btn = document.getElementById('sw-replay');
        btn?.addEventListener('click', async () => {
            btn.disabled = true;
            btn.textContent = 'Memuat video…';
            try {
                const m = await import(@json($studioJs));
                const d = JSON.parse(document.getElementById('sw-replay-data').textContent);
                await m.openStudio({ ...d, root: document.querySelector('[data-studio]'), frames: m.expandPose(d.pose) });
                btn.textContent = '▶ Studio terbuka di bawah';
            } catch (e) {
                btn.disabled = false;
                btn.textContent = '▶ Putar Video & Studio';
                alert('Video tidak bisa diputar: ' + (e.message || 'coba lagi'));
            }
        });
    </script>
@endif
