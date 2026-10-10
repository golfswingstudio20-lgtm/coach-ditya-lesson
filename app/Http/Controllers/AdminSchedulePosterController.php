<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\SchedulePoster;
use App\Support\Uploads;
use App\Support\WhatsApp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin > Poster Jadwal: buat gambar jadwal mingguan dari booking customer,
 * lalu unduh / bagikan / kirim ke WhatsApp admin.
 */
class AdminSchedulePosterController extends Controller
{
    public function index(Request $request): View
    {
        [$defFrom, $defTo] = SchedulePoster::defaultRange();

        $from = $this->date($request->query('from')) ?? $defFrom;
        $to   = $this->date($request->query('to')) ?? ($request->query('from') ? $from : $defTo);
        $mode = array_key_exists($request->query('mode'), SchedulePoster::MODES) ? $request->query('mode') : SchedulePoster::get('poster_mode');

        $poster = SchedulePoster::build($from, $to, $mode);

        return view('admin.poster.index', compact('poster', 'mode'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'poster_title'     => ['nullable', 'string', 'max:80'],
            'poster_subtitle'  => ['nullable', 'string', 'max:80'],
            'poster_notes'     => ['nullable', 'string', 'max:1500'],
            'poster_free'      => ['nullable', 'string', 'max:30'],
            'poster_booked'    => ['nullable', 'string', 'max:30'],
            'poster_footer'    => ['nullable', 'string', 'max:80'],
            'poster_ig'        => ['nullable', 'string', 'max:60'],
            'poster_contact'   => ['nullable', 'string', 'max:60'],
            'poster_website'   => ['nullable', 'string', 'max:100'],
            'poster_mode'      => ['required', Rule::in(array_keys(SchedulePoster::MODES))],
            'poster_logo'      => ['nullable', 'file', 'image'],
        ], ['*.max' => 'Teks terlalu panjang.', 'poster_logo.image' => 'Logo harus berupa gambar.']);

        $pairs = [];
        foreach (['poster_title', 'poster_subtitle', 'poster_notes', 'poster_free', 'poster_booked', 'poster_footer', 'poster_ig', 'poster_contact', 'poster_website', 'poster_mode'] as $key) {
            $pairs[$key] = trim((string) $request->input($key, ''));
        }
        $pairs['poster_show_logo'] = $request->boolean('poster_show_logo') ? '1' : '0';

        $old = Setting::get('poster_logo');
        if ($request->boolean('remove_poster_logo')) {
            $this->deleteFile($old);
            $pairs['poster_logo'] = '';
        }
        if ($new = Uploads::take($request, 'poster_logo', 'content')) {
            if ($new !== $old) {
                $this->deleteFile($old);
            }
            $pairs['poster_logo'] = $new;
        }

        Setting::put($pairs);

        return redirect()
            ->route('admin.poster.index', $request->only(['from', 'to']) + ['mode' => $pairs['poster_mode']])
            ->with('success', 'Pengaturan poster disimpan.');
    }

    /**
     * Terima PNG hasil poster dari browser, simpan, lalu kirim ke WhatsApp admin.
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate(['image' => ['required', 'string'], 'caption' => ['nullable', 'string', 'max:500']]);

        if (! preg_match('#^data:image/(png|jpeg);base64,#', $request->input('image'), $m)) {
            return response()->json(['ok' => false, 'detail' => 'Gambar tidak dikenali.'], 422);
        }

        $bytes = base64_decode(substr($request->input('image'), strlen($m[0])), true);
        if (! $bytes) {
            return response()->json(['ok' => false, 'detail' => 'Gambar rusak.'], 422);
        }

        $path = 'content/poster-jadwal-' . now()->format('Ymd-His') . '-' . Str::lower(Str::random(6)) . '.' . ($m[1] === 'jpeg' ? 'jpg' : 'png');

        try {
            Storage::disk('public')->put($path, $bytes);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'detail' => 'Gambar gagal disimpan ke penyimpanan.'], 500);
        }

        // Hapus poster kiriman sebelumnya supaya penyimpanan tidak menumpuk
        $this->deleteFile(Setting::get('poster_last_sent'));
        Setting::put(['poster_last_sent' => $path]);

        $result = WhatsApp::sendImage(WhatsApp::adminNumbers(), (string) $request->input('caption', ''), Storage::disk('public')->url($path));

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    private function date($value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) ? $value : null;
    }

    private function deleteFile(?string $path): void
    {
        if ($path && str_starts_with($path, 'content/')) {
            try {
                Storage::disk('public')->delete($path);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
