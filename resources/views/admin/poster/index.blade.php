@extends('admin.layouts.panel')

@section('title', 'Poster Jadwal')

@php
    use App\Models\Setting;
    use App\Support\SchedulePoster as SP;
    use App\Support\WhatsApp;
    use Carbon\Carbon;

    $today = Carbon::today();
    $sat   = $today->isSaturday() ? $today->copy() : ($today->isSunday() ? $today->copy()->subDay() : $today->copy()->next(Carbon::SATURDAY));
    $mon   = $today->copy()->startOfWeek(Carbon::MONDAY);
    $quick = [
        'Hari ini'           => [$today, $today],
        'Besok'              => [$today->copy()->addDay(), $today->copy()->addDay()],
        'Akhir pekan'        => [$sat, $sat->copy()->addDay()],
        'Minggu ini'         => [$mon, $mon->copy()->addDays(6)],
        'Minggu depan'       => [$mon->copy()->addWeek(), $mon->copy()->addWeek()->addDays(6)],
    ];
    $val = fn ($k) => old($k, in_array($k, ['poster_contact', 'poster_website', 'poster_ig'], true) && Setting::get($k) === '-' ? '-' : SP::get($k));
    $waReady = WhatsApp::enabled() && WhatsApp::adminNumbers();
    $fileName = 'jadwal-' . $poster['from'] . ($poster['from'] !== $poster['to'] ? '-sd-' . $poster['to'] : '') . '.png';
    $adminWa = preg_replace('/^0/', '62', preg_replace('/\D+/', '', SP::show('poster_contact')));
    $waReady = (bool) $waReady;
    $settingsUrl = route('admin.settings.index', ['tab' => 'whatsapp']);
    $caption = 'Jadwal ' . (SP::get('poster_subtitle') ?: \App\Support\Brand::name()) . ' — ' . $poster['range'];
@endphp

@section('content')
    <style>
        .pj { display: grid; grid-template-columns: minmax(0, 1fr) 400px; gap: 18px; align-items: start; }
        .pj-card { padding: 18px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); }
        .pj-card + .pj-card { margin-top: 16px; }
        .pj-card h2 { font-size: 15px; font-weight: 800; margin-bottom: 4px; }
        .pj-card > p { color: var(--text-muted); font-size: 12px; line-height: 1.5; margin-bottom: 14px; }
        .pj-stage { position: sticky; top: 84px; }
        .pj-frame { width: 100%; aspect-ratio: 1080 / 1350; border-radius: 16px; overflow: hidden; background: #e9e6dc; box-shadow: 0 18px 40px rgba(23, 46, 33, .14); }
        .pj-scale { width: 1080px; height: 1350px; transform-origin: 0 0; }
        .pj-actions { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-top: 14px; }
        .pj-actions .btn { justify-content: center; }
        .pj-msg { margin-top: 10px; padding: 10px 12px; border-radius: 12px; font-size: 12.5px; line-height: 1.5; display: none; }
        .pj-msg.ok { display: block; background: rgba(var(--d-green-rgb, 31, 77, 51), .1); color: var(--d-ink-green, #1f4d33); }
        .pj-msg.err { display: block; background: var(--d-red-tint, #fbe7e5); color: var(--d-red-ink, #8f2a24); }
        .pj-quick { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
        .pj-quick a { padding: 7px 12px; border: 1px solid var(--line); border-radius: 99px; color: var(--text-soft); font-size: 12px; font-weight: 700; text-decoration: none; }
        .pj-quick a.on, .pj-quick a:hover { border-color: var(--d-btn, #1f4d33); background: var(--d-btn, #1f4d33); color: #fff; }
        .pj-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .pj-modes { display: grid; gap: 6px; }
        .pj-modes label { display: flex; align-items: center; gap: 8px; padding: 9px 12px; border: 1px solid var(--line); border-radius: 12px; font-size: 13px; cursor: pointer; }
        .pj-modes input { accent-color: #1f4d33; }
        .pj-stat { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
        .pj-stat span { padding: 5px 10px; border-radius: 99px; background: rgba(var(--d-green-rgb, 31, 77, 51), .1); color: var(--d-ink-green, #1f4d33); font-size: 11.5px; font-weight: 700; }
        .pj-logo { display: flex; gap: 12px; align-items: center; }
        .pj-logo-prev { width: 64px; height: 64px; flex: 0 0 64px; border-radius: 50%; background: #fff center / contain no-repeat; border: 1px solid var(--line); }
        .pj textarea.input { min-height: 150px; }
        @media (max-width: 1100px) { .pj { grid-template-columns: 1fr; } .pj-stage { position: static; } }
        @media (max-width: 560px) { .pj-actions { grid-template-columns: 1fr; } .pj-row { grid-template-columns: 1fr; } }
    </style>

    <section class="page-head">
        <div>
            <h1>Poster Jadwal</h1>
            <p>Buat gambar jadwal lesson dari booking customer, lalu unduh, bagikan ke Instagram / WhatsApp, atau kirim ke WhatsApp admin.</p>
        </div>
    </section>

    @if ($errors->any())
        <div class="error-box"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="pj">
        {{-- ============ PRATINJAU ============ --}}
        <div class="pj-stage">
            <div class="pj-frame" id="pjFrame" data-no-mobile><div class="pj-scale" id="pjScale">@include('admin.poster._poster', ['poster' => $poster])</div></div>
            <div class="pj-actions" data-no-mobile>
                <button type="button" class="btn btn-primary" id="pjDownload">⬇ Unduh PNG</button>
                <button type="button" class="btn btn-outline" id="pjShare">↗ Bagikan (IG / WA)</button>
                <button type="button" class="btn btn-outline" id="pjSend">✉ Kirim ke WA admin</button>
            </div>
            <div class="pj-msg" id="pjMsg"></div>
        </div>

        {{-- ============ PENGATURAN ============ --}}
        <div>
            <form method="GET" action="{{ route('admin.poster.index') }}" class="pj-card">
                <h2>Tanggal jadwal</h2>
                <p>Maksimal {{ SP::MAX_DAYS }} hari. 1–2 hari tampil berdampingan, 3–7 hari tampil sebagai tabel.</p>
                <div class="pj-quick">
                    @foreach ($quick as $label => [$a, $b])
                        <a href="{{ route('admin.poster.index', ['from' => $a->toDateString(), 'to' => $b->toDateString(), 'mode' => $mode]) }}" class="{{ $poster['from'] === $a->toDateString() && $poster['to'] === $b->toDateString() ? 'on' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
                <div class="pj-row">
                    <div class="field"><label for="pjFrom">Dari</label><input type="date" name="from" id="pjFrom" class="input" value="{{ $poster['from'] }}" required></div>
                    <div class="field"><label for="pjTo">Sampai</label><input type="date" name="to" id="pjTo" class="input" value="{{ $poster['to'] }}" required></div>
                </div>
                <div class="field">
                    <span class="field-label">Tampilan nama murid</span>
                    <div class="pj-modes">
                        @foreach (SP::MODES as $k => $label)
                            <label><input type="radio" name="mode" value="{{ $k }}" @checked($mode === $k)> {{ $label }}</label>
                        @endforeach
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Tampilkan jadwal</button>
                <div class="pj-stat">
                    <span>{{ $poster['count'] }} booking</span>
                    <span>{{ collect($poster['days'])->sum(fn ($d) => collect($d['rows'])->where('booked', false)->count()) }} jam {{ strtolower(SP::get('poster_free')) }}</span>
                    <a href="{{ route('admin.schedule-blocks.index') }}" style="font-size:11.5px;font-weight:700;color:var(--d-ink-green,#1f4d33);align-self:center">Atur jam tutup →</a>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.poster.update') }}" enctype="multipart/form-data" class="pj-card" @if (\App\Support\Uploads::directEnabled()) data-direct-upload="{{ route('admin.uploads.sign') }}" @endif>
                @csrf
                @method('PUT')
                <input type="hidden" name="from" value="{{ $poster['from'] }}">
                <input type="hidden" name="to" value="{{ $poster['to'] }}">
                <h2>Isi poster</h2>
                <p>Tersimpan untuk poster berikutnya. Kata di dalam [kurung siku] pada judul tampil hijau miring.</p>

                <div class="field">
                    <span class="field-label">Logo</span>
                    <div class="pj-logo">
                        <span class="pj-logo-prev" id="pjLogoPrev" style="{{ ($u = Setting::get('poster_logo') ?: Setting::get('logo')) ? "background-image:url('" . e(\Illuminate\Support\Facades\Storage::disk('public')->url($u)) . "')" : '' }}"></span>
                        <div style="flex:1;min-width:0">
                            <input type="file" name="poster_logo" accept="image/*" class="input" data-folder="content" id="pjLogo">
                            <p class="field-hint">Kosong = memakai logo website (Settings). PNG transparan paling bagus.</p>
                            @if (Setting::get('poster_logo'))<label class="checkbox"><input type="checkbox" name="remove_poster_logo" value="1"> Hapus logo poster</label>@endif
                            <input type="hidden" name="poster_show_logo" value="0">
                            <label class="checkbox"><input type="checkbox" name="poster_show_logo" value="1" @checked($val('poster_show_logo') === '1')> Tampilkan logo</label>
                        </div>
                    </div>
                </div>

                <div class="pj-row">
                    <div class="field"><label for="pSub">Teks kecil di atas judul</label><input type="text" name="poster_subtitle" id="pSub" class="input" maxlength="80" value="{{ $val('poster_subtitle') }}"></div>
                    <div class="field"><label for="pTitle">Judul</label><input type="text" name="poster_title" id="pTitle" class="input" maxlength="80" value="{{ $val('poster_title') }}"></div>
                </div>
                <div class="pj-row">
                    <div class="field"><label for="pFree">Tulisan jam kosong</label><input type="text" name="poster_free" id="pFree" class="input" maxlength="30" value="{{ $val('poster_free') }}"></div>
                    <div class="field"><label for="pBooked">Tulisan jam terisi (mode sembunyikan nama)</label><input type="text" name="poster_booked" id="pBooked" class="input" maxlength="30" value="{{ $val('poster_booked') }}"></div>
                </div>
                <div class="field">
                    <label for="pNotes">Notes (satu baris satu poin)</label>
                    <textarea name="poster_notes" id="pNotes" class="input" maxlength="1500">{{ $val('poster_notes') }}</textarea>
                    <p class="field-hint">Kosongkan semua baris untuk menyembunyikan kotak Notes.</p>
                </div>
                <div class="field"><label for="pFooter">Kalimat penutup</label><input type="text" name="poster_footer" id="pFooter" class="input" maxlength="80" value="{{ $val('poster_footer') }}"></div>
                <div class="field"><label for="pContact">Contact admin (WhatsApp)</label><input type="text" name="poster_contact" id="pContact" class="input" maxlength="60" value="{{ $val('poster_contact') }}"></div>
                <div class="pj-row">
                    <div class="field"><label for="pWeb">Website</label><input type="text" name="poster_website" id="pWeb" class="input" maxlength="100" value="{{ $val('poster_website') }}"></div>
                    <div class="field"><label for="pIg">Instagram</label><input type="text" name="poster_ig" id="pIg" class="input" maxlength="60" value="{{ $val('poster_ig') }}"></div>
                </div>
                <p class="field-hint" style="margin:-4px 0 12px">Contact & website otomatis terisi dari menu Kontak dan alamat website ini. Tulis tanda <b>-</b> untuk menyembunyikan.</p>
                <div class="field">
                    <label for="pMode">Tampilan nama bawaan</label>
                    <select name="poster_mode" id="pMode" class="input">@foreach (SP::MODES as $k => $label)<option value="{{ $k }}" @selected($val('poster_mode') === $k)>{{ $label }}</option>@endforeach</select>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Simpan & perbarui poster</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/vendor/html-to-image.js') }}"></script>
    <script>
    (function () {
        var frame = document.getElementById('pjFrame'), scale = document.getElementById('pjScale'), node = document.getElementById('posterNode');
        var msg = document.getElementById('pjMsg');
        var fileName = @json($fileName), caption = @json($caption);
        var sendUrl = @json(route('admin.poster.send')), waReady = @json($waReady), settingsUrl = @json($settingsUrl);
        var adminWa = @json($adminWa);

        function fit() { scale.style.transform = 'scale(' + (frame.clientWidth / 1080) + ')'; }
        fit(); window.addEventListener('resize', fit);

        function say(text, ok) { msg.className = 'pj-msg ' + (ok ? 'ok' : 'err'); msg.innerHTML = text; }

        // Font ditanam sebagai data URI supaya PNG memakai font poster yang sama
        var fontCss = null;
        function embedFonts() {
            if (fontCss) return Promise.resolve(fontCss);
            var rules = [];
            Array.prototype.forEach.call(document.styleSheets, function (sheet) {
                var list; try { list = sheet.cssRules; } catch (e) { return; }
                Array.prototype.forEach.call(list || [], function (r) { if (r.type === CSSRule.FONT_FACE_RULE && /PSerif|PSans/.test(r.cssText)) rules.push(r.cssText); });
            });
            return Promise.all(rules.map(function (css) {
                var m = css.match(/url\(["']?([^"')]+)["']?\)/);
                if (!m) return css;
                return fetch(m[1]).then(function (r) { return r.blob(); }).then(function (blob) {
                    return new Promise(function (res) { var fr = new FileReader(); fr.onload = function () { res(css.replace(m[0], 'url(' + fr.result + ')')); }; fr.readAsDataURL(blob); });
                });
            })).then(function (list) { fontCss = list.join('\n'); return fontCss; });
        }

        var cached = null;
        function render() {
            if (cached) return Promise.resolve(cached);
            return document.fonts.ready.then(embedFonts).then(function (css) {
                var opt = { width: 1080, height: 1350, pixelRatio: 1, fontEmbedCSS: css, style: { transform: 'none' } };
                // Safari (iPhone/iPad) sering melewatkan font & gambar di percobaan pertama: render 2x
                var safari = /^((?!chrome|android|crios|fxios).)*safari/i.test(navigator.userAgent);
                return safari ? htmlToImage.toPng(node, opt).then(function () { return htmlToImage.toPng(node, opt); }) : htmlToImage.toPng(node, opt);
            }).then(function (png) { cached = png; return png; });
        }
        function busy(btn, on, text) { btn.disabled = on; if (on) { btn.dataset.l = btn.textContent; btn.textContent = text || 'Membuat gambar…'; } else if (btn.dataset.l) { btn.textContent = btn.dataset.l; } }
        function toFile(png) { return fetch(png).then(function (r) { return r.blob(); }).then(function (b) { return new File([b], fileName, { type: 'image/png' }); }); }
        function download(png) { var a = document.createElement('a'); a.href = png; a.download = fileName; document.body.appendChild(a); a.click(); a.remove(); }

        document.getElementById('pjDownload').addEventListener('click', function () {
            var b = this; busy(b, true);
            render().then(function (png) { download(png); say('✓ Poster diunduh: <b>' + fileName + '</b>', true); })
                .catch(function (e) { say('Gagal membuat gambar: ' + (e && e.message ? e.message : e)); })
                .finally(function () { busy(b, false); });
        });

        document.getElementById('pjShare').addEventListener('click', function () {
            var b = this; busy(b, true);
            render().then(toFile).then(function (file) {
                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    return navigator.share({ files: [file], text: caption }).then(function () { say('✓ Pilih Instagram atau WhatsApp di menu bagikan.', true); }).catch(function () {});
                }
                download(cached);
                say('Browser ini belum bisa langsung membagikan gambar. Poster sudah diunduh, silakan upload manual ke Instagram / WhatsApp. (Di HP, tombol ini membuka menu bagikan.)', true);
            }).catch(function (e) { say('Gagal membuat gambar: ' + (e && e.message ? e.message : e)); })
              .finally(function () { busy(b, false); });
        });

        document.getElementById('pjSend').addEventListener('click', function () {
            var b = this;
            if (!waReady) {
                say('WhatsApp otomatis belum aktif. Isi token Fonnte/Wablas & nomor admin di <a href="' + settingsUrl + '">Settings → Notifikasi & WhatsApp</a>.' +
                    (adminWa ? ' Atau <a href="#" id="pjManual">unduh poster lalu buka WhatsApp</a>.' : ''));
                var man = document.getElementById('pjManual');
                if (man) man.addEventListener('click', function (e) { e.preventDefault(); render().then(function (png) { download(png); window.open('https://wa.me/' + adminWa + '?text=' + encodeURIComponent(caption), '_blank'); }); });
                return;
            }
            busy(b, true);
            render().then(function (png) {
                b.textContent = 'Mengirim…';
                return fetch(sendUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) }, body: JSON.stringify({ image: png, caption: caption }) });
            }).then(function (r) { return r.json().catch(function () { return { ok: false, detail: 'Server error ' + r.status }; }); })
              .then(function (d) { say((d.ok ? '✓ ' : '') + (d.detail || (d.ok ? 'Terkirim.' : 'Gagal mengirim.')), d.ok); })
              .catch(function (e) { say('Gagal mengirim: ' + (e && e.message ? e.message : e)); })
              .finally(function () { busy(b, false); });
        });

        var logo = document.getElementById('pjLogo');
        logo && logo.addEventListener('change', function () { if (logo.files[0]) document.getElementById('pjLogoPrev').style.backgroundImage = 'url(' + URL.createObjectURL(logo.files[0]) + ')'; });
    })();
    </script>
@endpush
