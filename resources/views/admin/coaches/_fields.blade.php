{{-- Isian profil About Coach. Dipakai di Admin > About Coach dan panel edit di halaman Home. Variabel: $coach --}}
@once
<style>
    .cf { display: grid; gap: 14px; font-family: inherit; color: var(--d-text, #17261d); }
    .cf-sec { margin-top: 6px; padding-top: 14px; border-top: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .09); color: var(--d-ink-green-2, #2a6444); font-size: 11.5px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase; }
    .cf-sec:first-child { margin-top: 0; padding-top: 0; border-top: 0; }
    .cf-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .cf-f { display: grid; gap: 6px; min-width: 0; align-content: start; }
    .cf-f > label, .cf-lbl { color: var(--d-text-2, #3c4a42); font-size: 13px; font-weight: 600; }
    .cf-f input[type=text], .cf-f input[type=number], .cf-f input[type=email], .cf-f textarea {
        width: 100%; min-height: 44px; padding: 10px 14px; border: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .16); border-radius: 12px;
        background: var(--d-surface, #fff); color: var(--d-text, #17261d); font: inherit; font-size: 14.5px; line-height: 1.45; outline: none;
    }
    .cf-f textarea { min-height: 96px; resize: vertical; }
    .cf-f textarea.tall { min-height: 130px; }
    .cf-f input:focus, .cf-f textarea:focus { border-color: var(--d-ink-green, #1f4d33); box-shadow: 0 0 0 4px rgba(var(--d-green-rgb, 31, 77, 51), .1); }
    .cf-hint { color: var(--d-muted, #77837b); font-size: 12px; line-height: 1.45; }
    .cf-photo { display: flex; gap: 14px; align-items: flex-start; }
    .cf-photo-prev { flex: 0 0 110px; aspect-ratio: 4 / 5; border-radius: 16px; border: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .1); background: var(--d-tint, #e5eee6) center top / cover; display: grid; place-items: center; color: var(--d-muted, #77837b); font-size: 11px; text-align: center; padding: 6px; }
    .cf-photo > div:last-child { flex: 1; min-width: 0; display: grid; gap: 8px; }
    .cf-file { display: inline-flex; align-items: center; justify-content: center; height: 42px; padding: 0 16px; border: 1px dashed #3d7d57; border-radius: 12px; color: var(--d-ink-green, #1f4d33); background: var(--d-tint-2, #f0f5ef); font-size: 13.5px; font-weight: 600; cursor: pointer; }
    .cf-file input { display: none; }
    .cf-check { display: flex; align-items: center; gap: 9px; color: var(--d-text-2, #3c4a42); font-size: 13.5px; cursor: pointer; }
    .cf-check input { width: 17px; height: 17px; accent-color: #1f4d33; }
    @media (max-width: 600px) { .cf-row { grid-template-columns: 1fr; } }
</style>
@endonce

<div class="cf">
    <div class="cf-sec">Judul bagian di Home</div>
    <div class="cf-row">
        <div class="cf-f">
            <label for="cfLabel">Label kecil</label>
            <input type="text" name="section_label" id="cfLabel" maxlength="60" value="{{ old('section_label', $coach->section_label) }}" placeholder="About coach">
        </div>
        <div class="cf-f">
            <label for="cfTitle">Judul</label>
            <input type="text" name="section_title" id="cfTitle" maxlength="120" value="{{ old('section_title', $coach->section_title) }}" placeholder="Kenali [coach] Anda">
            <span class="cf-hint">Kata di dalam [kurung siku] tampil hijau.</span>
        </div>
    </div>

    <div class="cf-sec">Profil coach</div>
    <div class="cf-photo">
        <div class="cf-photo-prev" data-cf-preview @if ($coach->photo_url) style="background-image:url('{{ $coach->photo_url }}')" @endif>
            @unless ($coach->photo_url) Belum ada foto @endunless
        </div>
        <div>
            <span class="cf-lbl">Foto coach</span>
            <label class="cf-file"><input type="file" name="photo_file" accept="image/*" data-folder="coaches" data-cf-photo>📷 Ganti foto</label>
            <span class="cf-hint">JPG, PNG, atau WEBP, ukuran bebas. Foto potret (tegak) paling bagus.</span>
            @if ($coach->photo)
                <label class="cf-check"><input type="checkbox" name="remove_photo" value="1"> Hapus foto</label>
            @endif
        </div>
    </div>

    <div class="cf-row">
        <div class="cf-f">
            <label for="cfName">Nama coach</label>
            <input type="text" name="name" id="cfName" maxlength="100" required value="{{ old('name', $coach->name) }}" placeholder="Coach Andi">
        </div>
        <div class="cf-f">
            <label for="cfBadge">Label di foto</label>
            <input type="text" name="badge" id="cfBadge" maxlength="40" value="{{ old('badge', $coach->badge) }}" placeholder="Head Coach">
        </div>
    </div>

    <div class="cf-f">
        <label for="cfRole">Jabatan / spesialisasi</label>
        <input type="text" name="role" id="cfRole" maxlength="120" value="{{ old('role', $coach->role) }}" placeholder="Head Coach · Swing & Driving">
    </div>

    <div class="cf-f">
        <label for="cfBio">Artikel / tentang coach</label>
        <textarea name="bio" id="cfBio" maxlength="1500" class="tall" placeholder="Cerita singkat: mulai main golf, gaya melatih, cocok untuk siapa.">{{ old('bio', $coach->bio) }}</textarea>
        <span class="cf-hint">Boleh beberapa paragraf (tekan Enter untuk baris baru). Maks. 1500 karakter.</span>
    </div>

    <div class="cf-row">
        <div class="cf-f">
            <label for="cfYears">Pengalaman melatih (tahun)</label>
            <input type="number" name="years_experience" id="cfYears" min="0" max="80" value="{{ old('years_experience', $coach->years_experience) }}" placeholder="12">
        </div>
        <div class="cf-f">
            <label for="cfStudents">Murid dilatih</label>
            <input type="text" name="students" id="cfStudents" maxlength="30" value="{{ old('students', $coach->students) }}" placeholder="450+">
        </div>
    </div>

    <div class="cf-sec">Pengalaman, sertifikasi & prestasi</div>
    <div class="cf-f">
        <label for="cfExp">Riwayat pengalaman</label>
        <textarea name="experiences" id="cfExp" class="tall" placeholder="2020 – sekarang | Head Coach Golf Booking Lesson&#10;2016 – 2020 | Coach di driving range">{{ old('experiences', $coach->experiences_text) }}</textarea>
        <span class="cf-hint">Satu baris satu pengalaman, format: <b>periode | keterangan</b>. Maks. 8 baris.</span>
    </div>
    <div class="cf-row">
        <div class="cf-f">
            <label for="cfCert">Sertifikasi / lisensi</label>
            <textarea name="certifications" id="cfCert" placeholder="Sertifikat Pelatih Golf Nasional">{{ old('certifications', $coach->certifications_text) }}</textarea>
            <span class="cf-hint">Satu per baris.</span>
        </div>
        <div class="cf-f">
            <label for="cfAch">Prestasi</label>
            <textarea name="achievements" id="cfAch" placeholder="Juara 1 Amateur Open 2012">{{ old('achievements', $coach->achievements_text) }}</textarea>
            <span class="cf-hint">Satu per baris.</span>
        </div>
    </div>
    <div class="cf-f">
        <label for="cfSkills">Keahlian yang diajarkan</label>
        <textarea name="skills" id="cfSkills" placeholder="Full swing&#10;Driving&#10;Short game">{{ old('skills', $coach->skills_text) }}</textarea>
        <span class="cf-hint">Satu per baris (atau pisahkan dengan koma). Maks. 8.</span>
    </div>
    <div class="cf-f">
        <label for="cfQuote">Kutipan / motto</label>
        <input type="text" name="quote" id="cfQuote" maxlength="200" value="{{ old('quote', $coach->quote) }}" placeholder="Swing yang konsisten dimulai dari dasar yang benar.">
    </div>

    <div>
        <input type="hidden" name="is_active" value="0">
        <label class="cf-check"><input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $coach->is_active ?? true))> Tampilkan bagian About Coach di halaman Home</label>
    </div>
</div>

@once
<script>
document.addEventListener('change', function (e) {
    var input = e.target.closest && e.target.closest('[data-cf-photo]');
    if (!input || !input.files || !input.files[0]) return;
    var prev = input.closest('form').querySelector('[data-cf-preview]');
    if (prev) { prev.style.backgroundImage = "url('" + URL.createObjectURL(input.files[0]) + "')"; prev.textContent = ''; }
});
</script>
@endonce
