{{--
    Dipakai oleh create.blade.php dan edit.blade.php
    Variabel: $program (model), $action (url), $isEdit (bool)
--}}
@php
    $position = old('image_position', $program->image_position ?? 'center');
    $isActive = (bool) old('is_active', $program->is_active ?? true);
    $positionCss = collect(\App\Models\Program::POSITIONS)->map(fn ($item) => $item[1]);
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="form-card" id="programForm" @if (\App\Support\Uploads::directEnabled()) data-direct-upload="{{ route('admin.uploads.sign') }}" @endif>
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

    {{-- Foto --}}
    <div class="field">
        <label for="fImage">Foto program</label>
        <input type="file" name="image_file" id="fImage" data-folder="programs" accept="image/*"
               class="input @error('image_file') is-invalid @enderror">
        <p class="field-hint">
            JPG, PNG, atau WEBP. Ukuran tidak dibatasi. Foto landscape paling cocok.
            @if ($isEdit && $program->image) Kosongkan jika tidak ingin mengganti foto. @else Jika dikosongkan, memakai gambar bawaan. @endif
        </p>
        @error('image_file') <p class="field-error">{{ $message }}</p> @enderror

        <div class="banner-preview" id="bannerPreview"
             style="--card-img: url('{{ $program->image_url }}'); --card-pos: {{ $positionCss[$position] ?? 'center' }};"></div>

        @if ($isEdit && $program->image)
            <label class="checkbox">
                <input type="checkbox" name="remove_image" value="1">
                Hapus foto dan kembali ke gambar bawaan
            </label>
        @endif
    </div>

    <div class="field">
        <label for="fPosition">Fokus foto</label>
        <select name="image_position" id="fPosition" class="input">
            @foreach (\App\Models\Program::POSITIONS as $value => $option)
                <option value="{{ $value }}" data-css="{{ $option[1] }}" @selected($position === $value)>{{ $option[0] }}</option>
            @endforeach
        </select>
        <p class="field-hint">Bagian foto yang terlihat di kartu. Lihat perubahannya langsung di pratinjau di atas.</p>
    </div>

    {{-- Isi kartu --}}
    <div class="field-row">
        <div class="field">
            <label for="fLevel">Level</label>
            <input type="text" name="level" id="fLevel" maxlength="50" required
                   class="input @error('level') is-invalid @enderror"
                   value="{{ old('level', $program->level) }}" placeholder="Contoh: BEGINNER">
            <p class="field-hint">Label hijau kecil di atas nama program.</p>
            @error('level') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="fName">Nama program</label>
            <input type="text" name="name" id="fName" maxlength="100" required
                   class="input @error('name') is-invalid @enderror"
                   value="{{ old('name', $program->name) }}" placeholder="Contoh: Beginner Program">
            @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="field">
        <label for="fDescription">Deskripsi singkat</label>
        <textarea name="description" id="fDescription" maxlength="255" class="input"
                  placeholder="Satu atau dua kalimat tentang program">{{ old('description', $program->description) }}</textarea>
    </div>

    <div class="field">
        <label for="fFeatures">Poin fitur</label>
        <textarea name="features" id="fFeatures" class="input" rows="5"
                  placeholder="Fundamental Swing&#10;Putting&#10;Short Game">{{ old('features', $program->features_text) }}</textarea>
        <p class="field-hint">Tulis satu poin per baris. Maksimal 8 poin.</p>
    </div>

    <div class="field-row">
        <div class="field">
            <label for="fSort">Urutan tampil</label>
            <input type="number" name="sort_order" id="fSort" min="0" class="input"
                   value="{{ old('sort_order', $program->sort_order ?? 0) }}">
            <p class="field-hint">Angka kecil tampil lebih dulu (dari kiri).</p>
        </div>

        <div class="field">
            <span class="field-label">Status</span>
            <input type="hidden" name="is_active" value="0">
            <label class="checkbox">
                <input type="checkbox" name="is_active" value="1" @checked($isActive)>
                Tampilkan di halaman Program
            </label>
        </div>
    </div>

    <div class="form-actions">
        <a href="{{ route('admin.programs.index') }}" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary" id="btnSubmit">
            {{ $isEdit ? 'Simpan perubahan' : 'Tambah program' }}
        </button>
    </div>
</form>

@push('scripts')
    <script>
        (() => {
            const imageInput = document.getElementById('fImage');
            const position   = document.getElementById('fPosition');
            const preview    = document.getElementById('bannerPreview');

            imageInput.addEventListener('change', () => {
                const file = imageInput.files[0];
                if (!file) return;
                preview.style.setProperty('--card-img', `url('${URL.createObjectURL(file)}')`);
            });

            position.addEventListener('change', () => {
                preview.style.setProperty('--card-pos', position.selectedOptions[0].dataset.css);
            });

            document.getElementById('programForm').addEventListener('submit', () => {
                const btn = document.getElementById('btnSubmit');
                btn.disabled = true;
                btn.textContent = 'Menyimpan...';
            });
        })();
    </script>
@endpush
