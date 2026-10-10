<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Models\GalleryAlbum;
use App\Support\Uploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Kelola folder galeri: nama, keterangan, sampul, urutan, tampil/sembunyi, tambah, hapus. */
class AdminGalleryAlbumController extends Controller
{
    public function index(): View
    {
        $albums = GalleryAlbum::ordered()->get();
        $all    = Gallery::all();
        $counts = $albums->mapWithKeys(fn ($a) => [$a->key => $all->filter(fn ($g) => $g->album_key === $a->key)->count()]);

        return view('admin.gallery.albums', compact('albums', 'counts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $album = new GalleryAlbum($data + [
            'key'        => GalleryAlbum::makeKey($data['name']),
            'sort_order' => (int) GalleryAlbum::max('sort_order') + 1,
            'is_active'  => true,
        ]);
        $album->cover = Uploads::take($request, 'cover_file', 'gallery/albums');
        $album->save();

        return redirect()->route('admin.gallery-albums.index')->with('success', 'Folder "' . $album->name . '" dibuat.');
    }

    public function update(Request $request, GalleryAlbum $album): RedirectResponse
    {
        if ($request->has('move')) {
            $list = GalleryAlbum::ordered()->get()->values();
            $i    = $list->search(fn ($a) => $a->id === $album->id);
            $j    = $request->input('move') === 'up' ? $i - 1 : $i + 1;

            if ($i !== false && isset($list[$j])) {
                [$list[$i], $list[$j]] = [$list[$j], $list[$i]];
                $list->each(fn ($a, $n) => $a->update(['sort_order' => $n + 1]));
            }

            return back();
        }

        if ($request->boolean('quick_toggle')) {
            $album->update(['is_active' => ! $album->is_active]);

            return back()->with('success', $album->is_active ? 'Folder ditampilkan.' : 'Folder disembunyikan dari halaman Galeri.');
        }

        $album->fill($this->validated($request, $album));

        if ($request->boolean('remove_cover')) {
            Uploads::delete($album->cover, 'gallery/albums/');
            $album->cover = null;
        }

        if ($cover = Uploads::take($request, 'cover_file', 'gallery/albums')) {
            Uploads::delete($album->cover, 'gallery/albums/');
            $album->cover = $cover;
        }

        $album->save();

        return redirect()->route('admin.gallery-albums.index')->with('success', 'Folder "' . $album->name . '" disimpan.');
    }

    public function destroy(Request $request, GalleryAlbum $album): RedirectResponse
    {
        $others = GalleryAlbum::where('id', '!=', $album->id)->ordered()->get();

        if ($others->isEmpty()) {
            return back()->with('error', 'Minimal harus ada 1 folder.');
        }

        $target = $others->firstWhere('key', $request->input('move_to'))
            ?? $others->firstWhere('kind', $album->kind)
            ?? $others->first();

        $moved = 0;
        foreach (Gallery::all() as $g) {
            if ($g->album_key === $album->key) {
                $g->update(['album' => $target->key]);
                $moved++;
            }
        }

        Uploads::delete($album->cover, 'gallery/albums/');
        $album->delete();

        return redirect()->route('admin.gallery-albums.index')->with('success',
            'Folder dihapus.' . ($moved ? " {$moved} isi dipindah ke \"{$target->name}\"." : ''));
    }

    private function validated(Request $request, ?GalleryAlbum $album = null): array
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:120'],
            'kind'        => ['required', Rule::in(['image', 'video'])],
            'cover_file'  => ['nullable', 'file'],
        ], [
            'name.required' => 'Nama folder wajib diisi.',
        ]);

        return [
            'name'        => trim($data['name']),
            'description' => $data['description'] ?? null,
            'kind'        => $data['kind'],
        ];
    }
}
