{{--
    Dipakai oleh create.blade.php dan edit.blade.php
    Variabel: $gallery (model), $action (url), $isEdit (bool)
--}}
@php
    $type        = old('type', $gallery->type ?? 'image');
    $videoSource = old('video_source', ($gallery->video_file && ! $gallery->video_url) || (! $gallery->exists && $gallery->album === 'instagram')
        ? 'upload'
        : (str_contains((string) $gallery->video_url, 'instagram.com') ? 'instagram' : 'youtube'));
    $isActive    = old('status', $gallery->status ?? 'active') === 'active';
    $albumNow    = old('album', $gallery->album ?: ($gallery->exists ? $gallery->album_key : request('album', '')));
    $albumIcons  = ['latihan' => '📸', 'prestasi' => '🏆', 'youtube' => '▶', 'instagram' => '◎'];
@endphp

<style>
    .album-pick { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .album-opt { position: relative; display: flex; align-items: center; gap: 10px; padding: 11px 12px; border: 1px solid var(--border); border-radius: 14px; background: var(--d-surface, #fff); cursor: pointer; }
    .album-opt input { position: absolute; opacity: 0; pointer-events: none; }
    .album-opt .ic { width: 36px; height: 36px; flex: 0 0 36px; display: grid; place-items: center; border-radius: 11px; background: var(--accent-soft); font-size: 17px; }
    .album-opt b { display: block; font-size: 13.5px; color: var(--text); }
    .album-opt small { display: block; font-size: 11.5px; color: var(--text-muted); line-height: 1.35; }
    .album-opt:has(input:checked) { border-color: var(--lime); box-shadow: 0 0 0 3px rgba(var(--d-green-rgb, 31, 77, 51), .12); }
    .album-opt:has(input:focus-visible) { outline: 2px solid var(--lime); outline-offset: 2px; }
    @media (max-width: 520px) { .album-pick { grid-template-columns: 1fr; } }
</style>

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="form-card" id="galleryForm" @if (\App\Support\Uploads::directEnabled()) data-direct-upload="{{ route('admin.uploads.sign') }}" @endif>
    @csrf
    {{-- File yang sudah ter-upload tetap dipakai kalau form kembali karena ada error --}}
    @foreach (['image_file_path', 'video_upload_path'] as $kept)
        @if (old($kept))<input type="hidden" name="{{ $kept }}" value="{{ old($kept) }}">@endif
    @endforeach
    @if ($isEdit)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="error-box">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="field">
        <span class="field-label">Jenis media</span>
        <div class="segmented">
            <input type="radio" name="type" id="typeImage" value="image" @checked($type === 'image')>
            <label for="typeImage">▧ Foto</label>
            <input type="radio" name="type" id="typeVideo" value="video" @checked($type === 'video')>
            <label for="typeVideo">▶ Video</label>
        </div>
    </div>

    <div class="field">
        <span class="field-label">Folder</span>
        <div class="album-pick" id="albumPick">
            @foreach (\App\Models\Gallery::albums() as $key => [$label, $desc, $kind])
                <label class="album-opt" data-kind="{{ $kind }}">
                    <input type="radio" name="album" value="{{ $key }}" @checked($albumNow === $key)>
                    <span class="ic">{{ $albumIcons[$key] ?? ($kind === 'video' ? '▶' : '📁') }}</span>
                    <span><b>{{ $label }}</b><small>{{ $desc }}</small></span>
                </label>
            @endforeach
        </div>
        <p class="field-hint">Folder dipilih otomatis sesuai jenis media, tapi bisa Anda ganti.</p>
    </div>

    <div class="field">
        <label for="fTitle">Judul</label>
        <input type="text" name="title" id="fTitle" maxlength="255" required
               class="input @error('title') is-invalid @enderror"
               value="{{ old('title', $gallery->title) }}" placeholder="Contoh: Golf Training">
        @error('title') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="field">
        <label for="fCategory">Kategori</label>
        <input type="text" name="category" id="fCategory" maxlength="100" class="input"
               value="{{ old('category', $gallery->category) }}" placeholder="Contoh: Beginner, Practice & Training">
        <p class="field-hint">Untuk foto tampil di bawah judul, untuk video tampil sebagai label di atas judul.</p>
    </div>

    <div class="field">
        <label for="fDescription">Deskripsi</label>
        <textarea name="description" id="fDescription" maxlength="1000" class="input"
                  placeholder="Penjelasan singkat (opsional)">{{ old('description', $gallery->description) }}</textarea>
    </div>

    {{-- ===================== FOTO ===================== --}}
    <div class="field" id="imageGroup">
        <label for="fImage">File foto</label>
        <input type="file" name="image_file" id="fImage" data-folder="gallery/images" accept="image/*"
               class="input @error('image_file') is-invalid @enderror">
        <p class="field-hint">
            JPG, PNG, WEBP, GIF, atau HEIC. Ukuran tidak dibatasi.
            @if ($isEdit && $gallery->image) Kosongkan jika tidak ingin mengganti foto. @endif
        </p>
        @error('image_file') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    {{-- ===================== VIDEO ===================== --}}
    <div id="videoGroup">
        <div class="field">
            <span class="field-label">Sumber video</span>
            <div class="segmented" style="grid-template-columns: repeat(3, 1fr)">
                <input type="radio" name="video_source" id="srcYoutube" value="youtube" @checked($videoSource === 'youtube')>
                <label for="srcYoutube">YouTube</label>
                <input type="radio" name="video_source" id="srcInstagram" value="instagram" @checked($videoSource === 'instagram')>
                <label for="srcInstagram">Instagram</label>
                <input type="radio" name="video_source" id="srcUpload" value="upload" @checked($videoSource === 'upload')>
                <label for="srcUpload">Upload file</label>
            </div>
        </div>

        <div class="field" id="youtubeGroup">
            <label for="fVideoUrl" id="fVideoUrlLabel">Link YouTube</label>
            <input type="url" name="video_url" id="fVideoUrl" maxlength="500"
                   class="input @error('video_url') is-invalid @enderror"
                   value="{{ old('video_url', $gallery->video_url) }}" placeholder="https://www.youtube.com/watch?v=...">
            <p class="field-hint" id="fVideoUrlHint">Thumbnail diambil otomatis dari YouTube jika tidak mengunggah thumbnail sendiri.</p>
            @error('video_url') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field" id="uploadGroup">
            <label for="fVideoUpload">File video</label>
            <input type="file" name="video_upload" id="fVideoUpload" data-folder="gallery/videos" accept="video/*"
                   class="input @error('video_upload') is-invalid @enderror">
            <p class="field-hint">
                MP4, WEBM, atau MOV. Ukuran tidak dibatasi.
                @if ($isEdit && $gallery->video_file) Kosongkan jika tidak ingin mengganti video. @endif
            </p>
            @if (old('video_upload_path'))
                <p class="current-file">✓ Video sudah ter-upload, tidak perlu pilih ulang.</p>
            @endif
            @if ($isEdit && $gallery->video_file)
                <p class="current-file">File saat ini: {{ basename($gallery->video_file) }}</p>
            @endif
            @error('video_upload') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field" id="socialGroup">
            <label for="fSocialUrl">Link Instagram / YouTube (opsional)</label>
            <input type="url" name="social_url" id="fSocialUrl" maxlength="500"
                   class="input @error('social_url') is-invalid @enderror"
                   value="{{ old('social_url', $gallery->social_url) }}" placeholder="https://www.instagram.com/reel/ABC123xyz/">
            <p class="field-hint">Video tetap diputar langsung di website. Link ini muncul sebagai tombol "Lihat di Instagram" supaya pengunjung bisa like &amp; follow.</p>
            @error('social_url') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="fThumb">Thumbnail (opsional)</label>
            <input type="file" name="image_file" id="fThumb" data-folder="gallery/images" accept="image/*" class="input" disabled>
            <p class="field-hint">Gambar sampul sebelum video diputar. Disarankan untuk video Instagram.</p>
        </div>
    </div>

    <img id="mediaPreview" class="preview" alt="Pratinjau" @if (! $gallery->thumbnail_url) hidden @endif
         src="{{ $gallery->thumbnail_url }}">

    <div class="field-row" style="margin-top: 16px;">
        <div class="field">
            <label for="fSort">Urutan tampil</label>
            <input type="number" name="sort_order" id="fSort" min="0" class="input"
                   value="{{ old('sort_order', $gallery->sort_order ?? 0) }}">
            <p class="field-hint">Angka kecil tampil lebih dulu.</p>
        </div>

        <div class="field">
            <span class="field-label">Status</span>
            <input type="hidden" name="status" value="inactive">
            <label class="checkbox">
                <input type="checkbox" name="status" value="active" @checked($isActive)>
                Tampilkan ke customer & visitor
            </label>
        </div>
    </div>

    <div class="form-actions">
        <a href="{{ route('admin.gallery.index') }}" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary" id="btnSubmit">
            {{ $isEdit ? 'Simpan perubahan' : 'Tambah ke galeri' }}
        </button>
    </div>
</form>

@push('scripts')
    <script>
        (() => {
            const $ = (id) => document.getElementById(id);

            const typeImage  = $('typeImage');
            const srcUpload  = $('srcUpload');
            const srcInsta   = $('srcInstagram');
            const photoInput = $('fImage');
            const thumbInput = $('fThumb');
            const preview    = $('mediaPreview');

            // Hanya satu input "image_file" yang aktif: foto (tipe foto) atau thumbnail (tipe video)
            function syncFields() {
                const isImage   = typeImage.checked;
                const isLink    = !srcUpload.checked;
                const isInsta   = srcInsta.checked;

                $('imageGroup').hidden   = !isImage;
                $('videoGroup').hidden   = isImage;
                $('youtubeGroup').hidden = !isLink;
                $('uploadGroup').hidden  = isLink;
                $('socialGroup').hidden  = isLink;
                $('fVideoUrlLabel').textContent = isInsta ? 'Link Instagram (post / reel)' : 'Link YouTube';
                $('fVideoUrl').placeholder = isInsta ? 'https://www.instagram.com/reel/ABC123xyz/' : 'https://www.youtube.com/watch?v=...';
                $('fVideoUrlHint').textContent = isInsta
                    ? 'Catatan: di HP, Instagram sering membuka aplikasinya sendiri saat video diputar. Supaya pasti bisa diputar di website, pilih "Upload file" lalu isi link Instagram di sana.'
                    : 'Thumbnail diambil otomatis dari YouTube jika tidak mengunggah thumbnail sendiri.';

                photoInput.disabled = !isImage;
                thumbInput.disabled = isImage;
            }

            document.querySelectorAll('input[name="type"], input[name="video_source"]')
                .forEach((radio) => radio.addEventListener('change', syncFields));

            // Pilih folder otomatis (kecuali admin sudah memilih sendiri)
            let albumTouched = !!document.querySelector('input[name="album"]:checked');
            document.querySelectorAll('input[name="album"]').forEach((r) => r.addEventListener('change', () => { albumTouched = true; }));
            function autoAlbum(force) {
                const cur = document.querySelector('input[name="album"]:checked');
                const isImage = typeImage.checked;
                const curKind = cur ? cur.closest('.album-opt').dataset.kind : null;
                if (albumTouched && !force && cur && curKind === (isImage ? 'image' : 'video')) return;
                let key = 'latihan';
                if (!isImage) {
                    const url = ($('fVideoUrl').value + ' ' + (($('fSocialUrl') || {}).value || '')).toLowerCase();
                    key = srcInsta.checked || url.includes('instagram.com') ? 'instagram'
                        : (!srcUpload.checked || url.includes('youtu') ? 'youtube' : 'instagram');
                    if (!srcUpload.checked && !srcInsta.checked && url.includes('instagram.com')) key = 'instagram';
                }
                const el = document.querySelector('input[name="album"][value="' + key + '"]');
                if (el) el.checked = true;
            }
            document.querySelectorAll('input[name="type"], input[name="video_source"]').forEach((r) => r.addEventListener('change', () => autoAlbum(false)));
            ['fVideoUrl', 'fSocialUrl'].forEach((id) => $(id) && $(id).addEventListener('change', () => autoAlbum(false)));
            if (!albumTouched) autoAlbum(true);

            [photoInput, thumbInput].forEach((input) => {
                input.addEventListener('change', () => {
                    const file = input.files[0];
                    if (!file) return;
                    preview.src = URL.createObjectURL(file);
                    preview.hidden = false;
                });
            });

            $('galleryForm').addEventListener('submit', () => {
                const btn = $('btnSubmit');
                btn.disabled = true;
                btn.textContent = 'Menyimpan...';
            });

            syncFields();
        })();
    </script>
@endpush
