@extends('admin.layouts.panel')

@section('title', 'Settings')

@php
    use App\Models\Payment;
    use App\Models\Setting;
    use App\Support\BookingRules;
    use App\Support\Brand;
    use App\Support\Security;
    use App\Support\WhatsApp;

    $hero = Brand::hero();
@endphp

@push('styles')
    <style>
        .st-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }

        .st-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 42px;
            padding: 0 16px;
            border: 1px solid rgba(var(--d-green-rgb, 31, 77, 51), 0.2);
            border-radius: 999px;
            background: var(--d-surface, #ffffff);
            color: var(--d-text-2, #3c4a42);
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
        }

        .st-tab:hover { border-color: rgba(var(--d-green-rgb, 31, 77, 51), 0.5); }
        .st-tab.active { background: var(--lime); border-color: var(--lime); color: var(--ink-dark); }

        .st-tab.soon { opacity: .55; cursor: not-allowed; }
        .st-tab small { padding: 2px 7px; border-radius: 999px; background: rgba(var(--d-ink-rgb, 23, 46, 33), 0.060); font-size: 9px; }

        .st-card { padding: 22px; margin-bottom: 16px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); }
        .st-card h2 { font-size: 17px; font-weight: 900; }
        .st-card > p.st-sub { margin: 4px 0 18px; color: var(--text-muted); font-size: 12px; line-height: 1.5; }

        .st-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .st-grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }

        .st-box { padding: 14px; border: 1px solid var(--line); border-radius: 10px; background: var(--d-surface-2, #f8f7f2); }

        .st-upload { display: flex; gap: 14px; align-items: center; }
        .st-upload.stack { flex-direction: column; align-items: stretch; }

        .st-preview {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px dashed rgba(var(--d-green-rgb, 31, 77, 51), 0.35);
            border-radius: 10px;
            background: var(--d-surface-2, #f8f7f2);
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            overflow: hidden;
        }

        .st-preview img { width: 100%; height: 100%; object-fit: contain; }
        .st-preview.bg img { object-fit: cover; }
        .st-preview.logo { width: 76px; height: 76px; }
        .st-preview.icon { width: 52px; height: 52px; }
        .st-preview.bg { width: 100%; height: 120px; }

        .st-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            cursor: pointer;
        }

        .st-toggle strong { display: block; font-size: 13px; }
        .st-toggle small { display: block; margin-top: 3px; color: var(--text-muted); font-size: 11px; line-height: 1.5; }
        .st-toggle input { width: 20px; height: 20px; flex: 0 0 20px; accent-color: var(--lime); }

        .st-remove { display: inline-flex; align-items: center; gap: 6px; margin-top: 6px; color: var(--d-red-ink, #c9413a); font-size: 11px; font-weight: 700; cursor: pointer; }
        .st-remove input { accent-color: #d8233d; }

        .st-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--line); }

        .st-soon { padding: 50px 20px; text-align: center; color: var(--text-muted); font-size: 13px; line-height: 1.7; }
        .st-soon strong { display: block; color: var(--d-text, #17261d); font-size: 16px; margin-bottom: 6px; }

        .st-hero-preview {
            padding: 22px;
            border-radius: 10px;
            background-size: cover;
            background-position: center;
        }

        .st-hero-preview small { color: var(--d-ink-green, var(--lime)); font-size: 10px; font-weight: 800; letter-spacing: 2px; }
        .st-hero-preview h3 { margin-top: 6px; font-family: var(--fw-serif, Georgia, serif); font-size: 28px; font-weight: 600; line-height: 1.1; }
        .st-hero-preview h3 span { color: var(--d-ink-green, var(--lime)); }
        .st-hero-preview p { margin-top: 8px; max-width: 460px; color: var(--d-text-2, #3c4a42); font-size: 12px; line-height: 1.5; }

        .st-check { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 11px 0; border-bottom: 1px solid var(--line); }
        .st-check:last-child { border-bottom: 0; }
        .st-check strong { display: block; font-size: 13px; }
        .st-check small { display: block; margin-top: 2px; color: var(--text-muted); font-size: 11px; line-height: 1.5; }
        .st-badge { flex: 0 0 auto; padding: 4px 10px; border-radius: 999px; font-size: 10px; font-weight: 900; white-space: nowrap; }
        .st-badge.ok { background: rgba(var(--d-green-rgb, 31, 77, 51), 0.16); color: var(--d-ink-green, var(--lime)); }
        .st-badge.warn { background: rgba(255, 120, 130, .16); color: var(--d-red-ink, #c9413a); }
        .st-badge.muted { background: rgba(var(--d-ink-rgb, 23, 46, 33), 0.048); color: var(--text-soft); }
        .st-row-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .st-mini-btn {
            height: 32px; padding: 0 12px; display: inline-flex; align-items: center;
            border: 1px solid var(--line); border-radius: 7px; background: transparent;
            color: var(--text-soft); font-size: 11px; font-weight: 800; text-decoration: none; cursor: pointer;
        }
        .st-mini-btn:hover { border-color: rgba(var(--d-green-rgb, 31, 77, 51), 0.45); color: var(--d-ink-green, var(--lime)); }
        .st-mini-btn.danger:hover { border-color: rgba(216, 35, 61, .7); color: var(--d-red-ink, #c9413a); }
        .st-disabled { opacity: .55; }
        .st-code { font-family: Consolas, Menlo, monospace; font-size: 11px; color: var(--d-ink-green, var(--lime)); }

        @media (max-width: 1050px) { .st-grid-3 { grid-template-columns: 1fr; } }
        @media (max-width: 760px) { .st-grid-2 { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
    <section class="page-head">
        <div>
            <h1>Settings</h1>
            <p>Atur identitas usaha, tampilan, aturan booking, pembayaran, WhatsApp, keamanan, dan akun admin. Perubahan langsung berlaku setelah disimpan.</p>
        </div>
    </section>

    {{-- ================= TAB ================= --}}
    <nav class="st-tabs" aria-label="Kategori pengaturan">
        @foreach ($tabs as $key => $item)
            @if ($item['ready'])
                <a href="{{ route('admin.settings.index', ['tab' => $key]) }}"
                   class="st-tab {{ $tab === $key ? 'active' : '' }}"
                   @if ($tab === $key) aria-current="page" @endif>
                    {{ $item['label'] }}
                </a>
            @else
                <span class="st-tab soon" title="Segera hadir di {{ $item['stage'] }}">
                    {{ $item['label'] }} <small>{{ $item['stage'] }}</small>
                </span>
            @endif
        @endforeach
    </nav>

    @if ($errors->any())
        <div class="error-box">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ================= UMUM & BRANDING ================= --}}
    @if ($tab === 'general')
        <form method="POST" action="{{ route('admin.settings.general') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <section class="st-card">
                <h2>Identitas usaha</h2>
                <p class="st-sub">Tampil di navbar, judul tab browser, halaman maintenance, dan bukti pembayaran.</p>

                <div class="st-grid-2">
                    <div class="field">
                        <label for="sName">Nama usaha</label>
                        <input type="text" name="site_name" id="sName" class="input" maxlength="60" required
                               value="{{ old('site_name', Brand::name()) }}">
                        <p class="field-hint">Kata di tengah tampil hijau, misalnya Golf <strong style="color: var(--d-ink-green, var(--lime))">Booking</strong> Lesson.</p>
                    </div>
                    <div class="field">
                        <label for="sTagline">Slogan</label>
                        <input type="text" name="tagline" id="sTagline" class="input" maxlength="120"
                               value="{{ old('tagline', Brand::tagline()) }}">
                    </div>
                </div>

                <div class="st-grid-2">
                    <div class="st-box">
                        <div class="st-upload">
                            <div class="st-preview logo">
                                @if (Brand::logoUrl())
                                    <img src="{{ Brand::logoUrl() }}" alt="Logo saat ini">
                                @else
                                    <span style="font-size: 30px">⛳</span>
                                @endif
                            </div>
                            <div style="flex: 1; min-width: 0">
                                <label class="field-label" for="sLogo">Logo</label>
                                <input type="file" name="logo" id="sLogo" class="input" accept="image/png,image/webp">
                                <p class="field-hint">PNG atau WEBP, latar transparan, maks. 1 MB.</p>
                                @if (Setting::get('logo'))
                                    <label class="st-remove"><input type="checkbox" name="remove_logo" value="1"> Hapus, kembali ke ikon bawaan</label>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="st-box">
                        <div class="st-upload">
                            <div class="st-preview icon">
                                @if (Brand::faviconUrl())
                                    <img src="{{ Brand::faviconUrl() }}" alt="Favicon saat ini">
                                @else
                                    Ikon
                                @endif
                            </div>
                            <div style="flex: 1; min-width: 0">
                                <label class="field-label" for="sFavicon">Favicon (ikon tab browser)</label>
                                <input type="file" name="favicon" id="sFavicon" class="input" accept="image/png">
                                <p class="field-hint">PNG persegi, misalnya 512×512 px, maks. 512 KB.</p>
                                @if (Setting::get('favicon'))
                                    <label class="st-remove"><input type="checkbox" name="remove_favicon" value="1"> Hapus favicon</label>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if (\Illuminate\Support\Facades\Route::has('admin.contact.index'))
                    <p class="field-hint" style="margin-top: 14px">
                        Alamat lokasi, nomor WhatsApp publik, email, dan jam operasional diatur di menu
                        <a href="{{ route('admin.contact.index') }}" style="color: var(--d-ink-green, var(--lime))">Contact</a>.
                    </p>
                @endif
            </section>

            <section class="st-card">
                <h2>Mode maintenance</h2>
                <p class="st-sub">Tutup website sementara untuk customer & visitor. Admin tetap bisa login dan bekerja seperti biasa.</p>

                <input type="hidden" name="maintenance" value="0">
                <label class="st-toggle st-box">
                    <span>
                        <strong>Aktifkan mode maintenance</strong>
                        <small>Customer & visitor melihat halaman "Sedang dalam perbaikan" dan tidak bisa booking.</small>
                    </span>
                    <input type="checkbox" name="maintenance" value="1" @checked(old('maintenance', Brand::maintenance() ? '1' : '0') === '1')>
                </label>

                <div class="field" style="margin-top: 14px">
                    <label for="sMaintMsg">Pesan yang ditampilkan</label>
                    <textarea name="maintenance_message" id="sMaintMsg" class="input" maxlength="300">{{ old('maintenance_message', Brand::maintenanceMessage()) }}</textarea>
                </div>
            </section>

            <section class="st-card">
                <h2>Menu lanjutan</h2>
                <p class="st-sub">Sembunyikan tab Notifikasi & WhatsApp serta Keamanan dari menu Settings, misalnya saat presentasi. Fiturnya tetap terpasang dan bisa dimunculkan lagi kapan saja dari sini.</p>

                <input type="hidden" name="show_advanced_settings" value="0">
                <label class="st-toggle st-box">
                    <span>
                        <strong>Tampilkan tab Notifikasi & WhatsApp dan Keamanan</strong>
                        <small>Matikan untuk menyembunyikan kedua tab tersebut. Pengaturan yang sudah disimpan tetap berlaku.</small>
                    </span>
                    <input type="checkbox" name="show_advanced_settings" value="1"
                           @checked(old('show_advanced_settings', \App\Http\Controllers\AdminSettingController::showAdvanced() ? '1' : '0') === '1')>
                </label>
            </section>

            <div class="st-actions">
                <button type="submit" class="btn btn-primary">Simpan pengaturan umum</button>
            </div>
        </form>
    @endif

    {{-- ================= TAMPILAN ================= --}}
    @if ($tab === 'appearance')
        <form method="POST" action="{{ route('admin.settings.appearance') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <section class="st-card">
                <h2>Background</h2>
                <p class="st-sub">JPG atau WEBP landscape, disarankan minimal 1920×1080 px, maks. 3 MB. Kosong = foto lapangan golf bawaan.</p>

                <div class="st-grid-3">
                    @foreach ([
                        'bg_public' => ['Halaman publik', 'Home, Program, Galeri, Event, Contact, Booking', 'public'],
                        'bg_auth'   => ['Login & Register', 'Halaman masuk dan daftar akun', 'auth'],
                        'bg_admin'  => ['Panel admin', 'Latar belakang semua halaman admin', 'admin'],
                    ] as $field => $meta)
                        @php
                            [$label, $desc, $area] = $meta;
                        @endphp
                        <div class="st-box">
                            <div class="st-upload stack">
                                <div class="st-preview bg">
                                    <img src="{{ Brand::background($area) }}" alt="Background {{ strtolower($label) }} saat ini">
                                </div>
                                <div>
                                    <label class="field-label" for="{{ $field }}">{{ $label }}</label>
                                    <input type="file" name="{{ $field }}" id="{{ $field }}" class="input" accept="image/jpeg,image/webp">
                                    <p class="field-hint">{{ $desc }}</p>
                                    @if (Setting::get($field))
                                        <label class="st-remove"><input type="checkbox" name="remove_{{ $field }}" value="1"> Kembali ke bawaan</label>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="st-card">
                <h2>Teks & foto halaman website</h2>
                <p class="st-sub" style="margin-bottom: 12px">Semua judul, kalimat, tombol, dan foto di Home, Program, Galeri, Event, Kontak, dan footer sekarang diatur di menu <strong>Konten Website</strong>.</p>
                <a href="{{ route('admin.content.index') }}" class="btn btn-outline">✎ Buka Konten Website</a>
            </section>

            <div class="st-actions">
                <button type="submit" class="btn btn-primary">Simpan tampilan</button>
            </div>
        </form>
    @endif

    {{-- ================= BOOKING & JADWAL ================= --}}
    @if ($tab === 'booking')
        @php
            $bk = [
                'open_time'    => old('open_time', BookingRules::openTime()),
                'close_time'   => old('close_time', BookingRules::closeTime()),
                'slot_minutes' => (string) old('slot_minutes', BookingRules::slotMinutes()),
                'min_minutes'  => (string) old('min_minutes', BookingRules::minMinutes()),
                'cancel_days'  => (string) old('cancel_days', BookingRules::cancelDays()),
                'max_active'   => (string) old('max_active', BookingRules::maxActive()),
            ];
        @endphp

        <form method="POST" action="{{ route('admin.settings.booking') }}">
            @csrf
            @method('PUT')

            <section class="st-card">
                <h2>Jam operasional</h2>
                <p class="st-sub">Lesson coach dihitung per jam, jadi jadwal booking selalu dalam kelipatan 1 jam. Berlaku untuk booking customer, booking offline admin, dan reschedule.</p>

                <div class="st-grid-3">
                    <div class="field">
                        <label for="bOpen">Jam buka</label>
                        <input type="time" name="open_time" id="bOpen" class="input" required step="3600" value="{{ $bk['open_time'] }}">
                    </div>
                    <div class="field">
                        <label for="bClose">Jam tutup</label>
                        <input type="time" name="close_time" id="bClose" class="input" required step="3600" value="{{ $bk['close_time'] }}">
                    </div>
                    <div class="field">
                        <label for="bMin">Durasi minimal Lesson Driving Range</label>
                        <select name="min_minutes" id="bMin" class="input">
                            @foreach (['60' => '1 jam', '120' => '2 jam', '180' => '3 jam'] as $value => $label)
                                <option value="{{ $value }}" @selected($bk['min_minutes'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="bCancel">Batas cancel & reschedule</label>
                        <select name="cancel_days" id="bCancel" class="input">
                            @foreach (['0' => 'Sampai hari lesson', '1' => 'H-1 (sehari sebelumnya)', '2' => 'H-2', '3' => 'H-3'] as $value => $label)
                                <option value="{{ $value }}" @selected($bk['cancel_days'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="bMax">Booking aktif per customer</label>
                        <select name="max_active" id="bMax" class="input">
                            @foreach (['0' => 'Tanpa batas', '1' => '1 booking', '2' => '2 booking', '3' => '3 booking', '4' => '4 booking', '5' => '5 booking'] as $value => $label)
                                <option value="{{ $value }}" @selected($bk['max_active'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="field-hint">Booking pending & booked yang tanggalnya belum lewat.</p>
                    </div>
                </div>
            </section>

            <section class="st-card">
                <h2>Course Lesson</h2>
                <p class="st-sub">Sesi lesson di lapangan golf dengan jam dan harga tetap. Lapangan golf ditentukan sendiri oleh customer saat booking. Lesson Driving Range memakai harga per jam di tab Pembayaran.</p>

                <input type="hidden" name="course_enabled" value="0">
                <label class="st-toggle st-box" style="margin-bottom: 16px">
                    <span>
                        <strong>Aktifkan Course Lesson</strong>
                        <small>Customer bisa memilih Course Lesson di halaman booking.</small>
                    </span>
                    <input type="checkbox" name="course_enabled" value="1" @checked(old('course_enabled', BookingRules::courseEnabled() ? '1' : '0') === '1')>
                </label>

                <div class="st-grid-2">
                    <div class="field">
                        <label for="cStart">Jam mulai</label>
                        <input type="time" name="course_start" id="cStart" class="input" required step="3600" value="{{ old('course_start', BookingRules::courseStart()) }}">
                    </div>
                    <div class="field">
                        <label for="cEnd">Jam selesai</label>
                        <input type="time" name="course_end" id="cEnd" class="input" required step="3600" value="{{ old('course_end', BookingRules::courseEnd()) }}">
                    </div>
                </div>
                <p class="field-hint" style="margin-bottom: 12px">Harga Course Lesson diatur di tab <strong>Pembayaran → Harga lesson</strong>.</p>

                <div class="field">
                    <label for="cNote">Catatan harga</label>
                    <input type="text" name="course_note" id="cNote" class="input" maxlength="200"
                           value="{{ old('course_note', BookingRules::courseNote()) }}"
                           placeholder="Harga belum termasuk caddy fee, green fee, dan tip.">
                    <p class="field-hint">Tampil di halaman booking, pembayaran, dan Program setiap kali harga Course Lesson ditampilkan. Kosongkan jika tidak perlu.</p>
                </div>

            </section>

            <section class="st-card">
                <h2>Persetujuan</h2>
                <p class="st-sub">Cara booking online berubah dari Pending menjadi Booked.</p>

                <input type="hidden" name="auto_approve_paid" value="0">
                <label class="st-toggle st-box">
                    <span>
                        <strong>Setujui otomatis setelah lunas</strong>
                        <small>Booking online langsung berstatus Booked begitu pembayaran lunas, tanpa menunggu admin menekan Approve.</small>
                    </span>
                    <input type="checkbox" name="auto_approve_paid" value="1" @checked(old('auto_approve_paid', BookingRules::autoApprovePaid() ? '1' : '0') === '1')>
                </label>
            </section>

            <div class="st-actions">
                <button type="submit" class="btn btn-primary">Simpan aturan booking</button>
            </div>
        </form>
    @endif

    {{-- ================= PEMBAYARAN ================= --}}
    @if ($tab === 'payment')
        @php
            $qris    = Payment::methodConfig('qris');
            $banks   = ['mandiri' => Payment::methodConfig('mandiri'), 'bca' => Payment::methodConfig('bca')];
            $enabled = fn ($key) => old("pay_{$key}_enabled", Setting::get("pay_{$key}_enabled", '1')) === '1';
            $mode    = old('payment_mode', BookingRules::paymentMode());
            $savedAccount = fn ($key) => old("{$key}_account", Setting::get("{$key}_account"));
            $savedHolder  = fn ($key) => old("{$key}_holder", Setting::get("{$key}_holder"));
        @endphp

        <form method="POST" action="{{ route('admin.settings.payment') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <section class="st-card">
                <h2>Harga lesson</h2>
                <p class="st-sub">Harga yang tampil ke customer di halaman Booking, Pembayaran, dan Program. Harga baru hanya berlaku untuk tagihan baru, tagihan yang sudah dibuat tidak berubah.</p>

                <div class="st-grid-2">
                    <div class="st-box">
                        <label class="field-label" for="pPrice">Lesson Driving Range</label>
                        <input type="number" name="price_per_hour" id="pPrice" class="input" required min="0" step="1000"
                               value="{{ old('price_per_hour', BookingRules::pricePerHour()) }}">
                        <p class="field-hint" id="pricePreview"></p>
                    </div>
                    <div class="st-box">
                        <label class="field-label" for="cPrice">Course Lesson</label>
                        <input type="number" name="course_price" id="cPrice" class="input" required min="0" step="1000"
                               value="{{ old('course_price', BookingRules::coursePrice()) }}">
                        <p class="field-hint" id="coursePricePreview"></p>
                    </div>
                </div>
            </section>

            <section class="st-card">
                <h2>Mode pembayaran</h2>
                <p class="st-sub">Selama mode Demo, tidak ada uang sungguhan yang diterima. Pindah ke Live setelah QRIS / rekening asli siap.</p>

                <div class="st-grid-2">
                    <label class="st-toggle st-box" style="align-items: flex-start">
                        <span>
                            <strong>Demo (simulasi)</strong>
                            <small>QR dummy & rekening contoh. Customer menekan "Saya sudah bayar (simulasi)" dan pembayaran langsung lunas.</small>
                        </span>
                        <input type="radio" name="payment_mode" value="demo" @checked($mode === 'demo')>
                    </label>
                    <label class="st-toggle st-box" style="align-items: flex-start">
                        <span>
                            <strong>Live (uang sungguhan)</strong>
                            <small>Memakai QRIS & nomor rekening asli di bawah. Setiap pembayaran wajib dicek dan dikonfirmasi admin di Dashboard.</small>
                        </span>
                        <input type="radio" name="payment_mode" value="live" @checked($mode === 'live')>
                    </label>
                </div>
            </section>

            <section class="st-card">
                <h2>Aturan pembayaran</h2>
                <p class="st-sub">Batas waktu bayar dan persetujuan booking online.</p>

                <div class="st-grid-2">
                    <div class="field">
                        <label for="pDeadline">Batas waktu bayar</label>
                        <select name="payment_deadline_minutes" id="pDeadline" class="input">
                            @foreach (['15' => '15 menit', '30' => '30 menit', '45' => '45 menit', '60' => '1 jam', '120' => '2 jam'] as $value => $label)
                                <option value="{{ $value }}" @selected((string) old('payment_deadline_minutes', BookingRules::paymentDeadlineMinutes()) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="field-hint">Dihitung sejak booking dibuat. Jika customer belum mengirim bukti bayar (atau memilih cash), booking gagal otomatis dan admin mendapat notifikasi.</p>
                    </div>
                    <div class="field">
                        <span class="field-label">Persetujuan</span>
                        <input type="hidden" name="require_paid" value="0">
                        <label class="checkbox">
                            <input type="checkbox" name="require_paid" value="1" @checked(old('require_paid', BookingRules::requirePaidBeforeApprove() ? '1' : '0') === '1')>
                            Wajib lunas sebelum di-approve
                        </label>
                        <p class="field-hint">Tombol Approve dikunci sampai booking online lunas.</p>
                    </div>
                </div>
            </section>

            <section class="st-card">
                <h2>Metode pembayaran</h2>
                <p class="st-sub">
                    QR dan rekening di bawah tampil ke customer di halaman pembayaran.
                    Di mode <strong>Demo</strong>, kolom yang kosong diganti QR dummy / rekening contoh.
                    Di mode <strong>Live</strong>, setiap pembayaran harus dikonfirmasi admin di Dashboard setelah mengecek mutasi.
                </p>

                <div class="st-grid-3" style="align-items: start">

                    {{-- QRIS --}}
                    <div class="st-box">
                        <label class="st-toggle" style="margin-bottom: 12px">
                            <span><strong>QRIS</strong></span>
                            <input type="hidden" name="pay_qris_enabled" value="0">
                            <input type="checkbox" name="pay_qris_enabled" value="1" @checked($enabled('qris'))>
                        </label>

                        <div class="st-upload">
                            <div class="st-preview logo" style="width: 96px; height: 96px; background: var(--d-surface, #fff)">
                                @if ($qris['image'])
                                    <img src="{{ $qris['image'] }}" alt="QRIS saat ini">
                                @else
                                    <span style="color: var(--d-muted, #666)">QR dummy</span>
                                @endif
                            </div>
                            <div style="flex: 1; min-width: 0">
                                <label class="field-label" for="qImg">Gambar QRIS</label>
                                <input type="file" name="qris_image" id="qImg" class="input" accept="image/png,image/jpeg">
                                @if (Setting::get('qris_image'))
                                    <label class="st-remove"><input type="checkbox" name="remove_qris_image" value="1"> Hapus, kembali ke QR dummy</label>
                                @endif
                            </div>
                        </div>
                        <p class="field-hint">QR statis dari bank / penyedia QRIS, PNG atau JPG maks. 2 MB.</p>

                        <div class="field" style="margin-top: 12px">
                            <label for="qMerchant">Nama merchant</label>
                            <input type="text" name="qris_merchant" id="qMerchant" class="input" maxlength="60" value="{{ old('qris_merchant', $qris['merchant']) }}">
                        </div>
                        <div class="field">
                            <label for="qNmid">NMID</label>
                            <input type="text" name="qris_nmid" id="qNmid" class="input" maxlength="30" value="{{ old('qris_nmid', $qris['nmid']) }}" placeholder="Dari penyedia QRIS">
                        </div>
                    </div>

                    {{-- BANK --}}
                    @foreach ($banks as $key => $bank)
                        <div class="st-box">
                            <label class="st-toggle" style="margin-bottom: 12px">
                                <span><strong>{{ $bank['label'] }}</strong></span>
                                <input type="hidden" name="pay_{{ $key }}_enabled" value="0">
                                <input type="checkbox" name="pay_{{ $key }}_enabled" value="1" @checked($enabled($key))>
                            </label>

                            <div>
                                <div class="field">
                                    <label for="{{ $key }}Account">Nomor rekening</label>
                                    <input type="text" name="{{ $key }}_account" id="{{ $key }}Account" class="input" inputmode="numeric" maxlength="30"
                                           value="{{ $savedAccount($key) }}" placeholder="Demo: {{ \App\Models\Payment::DEMO_ACCOUNTS[$key]['account'] }}">
                                </div>
                                <div class="field">
                                    <label for="{{ $key }}Holder">Atas nama</label>
                                    <input type="text" name="{{ $key }}_holder" id="{{ $key }}Holder" class="input" maxlength="60"
                                           value="{{ $savedHolder($key) }}" placeholder="Nama pemilik rekening">
                                </div>
                                <p class="field-hint">Demo: jika kosong, customer melihat rekening contoh. Live: wajib diisi rekening asli.</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="st-card">
                <h2>Bayar cash</h2>
                <p class="st-sub">Customer booking lewat website, lalu membayar tunai langsung ke admin saat lesson. Admin menandai lunas di Dashboard, dan waktu pembayarannya tercatat otomatis.</p>

                <div class="st-grid-2">
                    <label class="st-toggle st-box">
                        <span>
                            <strong>Aktifkan bayar cash</strong>
                            <small>Pilihan "Bayar Cash" tampil di halaman pembayaran customer.</small>
                        </span>
                        <input type="hidden" name="pay_cash_enabled" value="0">
                        <input type="checkbox" name="pay_cash_enabled" value="1" @checked(old('pay_cash_enabled', Setting::get('pay_cash_enabled', '1')) === '1')>
                    </label>
                    <label class="st-toggle st-box">
                        <span>
                            <strong>Izinkan cash untuk Course Lesson</strong>
                            <small>Jika mati, Course Lesson tetap wajib dibayar di muka lewat QRIS / transfer.</small>
                        </span>
                        <input type="hidden" name="cash_allow_course" value="0">
                        <input type="checkbox" name="cash_allow_course" value="1" @checked(old('cash_allow_course', Setting::get('cash_allow_course', '0')) === '1')>
                    </label>
                </div>
            </section>

            <div class="st-actions">
                <button type="submit" class="btn btn-primary">Simpan pengaturan pembayaran</button>
            </div>
        </form>
    @endif

    {{-- ================= NOTIFIKASI & WHATSAPP ================= --}}
    @if ($tab === 'whatsapp')
        @php
            $waProvider = old('wa_provider', WhatsApp::provider());
            $lastTest   = json_decode((string) Setting::get('wa_last_test'), true);
        @endphp

        <form method="POST" action="{{ route('admin.settings.whatsapp') }}">
            @csrf
            @method('PUT')

            <section class="st-card">
                <h2>Konfigurasi API WhatsApp</h2>
                <p class="st-sub">
                    Lonceng notifikasi di aplikasi selalu aktif. WhatsApp dikirim sebagai tambahan ke nomor admin setelah konfigurasi di bawah diisi.
                    Daftar ke <strong>Fonnte</strong> (fonnte.com) atau <strong>Wablas</strong> (wablas.com), hubungkan nomor WhatsApp bisnis, lalu salin token API-nya ke sini.
                </p>

                <div class="st-grid-2">
                    <div class="field">
                        <label for="waProvider">Layanan</label>
                        <select name="wa_provider" id="waProvider" class="input">
                            @foreach (WhatsApp::PROVIDERS as $value => $label)
                                <option value="{{ $value }}" @selected($waProvider === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field" data-provider-only="wablas">
                        <label for="waUrl">URL server Wablas</label>
                        <input type="url" name="wa_wablas_url" id="waUrl" class="input" maxlength="200"
                               value="{{ old('wa_wablas_url', WhatsApp::wablasUrl()) }}" placeholder="https://solo.wablas.com">
                        <p class="field-hint">Lihat di dashboard Wablas, bagian device / API.</p>
                    </div>

                    <div class="field">
                        <label for="waToken">Token API</label>
                        <input type="password" name="wa_token" id="waToken" class="input" maxlength="500" autocomplete="off"
                               placeholder="{{ WhatsApp::hasToken() ? '•••••••• (tersimpan, kosongkan jika tidak diganti)' : 'Tempel token dari Fonnte / Wablas' }}">
                        <p class="field-hint">Disimpan terenkripsi dan tidak pernah ditampilkan ulang.</p>
                        @if (WhatsApp::hasToken())
                            <label class="st-remove"><input type="checkbox" name="remove_wa_token" value="1"> Hapus token & nonaktifkan WhatsApp</label>
                        @endif
                    </div>

                    <div class="field" data-provider-only="wablas">
                        <label for="waSecret">Secret key Wablas (opsional)</label>
                        <input type="password" name="wa_secret" id="waSecret" class="input" maxlength="200" autocomplete="off"
                               placeholder="{{ WhatsApp::secret() ? '•••••••• (tersimpan)' : 'Kosongkan jika tidak memakai secret key' }}">
                    </div>

                    <div class="field">
                        <label for="waAdmin">Nomor WhatsApp admin (penerima)</label>
                        <input type="text" name="wa_admin_numbers" id="waAdmin" class="input" maxlength="300" inputmode="tel"
                               value="{{ old('wa_admin_numbers', Setting::get('wa_admin_numbers')) }}" placeholder="0858 8680 3126, 0812 xxxx xxxx">
                        <p class="field-hint">Pisahkan dengan koma untuk lebih dari satu admin.</p>
                    </div>
                </div>

                <div class="st-box" style="display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap">
                    <div>
                        <strong style="font-size: 13px">Status koneksi</strong>
                        <p class="field-hint" style="margin-top: 3px">
                            @if (! WhatsApp::enabled())
                                Belum aktif. Pilih layanan, isi token & nomor admin, lalu simpan.
                            @elseif ($lastTest)
                                Uji terakhir {{ \Carbon\Carbon::parse($lastTest['at'])->locale('id')->diffForHumans() }}: {{ $lastTest['detail'] }}
                            @else
                                Tersimpan. Klik "Kirim pesan uji" untuk memastikan koneksi berhasil.
                            @endif
                        </p>
                    </div>
                    @if (! WhatsApp::enabled())
                        <span class="st-badge muted">Nonaktif</span>
                    @elseif ($lastTest)
                        <span class="st-badge {{ $lastTest['ok'] ? 'ok' : 'warn' }}">{{ $lastTest['ok'] ? 'Terhubung' : 'Gagal' }}</span>
                    @else
                        <span class="st-badge muted">Belum diuji</span>
                    @endif
                </div>
            </section>

            <section class="st-card">
                <h2>Kirim WhatsApp ke admin saat</h2>
                <p class="st-sub">Pesan sama dengan notifikasi di lonceng, ditambah link untuk membuka dashboard.</p>

                <div class="st-grid-2">
                    @foreach (WhatsApp::ADMIN_EVENTS as $event => $label)
                        <input type="hidden" name="wa_admin_{{ $event }}" value="0">
                        <label class="st-toggle st-box">
                            <span><strong>{{ $label }}</strong></span>
                            <input type="checkbox" name="wa_admin_{{ $event }}" value="1" @checked(old("wa_admin_{$event}", WhatsApp::adminEventEnabled($event) ? '1' : '0') === '1')>
                        </label>
                    @endforeach
                </div>

                <div class="field" style="margin-top: 16px">
                    <label for="waTemplate">Template pesan</label>
                    <textarea name="wa_template" id="waTemplate" class="input" style="height: 110px; font-family: Consolas, Menlo, monospace" maxlength="1000">{{ old('wa_template', WhatsApp::template()) }}</textarea>
                    <p class="field-hint">
                        Kata berikut diganti otomatis:
                        <span class="st-code">{judul}</span>, <span class="st-code">{pesan}</span>,
                        <span class="st-code">{link}</span>, <span class="st-code">{usaha}</span>.
                        Teks di antara bintang (*seperti ini*) tampil tebal di WhatsApp.
                    </p>
                </div>
            </section>

            <section class="st-card st-disabled">
                <h2>Kirim WhatsApp ke customer</h2>
                <p class="st-sub" style="margin-bottom: 0">
                    Belum tersedia: akun customer belum menyimpan nomor WhatsApp (form registrasi hanya meminta nama, email, dan password).
                    Fitur ini bisa diaktifkan setelah kolom nomor WhatsApp ditambahkan ke registrasi & profil customer.
                    Sementara itu, customer tetap menerima notifikasi di lonceng.
                </p>
            </section>

            <div class="st-actions" style="justify-content: space-between">
                <span></span>
                <button type="submit" class="btn btn-primary">Simpan pengaturan WhatsApp</button>
            </div>
        </form>

        @if (WhatsApp::enabled())
            <form method="POST" action="{{ route('admin.settings.whatsapp.test') }}" style="margin-top: -6px">
                @csrf
                <button type="submit" class="btn btn-outline">Kirim pesan uji ke {{ implode(', ', WhatsApp::adminNumbers()) }}</button>
            </form>
        @endif
    @endif

    {{-- ================= KEAMANAN ================= --}}
    @if ($tab === 'security')
        @php
            $twoFactorRoute = Security::twoFactorRoute();
            $sessions = Security::usesDatabaseSessions()
                ? \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $user->id)
                    ->orderByDesc('last_activity')
                    ->get()
                : collect();

            $device = function (?string $agent) {
                $agent = (string) $agent;
                $browser = match (true) {
                    str_contains($agent, 'Edg/')    => 'Edge',
                    str_contains($agent, 'OPR/')    => 'Opera',
                    str_contains($agent, 'Chrome/') => 'Chrome',
                    str_contains($agent, 'Firefox/') => 'Firefox',
                    str_contains($agent, 'Safari/') => 'Safari',
                    default                          => 'Browser',
                };
                $os = match (true) {
                    str_contains($agent, 'Windows')   => 'Windows',
                    str_contains($agent, 'iPhone')    => 'iPhone',
                    str_contains($agent, 'iPad')      => 'iPad',
                    str_contains($agent, 'Android')   => 'Android',
                    str_contains($agent, 'Mac OS')    => 'macOS',
                    str_contains($agent, 'Linux')     => 'Linux',
                    default                           => 'Perangkat',
                };
                return "{$browser} · {$os}";
            };
        @endphp

        <form method="POST" action="{{ route('admin.settings.security') }}" class="st-card">
            @csrf
            @method('PUT')

            <h2>Login & sesi admin</h2>
            <p class="st-sub">Melindungi akun dari tebak-tebakan password dan dari perangkat yang lupa ditinggal login.</p>

            <div class="st-grid-3">
                <div class="field">
                    <label for="sAttempts">Batas percobaan login</label>
                    <select name="login_max_attempts" id="sAttempts" class="input">
                        @foreach (['3' => '3 kali', '5' => '5 kali', '10' => '10 kali'] as $value => $label)
                            <option value="{{ $value }}" @selected((string) old('login_max_attempts', Security::loginAttempts()) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="field-hint">Berlaku untuk semua akun (admin & customer).</p>
                </div>
                <div class="field">
                    <label for="sLock">Lama dikunci setelah gagal</label>
                    <select name="login_lock_minutes" id="sLock" class="input">
                        @foreach (['1' => '1 menit', '5' => '5 menit', '15' => '15 menit', '60' => '1 jam'] as $value => $label)
                            <option value="{{ $value }}" @selected((string) old('login_lock_minutes', Security::lockMinutes()) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="sIdle">Admin keluar otomatis jika tidak aktif</label>
                    <select name="admin_idle_minutes" id="sIdle" class="input">
                        @foreach (['0' => 'Tidak pernah', '15' => '15 menit', '30' => '30 menit', '120' => '2 jam', '480' => '8 jam'] as $value => $label)
                            <option value="{{ $value }}" @selected((string) old('admin_idle_minutes', Security::adminIdleMinutes()) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <input type="hidden" name="require_admin_2fa" value="0">
            <label class="st-toggle st-box">
                <span>
                    <strong>Wajibkan verifikasi 2 langkah (2FA) untuk admin</strong>
                    <small>
                        Admin harus memasukkan kode dari aplikasi authenticator (Google Authenticator, dsb.) setiap login.
                        Admin yang belum mengaktifkan 2FA akan diarahkan ke halaman pengaturan 2FA.
                        @if ($user->two_factor_confirmed_at)
                            <strong style="color: var(--d-ink-green, var(--lime))">2FA akun Anda sudah aktif.</strong>
                        @else
                            Aktifkan dulu 2FA di akun Anda sendiri.
                        @endif
                    </small>
                </span>
                <input type="checkbox" name="require_admin_2fa" value="1" @checked(old('require_admin_2fa', Security::requireAdmin2fa() ? '1' : '0') === '1')>
            </label>

            <div class="st-actions" style="justify-content: space-between; flex-wrap: wrap">
                @if ($twoFactorRoute)
                    <a href="{{ route($twoFactorRoute) }}" class="btn btn-outline">Atur 2FA akun saya</a>
                @else
                    <span class="field-hint">Halaman pengaturan 2FA tidak ditemukan.</span>
                @endif
                <button type="submit" class="btn btn-primary">Simpan pengaturan keamanan</button>
            </div>
        </form>

        <section class="st-card">
            <div class="section-card-head" style="margin-bottom: 8px">
                <div>
                    <h2>Perangkat yang sedang login</h2>
                    <p class="st-sub" style="margin: 4px 0 0">Akun admin Anda: {{ $user->email }}</p>
                </div>
                @if ($sessions->count() > 1)
                    <form method="POST" action="{{ route('admin.settings.sessions.others') }}" data-confirm="Keluarkan semua perangkat lain dari akun ini?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="st-mini-btn danger">Keluarkan semua perangkat lain</button>
                    </form>
                @endif
            </div>

            @if (! Security::usesDatabaseSessions())
                <p class="field-hint">Daftar perangkat hanya tersedia jika SESSION_DRIVER=database di file .env.</p>
            @elseif ($sessions->isEmpty())
                <p class="field-hint">Tidak ada data sesi.</p>
            @else
                @foreach ($sessions as $session)
                    @php $isCurrent = $session->id === session()->getId(); @endphp
                    <div class="st-check">
                        <div>
                            <strong>
                                {{ $device($session->user_agent) }}
                                @if ($isCurrent) <span class="st-badge ok" style="margin-left: 6px">Perangkat ini</span> @endif
                            </strong>
                            <small>
                                IP {{ $session->ip_address ?? '-' }} ·
                                aktif {{ \Carbon\Carbon::createFromTimestamp($session->last_activity)->locale('id')->diffForHumans() }}
                            </small>
                        </div>
                        @unless ($isCurrent)
                            <form method="POST" action="{{ route('admin.settings.sessions.destroy', $session->id) }}" data-confirm="Keluarkan perangkat ini?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="st-mini-btn danger">Keluarkan</button>
                            </form>
                        @endunless
                    </div>
                @endforeach
            @endif
        </section>

        <section class="st-card">
            <div class="section-card-head" style="margin-bottom: 8px">
                <div>
                    <h2>Backup database</h2>
                    <p class="st-sub" style="margin: 4px 0 0">Menyimpan salinan seluruh data (booking, pembayaran, customer, pengaturan). 14 backup terakhir disimpan.</p>
                </div>
                @if (Security::backupSupported())
                    <form method="POST" action="{{ route('admin.settings.backups.store') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline">Buat backup sekarang</button>
                    </form>
                @endif
            </div>

            @if (! Security::backupSupported())
                <p class="field-hint">
                    Database Anda memakai <span class="st-code">{{ config('database.default') }}</span>.
                    Backup dari aplikasi hanya untuk SQLite. Untuk MySQL, gunakan fitur backup di hosting atau phpMyAdmin.
                </p>
            @elseif (empty(Security::backups()))
                <p class="field-hint">Belum ada backup.</p>
            @else
                @foreach (Security::backups() as $backup)
                    <div class="st-check">
                        <div>
                            <strong>{{ $backup['name'] }}</strong>
                            <small>
                                {{ \Carbon\Carbon::createFromTimestamp($backup['time'])->locale('id')->translatedFormat('d M Y, H:i') }} ·
                                {{ number_format($backup['size'] / 1024, 0, ',', '.') }} KB
                            </small>
                        </div>
                        <div class="st-row-actions">
                            <a href="{{ route('admin.settings.backups.download', $backup['name']) }}" class="st-mini-btn">Unduh</a>
                            <form method="POST" action="{{ route('admin.settings.backups.destroy', $backup['name']) }}" data-confirm="Hapus backup {{ $backup['name'] }}?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="st-mini-btn danger">Hapus</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            @endif
        </section>
    @endif

    {{-- ================= TAB TAHAP BERIKUTNYA ================= --}}
    @if (! ($tabs[$tab]['ready'] ?? false))
        <section class="st-card st-soon">
            <strong>{{ $tabs[$tab]['label'] }}</strong>
            Bagian ini akan tersedia di {{ $tabs[$tab]['stage'] }}.
        </section>
    @endif

    {{-- ================= AKUN ADMIN ================= --}}
    @if ($tab === 'account')
        <form method="POST" action="{{ route('admin.settings.profile') }}" class="st-card">
            @csrf
            @method('PUT')

            <h2>Profil admin</h2>
            <p class="st-sub">Nama dan email untuk login. Masukkan password saat ini untuk menyimpan.</p>

            <div class="st-grid-3">
                <div class="field">
                    <label for="aName">Nama</label>
                    <input type="text" name="name" id="aName" class="input" required maxlength="255"
                           value="{{ old('name', $user->name) }}">
                </div>
                <div class="field">
                    <label for="aEmail">Email</label>
                    <input type="email" name="email" id="aEmail" class="input" required maxlength="255"
                           value="{{ old('email', $user->email) }}" autocomplete="email">
                </div>
                <div class="field">
                    <label for="aCurrent1">Password saat ini</label>
                    <input type="password" name="current_password" id="aCurrent1" class="input" required autocomplete="current-password">
                </div>
            </div>

            <div class="st-actions">
                <button type="submit" class="btn btn-primary">Simpan profil</button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.settings.password') }}" class="st-card">
            @csrf
            @method('PUT')

            <h2>Ganti password</h2>
            <p class="st-sub">Minimal 8 karakter. Setelah diganti, login di perangkat lain akan dikeluarkan.</p>

            <div class="st-grid-3">
                <div class="field">
                    <label for="aCurrent2">Password saat ini</label>
                    <input type="password" name="current_password" id="aCurrent2" class="input" required autocomplete="current-password">
                </div>
                <div class="field">
                    <label for="aNew">Password baru</label>
                    <input type="password" name="password" id="aNew" class="input" required minlength="8" autocomplete="new-password">
                </div>
                <div class="field">
                    <label for="aConfirm">Ulangi password baru</label>
                    <input type="password" name="password_confirmation" id="aConfirm" class="input" required minlength="8" autocomplete="new-password">
                </div>
            </div>

            <div class="st-actions">
                <button type="submit" class="btn btn-primary">Ganti password</button>
            </div>
        </form>

        <section class="st-card">
            <h2>Verifikasi 2 langkah (2FA)</h2>
            <p class="st-sub" style="margin-bottom: 0">
                @if ($user->two_factor_confirmed_at ?? null)
                    <strong style="color: var(--d-ink-green, var(--lime))">Aktif.</strong> Akun admin terlindungi kode dari aplikasi authenticator.
                @else
                    Belum aktif. Aktifkan agar akun admin tetap aman walaupun password diketahui orang lain.
                @endif
            </p>
            @if (Security::twoFactorRoute())
                <div style="margin-top: 14px">
                    <a href="{{ route(Security::twoFactorRoute()) }}" class="btn btn-outline">Atur 2FA</a>
                </div>
            @endif
        </section>
    @endif
@endsection

@push('scripts')
    <script>
        // Field khusus Wablas
        (() => {
            const provider = document.getElementById('waProvider');
            if (!provider) return;
            const sync = () => document.querySelectorAll('[data-provider-only]').forEach((el) => {
                el.hidden = el.dataset.providerOnly !== provider.value;
            });
            provider.addEventListener('change', sync);
            sync();
        })();

        // Konfirmasi aksi berbahaya
        document.querySelectorAll('form[data-confirm]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!confirm(form.dataset.confirm)) event.preventDefault();
            });
        });

        // Pratinjau harga
        (() => {
            const price = document.getElementById('pPrice');
            const preview = document.getElementById('pricePreview');
            if (!price || !preview) return;
            const update = () => { preview.textContent = 'Tampil sebagai Rp' + Number(price.value || 0).toLocaleString('id-ID') + ' / jam'; };
            price.addEventListener('input', update);
            update();

            const course = document.getElementById('cPrice');
            const coursePreview = document.getElementById('coursePricePreview');
            if (course && coursePreview) {
                const updateCourse = () => { coursePreview.textContent = 'Tampil sebagai Rp' + Number(course.value || 0).toLocaleString('id-ID') + ' / sesi (07:00–12:00)'; };
                course.addEventListener('input', updateCourse);
                updateCourse();
            }
        })();

        // Pratinjau teks Home langsung saat diketik
        document.querySelectorAll('[data-preview]').forEach((input) => {
            const target = document.getElementById(input.dataset.preview);
            if (!target) return;
            input.addEventListener('input', () => { target.textContent = input.value; });
        });

        // Pratinjau gambar sebelum diunggah
        document.querySelectorAll('input[type="file"]').forEach((input) => {
            input.addEventListener('change', () => {
                const file = input.files[0];
                const preview = input.closest('.st-upload')?.querySelector('.st-preview');
                if (!file || !preview) return;
                preview.innerHTML = '';
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.alt = 'Pratinjau';
                preview.appendChild(img);
            });
        });
    </script>
@endpush
