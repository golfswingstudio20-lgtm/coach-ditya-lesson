<style>
    .ev-paybox { margin: 6px 0 16px; padding: 16px; border: 1px solid var(--border); border-radius: 16px; background: var(--d-surface-2, #f8f7f2); }
    .ev-paybox-head { margin-bottom: 14px; }
    .ev-paybox-head strong { display: block; font-size: 15px; color: var(--text); }
    .ev-paybox-head span { display: block; margin-top: 3px; font-size: 12.5px; color: var(--text-muted); }
    .ev-qr-row { display: flex; gap: 12px; align-items: flex-start; }
    .ev-qr-prev { width: 96px; height: 96px; flex: 0 0 96px; object-fit: contain; border-radius: 12px; border: 1px solid var(--border); background: #fff; }
</style>
{{--
    Dipakai oleh create.blade.php dan edit.blade.php
    Variabel: $event (model), $action (url), $isEdit (bool)
--}}
@php
    $dateValue  = old('event_date', $event->event_date?->format('Y-m-d'));
    $startValue = old('start_time', $event->start_time ? substr($event->start_time, 0, 5) : '');
    $endValue   = old('end_time', $event->end_time ? substr($event->end_time, 0, 5) : '');
    $status     = old('registration_status', $event->registration_status ?? 'open');
    $isActive   = (bool) old('is_active', $event->is_active ?? true);
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="form-card" id="eventForm" @if (\App\Support\Uploads::directEnabled()) data-direct-upload="{{ route('admin.uploads.sign') }}" @endif>
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

    {{-- Poster --}}
    <div class="field">
        <label for="fPoster">Poster event</label>
        <input type="file" name="poster_file" id="fPoster" data-folder="events/posters" accept="image/*"
               class="input @error('poster_file') is-invalid @enderror">
        <p class="field-hint">
            JPG, PNG, atau WEBP. Ukuran tidak dibatasi. Disarankan poster tegak (portrait).
            @if ($isEdit && $event->poster) Kosongkan jika tidak ingin mengganti poster. @endif
        </p>
        @error('poster_file') <p class="field-error">{{ $message }}</p> @enderror

        <img id="posterPreview" class="poster-preview" alt="Pratinjau poster"
             src="{{ $event->poster_url }}" @if (! $event->poster_url) hidden @endif>
    </div>

    {{-- Judul & deskripsi --}}
    <div class="field">
        <label for="fTitle">Judul event</label>
        <input type="text" name="title" id="fTitle" maxlength="150" required
               class="input @error('title') is-invalid @enderror"
               value="{{ old('title', $event->title) }}" placeholder="Contoh: Golf Coaching Clinic">
        <p class="field-hint">Di halaman Event, kata pertama tampil putih dan sisanya hijau.</p>
        @error('title') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="field">
        <label for="fDescription">Deskripsi singkat</label>
        <textarea name="description" id="fDescription" maxlength="1000" class="input"
                  placeholder="Penjelasan singkat tentang event">{{ old('description', $event->description) }}</textarea>
    </div>

    {{-- Jadwal --}}
    <div class="field-row three">
        <div class="field">
            <label for="fDate">Tanggal</label>
            <input type="date" name="event_date" id="fDate" required
                   class="input @error('event_date') is-invalid @enderror" value="{{ $dateValue }}">
            @error('event_date') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="fStart">Jam mulai</label>
            <input type="time" name="start_time" id="fStart"
                   class="input @error('start_time') is-invalid @enderror" value="{{ $startValue }}">
            @error('start_time') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="fEnd">Jam selesai</label>
            <input type="time" name="end_time" id="fEnd"
                   class="input @error('end_time') is-invalid @enderror" value="{{ $endValue }}">
            @error('end_time') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="field-row">
        <div class="field">
            <label for="fLocation">Lokasi</label>
            <input type="text" name="location" id="fLocation" maxlength="150" class="input"
                   value="{{ old('location', $event->location) }}" placeholder="Contoh: Padang Golf Modernland, Tangerang">
        </div>

        <div class="field">
            <label for="fStatus">Status registrasi</label>
            <select name="registration_status" id="fStatus" class="input">
                @foreach (\App\Models\Event::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="field-hint">Tombol "Bayar Sekarang" hanya aktif jika registrasi dibuka.</p>
        </div>
    </div>

    {{-- Harga --}}
    <div class="field-row">
        <div class="field">
            <label for="fPrice">Harga tiket (Rupiah)</label>
            <input type="number" name="price" id="fPrice" min="0" step="1000" required
                   class="input @error('price') is-invalid @enderror"
                   value="{{ old('price', $event->price ?? 0) }}">
            <p class="field-hint" id="pricePreview"></p>
            @error('price') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="fUnit">Satuan harga</label>
            <input type="text" name="price_unit" id="fUnit" maxlength="30" class="input"
                   value="{{ old('price_unit', $event->price_unit ?? 'person') }}" placeholder="person">
            <p class="field-hint">Tampil sebagai "/ PERSON" di samping harga.</p>
        </div>
    </div>

    <div class="field">
        <label for="fNote">Catatan</label>
        <textarea name="note" id="fNote" maxlength="500" class="input"
                  placeholder="Catatan di bawah tombol pembayaran (opsional)">{{ old('note', $event->note) }}</textarea>
    </div>

    {{-- ================= PEMBAYARAN KE PENYELENGGARA ================= --}}
    <div class="ev-paybox">
        <div class="ev-paybox-head">
            <strong>💳 Pembayaran event</strong>
            <span>Member membayar langsung ke penyelenggara. Isi sesuai info dari penyelenggara; yang dikosongkan tidak ditampilkan.</span>
        </div>

        <div class="field">
            <label for="fOrganizer">Penyelenggara</label>
            <input type="text" name="organizer" id="fOrganizer" maxlength="120" class="input"
                   value="{{ old('organizer', $event->organizer) }}" placeholder="Contoh: Daddies Golfer x Fanser (Coach sebagai guest star)">
        </div>

        <div class="field-row">
            <div class="field">
                <label for="fRef">Kode referal Coach</label>
                <input type="text" name="referral_code" id="fRef" maxlength="40" class="input" style="text-transform:uppercase;letter-spacing:1px;font-weight:700"
                       value="{{ old('referral_code', $event->referral_code) }}" placeholder="Contoh: COACHDITYA">
                <p class="field-hint">Ditampilkan besar ke member + tombol salin.</p>
            </div>
            <div class="field">
                <label for="fRefNote">Keterangan kode referal</label>
                <input type="text" name="referral_note" id="fRefNote" maxlength="200" class="input"
                       value="{{ old('referral_note', $event->referral_note) }}" placeholder="Contoh: Tulis di berita transfer / sebutkan saat daftar">
            </div>
        </div>

        <div class="field">
            <label for="fPayQr">QR pembayaran (QRIS penyelenggara)</label>
            <div class="ev-qr-row">
                <img id="payQrPreview" class="ev-qr-prev" alt="QR" src="{{ $event->pay_qr_url }}" @if (! $event->pay_qr_url) hidden @endif>
                <div style="flex:1;min-width:0">
                    <input type="file" name="pay_qr_file" id="fPayQr" data-folder="events/payments" accept="image/*" class="input">
                    @if ($event->pay_qr)
                        <label class="checkbox"><input type="checkbox" name="remove_pay_qr" value="1"> Hapus QR</label>
                    @endif
                </div>
            </div>
        </div>

        <div class="field">
            <label for="fAccounts">Rekening / e-wallet</label>
            <textarea name="pay_accounts" id="fAccounts" maxlength="1000" class="input" rows="3"
                      placeholder="BCA | 1234567890 | a.n. PT Daddies Golf&#10;DANA | 0812-1234-5678 | a.n. Budi">{{ old('pay_accounts', $event->pay_accounts) }}</textarea>
            <p class="field-hint">Satu baris satu rekening, format: <b>Bank | Nomor | Atas nama</b>.</p>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="fPayContact">WhatsApp penyelenggara</label>
                <input type="text" name="pay_contact" id="fPayContact" maxlength="30" class="input"
                       value="{{ old('pay_contact', $event->pay_contact) }}" placeholder="0813-1026-0245">
                <p class="field-hint">Member bisa kirim bukti bayar + kode referal lewat tombol WhatsApp.</p>
            </div>
            <div class="field">
                <label for="fPayLink">Link pendaftaran penyelenggara (opsional)</label>
                <input type="url" name="pay_link" id="fPayLink" maxlength="500" class="input @error('pay_link') is-invalid @enderror"
                       value="{{ old('pay_link', $event->pay_link) }}" placeholder="https://...">
                @error('pay_link') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="field">
            <label for="fPayInfo">Cara bayar / instruksi</label>
            <textarea name="pay_instructions" id="fPayInfo" maxlength="1500" class="input" rows="3"
                      placeholder="Contoh: Transfer sesuai harga, lalu kirim bukti ke WhatsApp penyelenggara dengan menyebutkan kode referal.">{{ old('pay_instructions', $event->pay_instructions) }}</textarea>
        </div>
    </div>

    <div class="field">
        <span class="field-label">Tampilkan</span>
        <input type="hidden" name="is_active" value="0">
        <label class="checkbox">
            <input type="checkbox" name="is_active" value="1" @checked($isActive)>
            Tampilkan event ini di halaman Event
        </label>
    </div>

    <div class="form-actions">
        <a href="{{ route('admin.events.index') }}" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary" id="btnSubmit">
            {{ $isEdit ? 'Simpan perubahan' : 'Tambah event' }}
        </button>
    </div>
</form>

@push('scripts')
    <script>
        (() => {
            const posterInput = document.getElementById('fPoster');
            const preview     = document.getElementById('posterPreview');
            const priceInput  = document.getElementById('fPrice');
            const pricePreview = document.getElementById('pricePreview');

            const qrInput = document.getElementById('fPayQr'), qrPrev = document.getElementById('payQrPreview');
            qrInput && qrInput.addEventListener('change', () => {
                const f = qrInput.files[0]; if (!f) return;
                qrPrev.src = URL.createObjectURL(f); qrPrev.hidden = false;
            });

            posterInput.addEventListener('change', () => {
                const file = posterInput.files[0];
                if (!file) return;
                preview.src = URL.createObjectURL(file);
                preview.hidden = false;
            });

            function updatePrice() {
                const value = Number(priceInput.value || 0);
                pricePreview.textContent = value > 0
                    ? 'Tampil sebagai: Rp' + value.toLocaleString('id-ID')
                    : 'Tampil sebagai: Gratis';
            }

            priceInput.addEventListener('input', updatePrice);
            updatePrice();

            document.getElementById('eventForm').addEventListener('submit', () => {
                const btn = document.getElementById('btnSubmit');
                btn.disabled = true;
                btn.textContent = 'Menyimpan...';
            });
        })();
    </script>
@endpush
