<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Semua teks & foto halaman website yang bisa diubah admin
 * (Admin > Konten Website, atau tombol ✎ Edit langsung di halaman).
 *
 * Disimpan di tabel settings (key => value). Kalau belum diisi, dipakai teks bawaan.
 * Kata di dalam [kurung siku] pada judul tampil berwarna hijau.
 *
 * Jenis isian: text, textarea, image, toggle, icon.
 */
class Content
{
    public const ICONS = [
        'calendar' => 'Kalender', 'coach' => 'Coach', 'pin' => 'Lokasi', 'golf' => 'Golf', 'flag' => 'Bendera',
        'trophy' => 'Piala', 'award' => 'Medali', 'star' => 'Bintang', 'target' => 'Target', 'clock' => 'Jam',
        'users' => 'Orang', 'heart' => 'Hati', 'shield' => 'Perisai', 'spark' => 'Kilau', 'chat' => 'Chat',
        'wallet' => 'Dompet', 'check-c' => 'Centang', 'video' => 'Video', 'image' => 'Foto', 'layers' => 'Lapisan',
    ];

    /**
     * Halaman => [judul, url-route, bagian => [judul, anchor, field => [jenis, label, bawaan, (hint)]]]
     */
    public static function pages(): array
    {
        return [
            'home' => ['Home', 'home', [
                'hero' => ['Bagian atas (hero)', 'top', [
                    'bg_public'           => ['image', 'Foto latar', null, 'Juga dipakai sebagai latar halaman publik lain. Foto landscape, makin besar makin tajam.'],
                    'hero_label'          => ['text', 'Label kecil di atas judul', 'GOLF BOOKING LESSON'],
                    'hero_title_1'        => ['text', 'Judul bagian 1', 'FROM FIRST SWING'],
                    'hero_title_2'        => ['text', 'Judul bagian 2', 'TO'],
                    'hero_highlight'      => ['text', 'Kata hijau di akhir judul', 'CHAMPIONSHIP'],
                    'hero_text'           => ['textarea', 'Kalimat di bawah judul', 'Tingkatkan permainan golf Anda bersama instruktur profesional. Program latihan dirancang untuk membantu Anda berkembang dari pemula hingga level kompetitif.'],
                    'home_btn_primary'    => ['text', 'Tombol utama', 'Mulai Sekarang', 'Untuk admin yang login, tombol ini tetap "Buka Panel Admin".'],
                    'home_btn_secondary'  => ['text', 'Tombol kedua (ke Program)', 'Lihat Program'],
                    'home_side_coach'     => ['text', 'Kartu samping: label coach', 'Coach Anda'],
                    'home_side_book'      => ['text', 'Kartu samping: judul booking', 'Booking online'],
                    'home_side_book_text' => ['text', 'Kartu samping: keterangan booking', 'Pilih jam, bayar, langsung main'],
                    'home_side_loc'       => ['text', 'Kartu samping: teks lokasi', 'lokasi latihan', 'Tampil setelah jumlah lokasi, misal "2 lokasi latihan".'],
                ]],
                'stats' => ['Angka di bawah hero', 'top', [
                    'home_show_stats'       => ['toggle', 'Tampilkan angka', '1'],
                    'home_stat_years'       => ['text', 'Label tahun melatih', 'Tahun melatih'],
                    'home_stat_locations'   => ['text', 'Label lokasi', 'Lokasi latihan'],
                    'home_stat_programs'    => ['text', 'Label program', 'Program'],
                ]],
                'features' => ['Keunggulan', 'hm-features', [
                    'home_show_features'  => ['toggle', 'Tampilkan bagian ini', '1'],
                    'home_feat_eyebrow'   => ['text', 'Label kecil', 'Kenapa kami'],
                    'home_feat_title'     => ['text', 'Judul', 'Belajar golf jadi lebih mudah', 'Kata di dalam [kurung siku] tampil hijau.'],
                    'home_feat1_icon'     => ['icon', 'Poin 1: ikon', 'calendar'],
                    'home_feat1_title'    => ['text', 'Poin 1: judul', 'Atur jadwal sendiri'],
                    'home_feat1_text'     => ['textarea', 'Poin 1: keterangan', 'Lihat jam kosong secara langsung dan booking lesson per jam dari HP.'],
                    'home_feat2_icon'     => ['icon', 'Poin 2: ikon', 'coach'],
                    'home_feat2_title'    => ['text', 'Poin 2: judul', 'Coach berpengalaman'],
                    'home_feat2_text'     => ['textarea', 'Poin 2: keterangan', 'Dilatih langsung oleh coach profesional dengan metode yang terstruktur.'],
                    'home_feat3_icon'     => ['icon', 'Poin 3: ikon', 'pin'],
                    'home_feat3_title'    => ['text', 'Poin 3: judul', 'Pilih lokasi latihan'],
                    'home_feat3_text'     => ['textarea', 'Poin 3: keterangan', 'Driving range atau course lesson di lapangan golf pilihan Anda.'],
                ]],
                'programs' => ['Program', 'hm-programs', [
                    'home_show_programs'  => ['toggle', 'Tampilkan bagian ini', '1'],
                    'home_prog_eyebrow'   => ['text', 'Label kecil', 'Program'],
                    'home_prog_title'     => ['text', 'Judul', 'Pilih program sesuai level Anda', 'Kata di dalam [kurung siku] tampil hijau. Isi kartu program diatur di menu Program.'],
                    'home_prog_link'      => ['text', 'Teks link', 'Semua program →'],
                    'home_prog_price'     => ['text', 'Satuan harga', '/ jam'],
                    'home_prog_btn'       => ['text', 'Tombol di kartu', 'Lihat'],
                ]],
                'gallery' => ['Galeri', 'hm-gallery', [
                    'home_show_gallery'   => ['toggle', 'Tampilkan bagian ini', '1'],
                    'home_gal_eyebrow'    => ['text', 'Label kecil', 'Galeri'],
                    'home_gal_title'      => ['text', 'Judul', 'Suasana latihan kami', 'Kata di dalam [kurung siku] tampil hijau. Foto diambil otomatis dari menu Galeri.'],
                    'home_gal_link'       => ['text', 'Teks link', 'Lihat galeri →'],
                ]],
                'events' => ['Event', 'hm-events', [
                    'home_show_events'    => ['toggle', 'Tampilkan bagian ini', '1'],
                    'home_ev_eyebrow'     => ['text', 'Label kecil', 'Event'],
                    'home_ev_title'       => ['text', 'Judul', 'Event mendatang', 'Kata di dalam [kurung siku] tampil hijau. Event diambil otomatis dari menu Event.'],
                    'home_ev_link'        => ['text', 'Teks link', 'Semua event →'],
                ]],
                'cta' => ['Ajakan di bawah', 'hm-cta', [
                    'home_show_cta'       => ['toggle', 'Tampilkan bagian ini', '1'],
                    'home_cta_title'      => ['textarea', 'Judul', "Latihan hari ini,\nprestasi esok hari.", 'Tekan Enter untuk pindah baris.'],
                    'home_cta_text'       => ['text', 'Kalimat', 'Booking lesson pertama Anda sekarang, hanya butuh 1 menit.'],
                    'home_cta_btn'        => ['text', 'Tombol', 'Mulai Sekarang'],
                ]],
            ]],

            'coach' => ['About Coach', 'home', [
                'coach_text' => ['Judul kotak & tombol About Coach', 'coach', [
                    'coach_exp_title'     => ['text', 'Judul pengalaman', 'Pengalaman'],
                    'coach_cert_title'    => ['text', 'Judul sertifikasi', 'Sertifikasi'],
                    'coach_ach_title'     => ['text', 'Judul prestasi', 'Prestasi'],
                    'coach_skill_title'   => ['text', 'Judul keahlian', 'Keahlian yang diajarkan'],
                    'coach_stat_years'    => ['text', 'Label tahun melatih', 'Tahun melatih'],
                    'coach_stat_students' => ['text', 'Label murid', 'Murid dilatih'],
                    'coach_stat_cert'     => ['text', 'Label jumlah sertifikasi', 'Sertifikasi'],
                    'coach_float'         => ['text', 'Label di samping foto', 'tahun melatih'],
                    'coach_btn_book'      => ['text', 'Tombol booking', 'Booking Sekarang'],
                    'coach_btn_chat'      => ['text', 'Tombol chat (member)', 'Chat coach'],
                    'coach_btn_program'   => ['text', 'Tombol program (pengunjung)', 'Lihat program'],
                ]],
            ]],

            'program' => ['Program', 'program', [
                'program_head' => ['Judul halaman Program', 'top', [
                    'program_title'       => ['text', 'Judul halaman', 'Program Latihan'],
                    'program_sub'         => ['textarea', 'Kalimat di bawah judul', 'Program terstruktur bersama coach profesional, sesuai level permainan Anda.'],
                    'program_price_eyebrow' => ['text', 'Label kecil bagian harga', 'Harga lesson'],
                    'program_price_title' => ['text', 'Judul bagian harga', 'Pilih jenis lesson'],
                    'program_range_title' => ['text', 'Nama Lesson Driving Range', 'Lesson Driving Range'],
                    'program_course_title' => ['text', 'Nama Course Lesson', 'Course Lesson'],
                ]],
            ]],

            'galeri' => ['Galeri', 'galeri', [
                'galeri_head' => ['Judul halaman Galeri', 'top', [
                    'galeri_title'        => ['text', 'Judul halaman', 'Galeri'],
                    'galeri_sub'          => ['textarea', 'Kalimat di bawah judul', 'Aktivitas latihan, suasana lapangan, dan video rekomendasi.'],
                ]],
            ]],

            'event' => ['Event', 'event', [
                'event_head' => ['Judul halaman Event', 'top', [
                    'event_title'         => ['text', 'Judul halaman', 'Event'],
                    'event_sub'           => ['textarea', 'Kalimat di bawah judul', 'Ikuti event & coaching clinic, amankan tempat Anda.'],
                    'event_empty'         => ['text', 'Teks jika belum ada event', 'Belum ada event terdekat'],
                ]],
            ]],

            'contact' => ['Kontak', 'contact', [
                'contact_head' => ['Judul halaman Kontak', 'top', [
                    'contact_title'       => ['text', 'Judul halaman', 'Bantuan & Kontak', 'Keterangan, alamat & nomor diatur di menu Kontak & Lokasi.'],
                    'contact_loc_title'   => ['text', 'Judul lokasi', 'Lokasi latihan'],
                    'contact_reach_title' => ['text', 'Judul hubungi', 'Hubungi kami'],
                    'contact_form_title'  => ['text', 'Judul form pesan', 'Kirim pesan'],
                ]],
            ]],

            'member' => ['Dashboard member', 'dashboard', [
                'member_head' => ['Dashboard member', 'top', [
                    'member_greet'        => ['text', 'Kalimat sapaan', 'Saatnya tingkatkan permainan golf Anda.', 'Tampil setelah "Selamat pagi/siang/malam!".'],
                    'member_banner_title' => ['textarea', 'Judul banner booking', "Better Swing,\nBetter You", 'Tekan Enter untuk pindah baris.'],
                    'member_banner_text'  => ['text', 'Kalimat banner booking', 'Pilih jam kosong dan booking lesson sekarang.'],
                ]],
            ]],

            'footer' => ['Footer', 'home', [
                'footer' => ['Footer (bagian paling bawah, laptop)', 'top', [
                    'tagline'             => ['textarea', 'Slogan', Brand::DEFAULT_TAGLINE],
                    'footer_menu_title'   => ['text', 'Judul kolom menu', 'Menu'],
                    'footer_contact_title' => ['text', 'Judul kolom kontak', 'Hubungi kami'],
                ]],
            ]],
        ];
    }

    /** Semua bagian: key => [page, judul, anchor, fields] */
    public static function sections(): array
    {
        $out = [];
        foreach (self::pages() as $page => [, , $sections]) {
            foreach ($sections as $key => [$title, $anchor, $fields]) {
                $out[$key] = ['page' => $page, 'title' => $title, 'anchor' => $anchor, 'fields' => $fields];
            }
        }

        return $out;
    }

    public static function section(string $key): ?array
    {
        return self::sections()[$key] ?? null;
    }

    /** Daftar semua field: key => definisi */
    public static function fields(): array
    {
        static $all = null;
        if ($all === null) {
            $all = [];
            foreach (self::sections() as $s) {
                $all += $s['fields'];
            }
        }

        return $all;
    }

    /** Teks (atau path gambar) tersimpan, atau bawaan. */
    public static function get(string $key, ?string $default = null): ?string
    {
        $def = self::fields()[$key][2] ?? $default;

        return Setting::get($key, $def);
    }

    /** Teks aman dengan [kata] => <span>kata</span> dan baris baru => <br>. */
    public static function html(string $key): string
    {
        $text = nl2br(e((string) self::get($key)), false);

        return preg_replace('/\[(.+?)\]/u', '<span class="fw-hl">$1</span>', $text);
    }

    public static function on(string $key): bool
    {
        return self::get($key) !== '0';
    }

    public static function imageUrl(string $key): ?string
    {
        $path = Setting::get($key);

        return $path ? Storage::disk('public')->url($path) : null;
    }

    /**
     * Simpan satu bagian dari form. Nama input = key field.
     */
    public static function save(string $sectionKey, Request $request): void
    {
        $section = self::section($sectionKey);
        abort_unless($section, 404);

        $rules = [];
        foreach ($section['fields'] as $key => [$type]) {
            $rules[$key] = match ($type) {
                'textarea' => ['nullable', 'string', 'max:2000'],
                'toggle'   => ['nullable', 'in:0,1'],
                'icon'     => ['nullable', 'in:' . implode(',', array_keys(self::ICONS))],
                'image'    => ['nullable', 'file', 'image'],
                default    => ['nullable', 'string', 'max:255'],
            };
        }

        $validator = Validator::make($request->all(), $rules, [
            '*.max'   => 'Teks terlalu panjang.',
            '*.image' => 'File harus berupa gambar.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator, back()->withInput()->withErrors($validator, 'content')->with('content_section', $sectionKey));
        }

        $pairs = [];
        foreach ($section['fields'] as $key => [$type, , $default]) {
            if ($type === 'image') {
                $old = Setting::get($key);
                if ($request->boolean('remove_' . $key)) {
                    self::deleteFile($old);
                    $pairs[$key] = '';
                }
                $new = Uploads::take($request, $key, 'content');
                if ($new) {
                    if ($old !== $new) {
                        self::deleteFile($old);
                    }
                    $pairs[$key] = $new;
                }
                continue;
            }

            if ($type === 'toggle') {
                $pairs[$key] = $request->boolean($key) ? '1' : '0';
                continue;
            }

            $value = trim((string) $request->input($key, ''));
            // Kosong atau sama dengan bawaan: simpan kosong supaya bawaan tetap dipakai
            $pairs[$key] = ($value === '' || $value === $default) ? '' : $value;
        }

        Setting::put($pairs);
    }

    private static function deleteFile(?string $path): void
    {
        if ($path && (str_starts_with($path, 'content/') || str_starts_with($path, 'settings/'))) {
            try {
                Storage::disk('public')->delete($path);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
