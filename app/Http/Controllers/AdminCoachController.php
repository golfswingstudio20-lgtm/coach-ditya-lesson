<?php

namespace App\Http\Controllers;

use App\Support\Uploads;
use App\Models\Coach;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * About Coach: satu profil coach yang tampil di halaman Home.
 * Hanya informasi. Customer tidak memilih coach saat booking.
 */
class AdminCoachController extends Controller
{
    public function index(): View
    {
        $coach = Coach::main() ?? new Coach(['name' => '', 'is_active' => true, 'skills' => []]);

        return view('admin.coaches.index', compact('coach'));
    }

    /** Link lama (tambah/edit coach) diarahkan ke halaman About Coach. */
    public function create(): RedirectResponse
    {
        return redirect()->route('admin.coaches.index');
    }

    public function edit(Coach $coach): RedirectResponse
    {
        return redirect()->route('admin.coaches.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $coach = Coach::main() ?? new Coach(['sort_order' => 1]);

        return $this->save($request, $coach);
    }

    public function update(Request $request, Coach $coach): RedirectResponse
    {
        return $this->save($request, $coach);
    }

    public function destroy(Coach $coach): RedirectResponse
    {
        return redirect()->route('admin.coaches.index');
    }

    private function save(Request $request, Coach $coach): RedirectResponse
    {
        $request->validate([
            'section_label'    => ['nullable', 'string', 'max:60'],
            'section_title'    => ['nullable', 'string', 'max:120'],
            'name'             => ['required', 'string', 'max:100'],
            'role'             => ['nullable', 'string', 'max:120'],
            'badge'            => ['nullable', 'string', 'max:40'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:80'],
            'students'         => ['nullable', 'string', 'max:30'],
            'bio'              => ['nullable', 'string', 'max:1500'],
            'skills'           => ['nullable', 'string', 'max:600'],
            'experiences'      => ['nullable', 'string', 'max:2000'],
            'certifications'   => ['nullable', 'string', 'max:1200'],
            'achievements'     => ['nullable', 'string', 'max:1200'],
            'quote'            => ['nullable', 'string', 'max:200'],
            'photo_file'       => ['nullable', 'file'],
            'is_active'        => ['nullable', 'boolean'],
        ], [
            'name.required'    => 'Nama coach wajib diisi.',
            'bio.max'          => 'Tentang coach maksimal 1500 karakter.',
            'photo_file.image' => 'File harus berupa gambar.',
            'photo_file.max'   => 'Ukuran foto maksimal 4 MB.',
        ]);

        $experiences = $this->lines($request->input('experiences'), 8, 120)
            ->map(function ($line) {
                [$period, $text] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');

                return $text === '' ? ['period' => '', 'text' => $period] : ['period' => Str::limit($period, 30, ''), 'text' => $text];
            })->values()->all();

        $coach->fill([
            'section_label'    => $request->input('section_label'),
            'section_title'    => $request->input('section_title'),
            'name'             => trim($request->input('name')),
            'role'             => $request->input('role'),
            'badge'            => $request->input('badge'),
            'years_experience' => $request->filled('years_experience') ? (int) $request->input('years_experience') : null,
            'students'         => $request->input('students'),
            'bio'              => $request->input('bio'),
            'skills'           => $this->lines($request->input('skills'), 8, 30, true)->all(),
            'experiences'      => $experiences,
            'certifications'   => $this->lines($request->input('certifications'), 8, 100)->all(),
            'achievements'     => $this->lines($request->input('achievements'), 8, 100)->all(),
            'quote'            => $request->input('quote'),
            'is_active'        => $request->boolean('is_active'),
        ]);

        if ($request->boolean('remove_photo')) {
            $this->deletePhoto($coach->photo);
            $coach->photo = null;
        }

        $photo = Uploads::take($request, 'photo_file', 'coaches');
        if ($photo) {
            $this->deletePhoto($coach->photo);
            $coach->photo = $photo;
        }

        $coach->save();

        if ($request->input('return') === 'home') {
            return redirect()->to(route('home') . '#coach')->with('coach_saved', 'Profil coach berhasil disimpan.');
        }

        return redirect()->route('admin.coaches.index')->with('success', 'Profil coach berhasil disimpan.');
    }

    private function lines(?string $value, int $max, int $limit, bool $commas = false)
    {
        $pattern = $commas ? '/\r\n|\r|\n|,/' : '/\r\n|\r|\n/';

        return collect(preg_split($pattern, (string) $value))
            ->map(fn ($s) => Str::limit(trim(preg_replace('/^[\s\-•*]+/u', '', $s)), $limit, ''))
            ->filter()->take($max)->values();
    }

    private function deletePhoto(?string $path): void
    {
        if ($path && Str::startsWith($path, 'coaches/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
