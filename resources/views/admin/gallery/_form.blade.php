{{--
    Dipakai oleh create.blade.php dan edit.blade.php
    Variabel: $gallery (model), $action (url), $isEdit (bool)
--}}
@php
    $type        = old('type', $gallery->type ?? 'image');
    $videoSource = old('video_source', $gallery->video_file && ! $gallery->video_url
        ? 'upload'
        : (str_contains((string) $gallery->video_url, 'instagram.com') ? 'instagram' : 'youtube'));
    $isActive    = old('status', $gallery->status ?? 'active') === 'active';
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="form-card" id="galleryForm" @if (\App\Support\Uploads::directEnabled()) data-direct-upload="{{ route('admin.uploads.sign') }}" @endif>
    @csrf
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
            @if ($isEdit && $gallery->video_file)
                <p class="current-file">File saat ini: {{ basename($gallery->video_file) }}</p>
            @endif
            @error('video_upload') <p class="field-error">{{ $message }}</p> @enderror
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
                $('fVideoUrlLabel').textContent = isInsta ? 'Link Instagram (post / reel)' : 'Link YouTube';
                $('fVideoUrl').placeholder = isInsta ? 'https://www.instagram.com/reel/ABC123xyz/' : 'https://www.youtube.com/watch?v=...';
                $('fVideoUrlHint').textContent = isInsta
                    ? 'Salin dari tombol Bagikan > Salin tautan di Instagram. Akun harus publik. Upload thumbnail di bawah supaya kartunya tampil bagus.'
                    : 'Thumbnail diambil otomatis dari YouTube jika tidak mengunggah thumbnail sendiri.';

                photoInput.disabled = !isImage;
                thumbInput.disabled = isImage;
            }

            document.querySelectorAll('input[name="type"], input[name="video_source"]')
                .forEach((radio) => radio.addEventListener('change', syncFields));

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
