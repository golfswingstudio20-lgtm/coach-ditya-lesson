<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Support\Uploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminGalleryController extends Controller
{
    private const YOUTUBE_PATTERN   = '~^https?://(www\.|m\.)?(youtube\.com|youtu\.be)/~i';
    private const INSTAGRAM_PATTERN = '~^https?://(www\.)?instagram\.com/(p|reel|reels|tv)/[A-Za-z0-9_-]+~i';

    /**
     * Menampilkan daftar gallery.
     */
    public function index(Request $request): View
    {
        $type = in_array($request->query('type'), ['image', 'video'], true)
            ? $request->query('type')
            : null;

        $galleries = Gallery::query()
            ->when($type, fn ($query) => $query->where('type', $type))
            ->ordered()
            ->get();

        $counts = [
            'all'   => Gallery::count(),
            'image' => Gallery::images()->count(),
            'video' => Gallery::videos()->count(),
        ];

        return view('admin.gallery.index', compact('galleries', 'counts', 'type'));
    }

    /**
     * Menampilkan form tambah gallery.
     */
    public function create(): View
    {
        $gallery = new Gallery([
            'type'       => 'image',
            'status'     => 'active',
            'sort_order' => 0,
        ]);

        return view('admin.gallery.create', compact('gallery'));
    }

    /**
     * Menyimpan gallery baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $gallery = new Gallery($this->validatedData($request));

        $this->handleMedia($request, $gallery);
        $gallery->save();

        return redirect()
            ->route('admin.gallery.index')
            ->with('success', 'Gallery berhasil ditambahkan.');
    }

    /**
     * Menampilkan form edit gallery.
     */
    public function edit(Gallery $gallery): View
    {
        return view('admin.gallery.edit', compact('gallery'));
    }

    /**
     * Mengupdate gallery.
     * Jika dikirim dengan quick_toggle=1, hanya status yang dibalik
     * (tombol Tampilkan / Sembunyikan di halaman daftar).
     */
    public function update(Request $request, Gallery $gallery): RedirectResponse
    {
        if ($request->boolean('quick_toggle')) {
            $gallery->update([
                'status' => $gallery->isActive() ? 'inactive' : 'active',
            ]);

            return back()->with(
                'success',
                $gallery->isActive()
                    ? 'Media ditampilkan ke customer & visitor.'
                    : 'Media disembunyikan dari customer & visitor.'
            );
        }

        $gallery->fill($this->validatedData($request, $gallery));

        $this->handleMedia($request, $gallery);
        $gallery->save();

        return redirect()
            ->route('admin.gallery.index')
            ->with('success', 'Gallery berhasil diperbarui.');
    }

    /**
     * Menghapus gallery beserta file-nya.
     */
    public function destroy(Gallery $gallery): RedirectResponse
    {
        $this->deleteStoredFile($gallery->image);
        $this->deleteStoredFile($gallery->video_file);

        $gallery->delete();

        return redirect()
            ->route('admin.gallery.index')
            ->with('success', 'Gallery berhasil dihapus.');
    }

    /* ---------------------------------------------------------------
     | HELPER
     * --------------------------------------------------------------- */
    private function validatedData(Request $request, ?Gallery $gallery = null): array
    {
        $type   = $request->input('type');
        $source = $this->videoSource($request);
        $isLink = $type === 'video' && in_array($source, ['youtube', 'instagram'], true);

        // Link hanya dicek kalau memang memakai link; kalau upload file, kolom link diabaikan.
        if (! $isLink) {
            $request->merge(['video_url' => null]);
        }

        // File wajib jika belum ada file lama (file bisa datang dari upload langsung: *_path)
        $needsImage     = $type === 'image' && ! $gallery?->image && ! $request->filled('image_file_path');
        $needsVideoFile = $type === 'video' && $source === 'upload' && ! $gallery?->video_file && ! $request->filled('video_upload_path');

        $request->validate([
            'type'         => ['required', Rule::in(['image', 'video'])],
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'category'     => ['nullable', 'string', 'max:100'],
            'status'       => ['required', Rule::in(['active', 'inactive'])],
            'sort_order'   => ['nullable', 'integer', 'min:0'],
            'video_source' => ['nullable', Rule::in(['youtube', 'instagram', 'upload'])],
            // Ukuran file tidak dibatasi.
            'image_file'   => [Rule::requiredIf($needsImage), 'nullable', 'file'],
            'video_url'    => $isLink ? [
                'required', 'url', 'max:500',
                $source === 'instagram' ? 'regex:' . self::INSTAGRAM_PATTERN : 'regex:' . self::YOUTUBE_PATTERN,
            ] : ['nullable'],
            'video_upload' => [Rule::requiredIf($needsVideoFile), 'nullable', 'file'],
        ], [
            'title.required'        => 'Judul wajib diisi.',
            'image_file.required'   => 'Pilih file foto yang akan diunggah.',
            'video_url.required'    => $source === 'instagram' ? 'Link Instagram wajib diisi.' : 'Link YouTube wajib diisi.',
            'video_url.regex'       => $source === 'instagram'
                ? 'Link harus dari postingan / reel Instagram, contoh: https://www.instagram.com/reel/ABC123/'
                : 'Link harus berasal dari YouTube.',
            'video_upload.required' => 'Pilih file video yang akan diunggah.',
        ]);

        return [
            'type'        => $type,
            'title'       => $request->input('title'),
            'description' => $request->input('description'),
            'category'    => $request->input('category'),
            'status'      => $request->input('status'),
            'sort_order'  => (int) $request->input('sort_order', 0),
        ];
    }

    /** Sumber video; link Instagram/YouTube dikenali otomatis walau tab yang dipilih salah. */
    private function videoSource(Request $request): string
    {
        $source = $request->input('video_source', 'youtube');

        if ($source !== 'upload') {
            $url = (string) $request->input('video_url');
            if (preg_match(self::INSTAGRAM_PATTERN, $url)) {
                return 'instagram';
            }
            if (preg_match(self::YOUTUBE_PATTERN, $url)) {
                return 'youtube';
            }
        }

        return in_array($source, ['youtube', 'instagram', 'upload'], true) ? $source : 'youtube';
    }

    private function handleMedia(Request $request, Gallery $gallery): void
    {
        // Foto (untuk tipe image) atau thumbnail (untuk tipe video)
        $image = Uploads::take($request, 'image_file', 'gallery/images');
        if ($image) {
            $this->deleteStoredFile($gallery->image);
            $gallery->image = $image;
        }

        if ($gallery->type === 'image') {
            $gallery->video_url = null;
            $this->deleteStoredFile($gallery->video_file);
            $gallery->video_file = null;

            return;
        }

        $source = $this->videoSource($request);

        if (in_array($source, ['youtube', 'instagram'], true)) {
            $gallery->video_url = trim((string) $request->input('video_url'));
            $this->deleteStoredFile($gallery->video_file);
            $gallery->video_file = null;

            return;
        }

        $video = Uploads::take($request, 'video_upload', 'gallery/videos');
        if ($video) {
            $this->deleteStoredFile($gallery->video_file);
            $gallery->video_file = $video;
        }

        $gallery->video_url = null;
    }

    /**
     * Hanya menghapus file yang diunggah lewat admin (folder storage gallery/),
     * file bawaan di public/images tidak akan tersentuh.
     */
    private function deleteStoredFile(?string $path): void
    {
        Uploads::delete($path, 'gallery/');
    }
}
