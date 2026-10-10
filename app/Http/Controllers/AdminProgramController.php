<?php

namespace App\Http\Controllers;

use App\Support\Uploads;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminProgramController extends Controller
{
    private const MAX_FEATURES = 8;

    /**
     * Menampilkan daftar program.
     */
    public function index(): View
    {
        $programs = Program::ordered()->get();

        return view('admin.programs.index', compact('programs'));
    }

    /**
     * Menampilkan form tambah program.
     */
    public function create(): View
    {
        $program = new Program([
            'image_position' => 'center',
            'sort_order'     => (int) Program::max('sort_order') + 1,
            'is_active'      => true,
            'features'       => [],
        ]);

        return view('admin.programs.create', compact('program'));
    }

    /**
     * Menyimpan program baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $program = new Program($this->validatedData($request));

        $this->handleImage($request, $program);
        $program->save();

        return redirect()
            ->route('admin.programs.index')
            ->with('success', 'Program berhasil ditambahkan.');
    }

    /**
     * Menampilkan form edit program.
     */
    public function edit(Program $program): View
    {
        return view('admin.programs.edit', compact('program'));
    }

    /**
     * Mengupdate program.
     * quick_toggle=1 hanya membalik status tampil / sembunyi.
     */
    public function update(Request $request, Program $program): RedirectResponse
    {
        if ($request->boolean('quick_toggle')) {
            $program->update(['is_active' => ! $program->is_active]);

            return back()->with(
                'success',
                $program->is_active
                    ? 'Program ditampilkan ke customer & visitor.'
                    : 'Program disembunyikan dari customer & visitor.'
            );
        }

        $program->fill($this->validatedData($request));

        $this->handleImage($request, $program);
        $program->save();

        return redirect()
            ->route('admin.programs.index')
            ->with('success', 'Program berhasil diperbarui.');
    }

    /**
     * Menghapus program beserta fotonya.
     */
    public function destroy(Program $program): RedirectResponse
    {
        $this->deleteStoredFile($program->image);
        $program->delete();

        return redirect()
            ->route('admin.programs.index')
            ->with('success', 'Program berhasil dihapus.');
    }

    /* ---------------------------------------------------------------
     | HELPER
     * --------------------------------------------------------------- */
    private function validatedData(Request $request): array
    {
        $request->validate([
            'level'          => ['required', 'string', 'max:50'],
            'name'           => ['required', 'string', 'max:100'],
            'description'    => ['nullable', 'string', 'max:255'],
            'features'       => ['nullable', 'string', 'max:1000'],
            'image_file'     => ['nullable', 'file'],
            'image_position' => ['required', Rule::in(array_keys(Program::POSITIONS))],
            'sort_order'     => ['nullable', 'integer', 'min:0'],
            'is_active'      => ['nullable', 'boolean'],
        ], [
            'level.required' => 'Level program wajib diisi.',
            'name.required'  => 'Nama program wajib diisi.',
            'image_file.image' => 'File harus berupa gambar.',
            'image_file.max' => 'Ukuran foto maksimal 5 MB.',
        ]);

        // Fitur: satu per baris, baris kosong diabaikan
        $features = collect(preg_split('/\r\n|\r|\n/', (string) $request->input('features')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->map(fn ($line) => mb_substr($line, 0, 80))
            ->take(self::MAX_FEATURES)
            ->values()
            ->all();

        return [
            'level'          => mb_strtoupper(trim($request->input('level'))),
            'name'           => $request->input('name'),
            'description'    => $request->input('description'),
            'features'       => $features,
            'image_position' => $request->input('image_position'),
            'sort_order'     => (int) $request->input('sort_order', 0),
            'is_active'      => $request->boolean('is_active'),
        ];
    }

    private function handleImage(Request $request, Program $program): void
    {
        if ($request->boolean('remove_image')) {
            $this->deleteStoredFile($program->image);
            $program->image = null;
        }

        $image = Uploads::take($request, 'image_file', 'programs');
        if ($image) {
            $this->deleteStoredFile($program->image);
            $program->image = $image;
        }
    }

    /**
     * Hanya menghapus foto yang diunggah lewat admin (storage programs/).
     */
    private function deleteStoredFile(?string $path): void
    {
        Uploads::delete($path, 'programs/');
    }
}
