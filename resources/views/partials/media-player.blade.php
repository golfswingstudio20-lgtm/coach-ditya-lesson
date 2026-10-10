{{--
    Pemutar foto/video sederhana (dipakai di admin Kelola Galeri).
    Elemen yang bisa diklik: [data-play] dengan data-type, data-embed, data-platform, data-link,
    data-link-label, data-file, data-poster, data-src, data-title.
--}}
@once
<style>
    .mp { position: fixed; inset: 0; z-index: 400; display: none; align-items: center; justify-content: center; padding: 56px 16px 24px; background: rgba(10, 20, 14, .9); overflow-y: auto; }
    .mp.open { display: flex; }
    .mp-box { width: min(960px, 100%); }
    .mp-media { border-radius: 18px; overflow: hidden; background: #000; }
    .mp-media img { display: block; width: 100%; max-height: 80vh; object-fit: contain; }
    .mp-ratio { position: relative; aspect-ratio: 16 / 9; margin: 0 auto; }
    .mp-ratio iframe, .mp-ratio video { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; background: #000; }
    .mp-ig { width: min(400px, 100%); max-height: calc(100svh - 170px); margin: 0 auto; overflow-y: auto; border-radius: 16px; background: #fff; }
    .mp-ig .instagram-media { margin: 0 !important; min-width: 0 !important; width: 100% !important; max-width: 100% !important; border: 0 !important; box-shadow: none !important; }
    .mp-media:has(.mp-ig) { background: transparent; }
    .mp-cap { margin-top: 12px; color: #fff; text-align: center; font-weight: 700; }
    .mp-open { display: flex; width: fit-content; align-items: center; gap: 8px; margin: 12px auto 0; height: 42px; padding: 0 18px; border-radius: 99px; background: linear-gradient(90deg, #f58529, #dd2a7b 55%, #8134af); color: #fff !important; font-weight: 700; font-size: 14px; text-decoration: none; }
    .mp-x { position: fixed; top: 12px; right: 12px; width: 42px; height: 42px; border: 0; border-radius: 50%; background: rgba(255, 255, 255, .15); color: #fff; font-size: 20px; cursor: pointer; }
    [data-play] { cursor: pointer; }
</style>
<div class="mp" id="mediaPlayer" role="dialog" aria-modal="true" aria-label="Pemutar media">
    <button type="button" class="mp-x" data-mp-close aria-label="Tutup">✕</button>
    <div class="mp-box"><div class="mp-media" id="mpMedia"></div><div class="mp-cap" id="mpCap"></div></div>
</div>
<script>
(function () {
    'use strict';
    var box = document.getElementById('mediaPlayer'), media = document.getElementById('mpMedia');
    function close() { box.classList.remove('open'); media.innerHTML = ''; document.body.style.overflow = ''; }
    function linkBtn(url, label) {
        var a = document.createElement('a'); a.className = 'mp-open'; a.href = url; a.target = '_blank'; a.rel = 'noopener';
        a.textContent = 'Lihat di ' + (label || 'Instagram'); return a;
    }
    window.gblPlay = function (el) {
        var d = el.dataset; media.innerHTML = '';
        if (d.platform === 'instagram' && d.link) {
            var ig = document.createElement('div'); ig.className = 'mp-ig';
            var q = document.createElement('blockquote'); q.className = 'instagram-media';
            q.setAttribute('data-instgrm-permalink', d.link.split('?')[0]); q.setAttribute('data-instgrm-version', '14');
            q.textContent = 'Memuat video Instagram…'; ig.appendChild(q); media.appendChild(ig);
            if (window.instgrm && window.instgrm.Embeds) window.instgrm.Embeds.process();
            else if (!document.getElementById('igEmbedJs')) { var s = document.createElement('script'); s.id = 'igEmbedJs'; s.async = true; s.src = 'https://www.instagram.com/embed.js'; document.body.appendChild(s); }
        } else if (d.type === 'video') {
            var wrap = document.createElement('div'); wrap.className = 'mp-ratio';
            if (d.embed) {
                var f = document.createElement('iframe');
                f.src = d.embed + (d.embed.indexOf('?') === -1 ? '?' : '&') + 'autoplay=1&mute=1&playsinline=1&rel=0';
                f.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen'; f.allowFullscreen = true;
                wrap.appendChild(f);
            } else if (d.file) {
                var v = document.createElement('video'); v.controls = true; v.playsInline = true; v.src = d.file;
                if (d.poster) v.poster = d.poster;
                v.addEventListener('loadedmetadata', function () {
                    if (v.videoHeight > v.videoWidth) {
                        wrap.style.aspectRatio = v.videoWidth + ' / ' + v.videoHeight;
                        wrap.style.width = 'min(100%, calc((100svh - 170px) * ' + (v.videoWidth / v.videoHeight).toFixed(4) + '))';
                    }
                });
                wrap.appendChild(v);
                var p = v.play(); if (p && p.catch) p.catch(function () { v.muted = true; v.play().catch(function () {}); });
            }
            media.appendChild(wrap);
        } else if (d.src) {
            var img = document.createElement('img'); img.src = d.src; img.alt = d.title || ''; media.appendChild(img);
        }
        if (d.link && d.platform !== 'instagram') media.appendChild(linkBtn(d.link, d.linkLabel));
        if (d.platform === 'instagram' && d.link) media.appendChild(linkBtn(d.link, 'Instagram'));
        document.getElementById('mpCap').textContent = d.title || '';
        box.classList.add('open'); document.body.style.overflow = 'hidden';
    };
    document.addEventListener('click', function (e) {
        var el = e.target.closest && e.target.closest('[data-play]');
        if (el) { e.preventDefault(); window.gblPlay(el); return; }
        if (e.target === box || (e.target.closest && e.target.closest('[data-mp-close]'))) close();
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
})();
</script>
@endonce
