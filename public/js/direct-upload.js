/*
 * Upload langsung ke Supabase Storage (tanpa batas ukuran dari server website).
 * Form: <form data-direct-upload="/admin/uploads/sign">
 * Input: <input type="file" name="image_file" data-folder="gallery/images">
 * Saat submit: setiap file dikirim langsung ke Supabase, lalu form hanya mengirim "<name>_path".
 */
(function () {
    'use strict';
    if (window.__directUpload) return; // jangan terpasang dua kali
    window.__directUpload = true;

    function token(form) {
        var i = form.querySelector('input[name="_token"]');
        var m = document.querySelector('meta[name="csrf-token"]');
        return (i && i.value) || (m && m.content) || '';
    }

    function size(n) {
        return n > 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB';
    }

    function put(url, file, onProgress) {
        return new Promise(function (resolve, reject) {
            var x = new XMLHttpRequest();
            x.open('PUT', url);
            x.setRequestHeader('Content-Type', file.type || 'application/octet-stream');
            x.setRequestHeader('cache-control', 'max-age=31536000');
            x.setRequestHeader('x-upsert', 'false');
            x.upload.onprogress = function (e) { if (e.lengthComputable) onProgress(e.loaded / e.total); };
            x.onload = function () {
                if (x.status >= 200 && x.status < 300) return resolve();
                var msg = 'Upload ditolak Supabase (' + x.status + ')';
                try { var j = JSON.parse(x.responseText); if (j.message || j.error) msg += ': ' + (j.message || j.error); } catch (e) {}
                if (x.status === 413) msg += '. Ukuran file melebihi batas di Supabase (Storage > Settings > Upload file size limit).';
                reject(new Error(msg));
            };
            x.onerror = function () { reject(new Error('Koneksi ke Supabase terputus. Coba lagi.')); };
            x.send(file);
        });
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.matches || !form.matches('form[data-direct-upload]') || form.dataset.uploaded === '1') return;

        var inputs = Array.prototype.filter.call(
            form.querySelectorAll('input[type=file][data-folder]'),
            function (i) { return !i.disabled && i.files && i.files.length; }
        );
        if (!inputs.length) return;

        e.preventDefault();
        e.stopImmediatePropagation();

        var btn = form.querySelector('[type=submit]');
        var label = btn ? btn.textContent : '';
        var status = form.querySelector('[data-upload-status]');
        if (!status) {
            status = document.createElement('p');
            status.setAttribute('data-upload-status', '');
            status.style.cssText = 'margin:10px 0 0;font-size:13px;font-weight:600;color:var(--text-soft,#3c4a42)';
            (btn ? btn.parentNode : form).insertBefore(status, btn ? btn.parentNode.firstChild : null);
        }
        if (btn) btn.disabled = true;

        var total = inputs.reduce(function (s, i) { return s + i.files[0].size; }, 0), done = 0;

        inputs.reduce(function (chain, input) {
            return chain.then(function () {
                var file = input.files[0];
                var body = new FormData();
                body.append('folder', input.dataset.folder);
                body.append('filename', file.name);
                return fetch(form.dataset.directUpload, {
                    method: 'POST', body: body, credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': token(form), 'Accept': 'application/json' }
                })
                    .then(function (r) { return r.json().then(function (d) { if (!r.ok) throw new Error(d.message || 'Gagal menyiapkan upload.'); return d; }); })
                    .then(function (d) {
                        return put(d.upload_url, file, function (p) {
                            var pct = Math.round(((done + file.size * p) / total) * 100);
                            status.textContent = 'Meng-upload ' + file.name + ' (' + size(file.size) + ')… ' + pct + '%';
                            if (btn) btn.textContent = 'Upload ' + pct + '%';
                        }).then(function () {
                            done += file.size;
                            var hidden = form.querySelector('input[type=hidden][name="' + input.name + '_path"]');
                            if (!hidden) {
                                hidden = document.createElement('input');
                                hidden.type = 'hidden'; hidden.name = input.name + '_path';
                                form.appendChild(hidden);
                            }
                            hidden.value = d.path;
                            input.disabled = true;
                        });
                    });
            });
        }, Promise.resolve())
            .then(function () {
                status.textContent = 'Upload selesai, menyimpan…';
                if (btn) btn.textContent = 'Menyimpan…';
                form.dataset.uploaded = '1';
                HTMLFormElement.prototype.submit.call(form);
            })
            .catch(function (err) {
                inputs.forEach(function (i) { i.disabled = false; });
                status.style.color = '#c9413a';
                status.textContent = err.message || 'Upload gagal.';
                if (btn) { btn.disabled = false; btn.textContent = label; }
            });
    }, true);
})();
