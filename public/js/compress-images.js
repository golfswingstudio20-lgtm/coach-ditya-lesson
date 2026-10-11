/*
 * Kompres foto otomatis di browser sebelum di-upload (hemat penyimpanan & kuota Supabase,
 * dan foto HP besar tidak ditolak server).
 *
 * - Berlaku untuk semua <input type="file"> yang menerima gambar.
 * - Foto diperkecil ke sisi terpanjang maks. 1920 px (poster event 2048 px), JPEG kualitas 82%.
 * - Dilewati: file < 350 KB, GIF/SVG, logo, favicon, QR (nama input mengandung logo/favicon/qr),
 *   input dengan atribut data-no-compress, atau kalau hasilnya tidak lebih kecil.
 * - Kalau browser tidak bisa membaca fotonya (mis. HEIC di Chrome), file asli tetap dipakai.
 */
(function () {
    'use strict';
    if (window.__compressImages) return;
    window.__compressImages = true;

    var MIN_SIZE = 350 * 1024;
    var SKIP_NAME = /logo|favicon|qr/i;
    var pending = new WeakMap(); // input -> Promise

    function wanted(input) {
        if (input.hasAttribute('data-no-compress') || SKIP_NAME.test(input.name || '')) return false;
        var accept = (input.getAttribute('accept') || '').toLowerCase();
        return accept === '' || accept.indexOf('image/*') !== -1 || accept.indexOf('image/jpeg') !== -1 || accept.indexOf('image/jpg') !== -1;
    }

    function maxSide(input) {
        var custom = parseInt(input.getAttribute('data-max-size') || '', 10);
        if (custom > 0) return custom;
        return /poster/i.test(input.getAttribute('data-folder') || input.name || '') ? 2048 : 1920;
    }

    function load(file) {
        if (window.createImageBitmap) {
            return createImageBitmap(file, { imageOrientation: 'from-image' }).catch(function () { return createImageBitmap(file); });
        }
        return new Promise(function (resolve, reject) {
            var img = new Image();
            img.onload = function () { resolve(img); };
            img.onerror = reject;
            img.src = URL.createObjectURL(file);
        });
    }

    function compress(file, limit) {
        if (!/^image\/(jpeg|png|webp|heic|heif|bmp)$/i.test(file.type) || file.size < MIN_SIZE) return Promise.resolve(file);

        return load(file).then(function (img) {
            var w = img.width, h = img.height, scale = Math.min(1, limit / Math.max(w, h));
            var canvas = document.createElement('canvas');
            canvas.width = Math.round(w * scale);
            canvas.height = Math.round(h * scale);
            var ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff'; // PNG transparan -> latar putih saat jadi JPEG
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            if (img.close) img.close();

            return new Promise(function (resolve) {
                canvas.toBlob(function (blob) {
                    if (!blob || blob.size >= file.size) return resolve(file);
                    var name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                    resolve(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
                }, 'image/jpeg', 0.82);
            });
        }).catch(function () { return file; });
    }

    function note(input, text) {
        var el = input.parentNode.querySelector('[data-compress-note]');
        if (!el) {
            el = document.createElement('small');
            el.setAttribute('data-compress-note', '');
            el.style.cssText = 'display:block;margin-top:4px;font-size:11.5px;color:#3d7d57;font-weight:600';
            input.insertAdjacentElement('afterend', el);
        }
        el.textContent = text;
    }

    function size(n) { return n > 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.round(n / 1024) + ' KB'; }

    document.addEventListener('change', function (e) {
        var input = e.target;
        if (!input || input.type !== 'file' || !input.files || !input.files.length || !wanted(input)) return;

        var files = Array.prototype.slice.call(input.files);
        if (!files.some(function (f) { return /^image\//.test(f.type) && f.size >= MIN_SIZE; })) return;

        var before = files.reduce(function (s, f) { return s + f.size; }, 0);
        note(input, 'Mengecilkan foto…');

        var job = Promise.all(files.map(function (f) { return compress(f, maxSide(input)); })).then(function (out) {
            var after = out.reduce(function (s, f) { return s + f.size; }, 0);
            if (after < before && window.DataTransfer) {
                try {
                    var dt = new DataTransfer();
                    out.forEach(function (f) { dt.items.add(f); });
                    input.files = dt.files;
                    note(input, '✓ Foto dikecilkan: ' + size(before) + ' → ' + size(after));
                } catch (err) { note(input, ''); }
            } else {
                note(input, '');
            }
        }).finally(function () { pending.delete(input); });

        pending.set(input, job);
    }, true);

    // Tahan submit sampai kompres selesai (berjalan sebelum upload langsung ke Supabase)
    document.addEventListener('submit', function (e) {
        var form = e.target;
        var jobs = Array.prototype.slice.call(form.querySelectorAll('input[type=file]'))
            .map(function (i) { return pending.get(i); }).filter(Boolean);
        if (!jobs.length) return;

        e.preventDefault();
        e.stopImmediatePropagation();
        var submitter = e.submitter || null;
        Promise.all(jobs).then(function () {
            if (form.requestSubmit) form.requestSubmit(submitter && submitter.form === form ? submitter : undefined);
            else form.submit();
        });
    }, true);
})();
