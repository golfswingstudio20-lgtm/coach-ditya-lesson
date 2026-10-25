<?php

namespace App\Support;

use App\Models\SwingAnalysis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Validasi & penyimpanan hasil Golf Analyzer, dipakai customer (/swing) dan admin (/admin/swing/baru).
 * Analisis pose berjalan di browser; server hanya menerima ringkasan + gambar kecil tiap tahapan.
 */
class SwingAnalysisStore
{
    public const PHASE_KEYS = ['setup', 'backswing', 'top', 'impact', 'finish'];

    public static function rules(): array
    {
        return [
            'camera_view'       => ['required', Rule::in(array_keys(SwingAnalysis::VIEWS))],
            'handedness'        => ['required', Rule::in(array_keys(SwingAnalysis::HANDS))],
            'score'             => ['required', 'integer', 'min:0', 'max:100'],
            'duration_ms'       => ['required', 'integer', 'min:0', 'max:120000'],
            'frames'            => ['required', 'integer', 'min:0', 'max:5000'],
            'insight'           => ['nullable', 'string', 'max:600'],
            'metrics'           => ['required', 'array', 'min:1', 'max:12'],
            'metrics.*.key'     => ['required', 'string', 'max:30'],
            'metrics.*.label'   => ['required', 'string', 'max:60'],
            'metrics.*.score'   => ['required', 'integer', 'min:0', 'max:100'],
            'metrics.*.value'   => ['nullable', 'numeric'],
            'metrics.*.unit'    => ['nullable', 'string', 'max:10'],
            'metrics.*.note'    => ['nullable', 'string', 'max:200'],
            'phases'            => ['required', 'array', 'min:1', 'max:6'],
            'phases.*.key'      => ['required', Rule::in(self::PHASE_KEYS)],
            'phases.*.name'     => ['required', 'string', 'max:40'],
            'phases.*.score'    => ['required', 'integer', 'min:0', 'max:100'],
            'phases.*.note'     => ['nullable', 'string', 'max:200'],
            'phases.*.image'    => ['nullable', 'string', 'max:400000'],
            'phases.*.t'        => ['nullable', 'numeric', 'min:0', 'max:120'],
            'pose'              => ['nullable', 'array', 'max:400'],
            'pose.*'            => ['array', 'max:48'],
            'tips'              => ['nullable', 'array', 'max:8'],
            'tips.*.title'      => ['required', 'string', 'max:60'],
            'tips.*.text'       => ['required', 'string', 'max:220'],
        ];
    }

    /** Simpan hasil yang sudah tervalidasi untuk user tertentu. */
    public static function save(array $data, int $userId): SwingAnalysis
    {
        $analysis = SwingAnalysis::create([
            'user_id'     => $userId,
            'camera_view' => $data['camera_view'],
            'handedness'  => $data['handedness'],
            'score'       => $data['score'],
            'duration_ms' => $data['duration_ms'],
            'frames'      => $data['frames'],
            'insight'     => $data['insight'] ?? null,
            'metrics'     => array_values(array_map(fn ($m) => [
                'key'   => $m['key'],
                'label' => $m['label'],
                'score' => (int) $m['score'],
                'value' => isset($m['value']) ? round((float) $m['value'], 2) : null,
                'unit'  => $m['unit'] ?? null,
                'note'  => $m['note'] ?? null,
            ], $data['metrics'])),
            'tips'        => array_values($data['tips'] ?? []),
            'phases'      => [],
            'pose_data'   => self::cleanPose($data['pose'] ?? null),
        ]);

        $phases = [];
        foreach ($data['phases'] as $phase) {
            $phases[] = [
                'key'   => $phase['key'],
                'name'  => $phase['name'],
                'score' => (int) $phase['score'],
                'note'  => $phase['note'] ?? null,
                't'     => isset($phase['t']) ? round((float) $phase['t'], 3) : null,
                'image' => self::storeImage($phase['image'] ?? null, $analysis, $phase['key']),
            ];
        }

        $analysis->update(['phases' => $phases]);

        return $analysis;
    }

    /**
     * Data pose ringkas untuk memutar ulang garis di Studio Video:
     * tiap frame = [detik, x,y,v untuk 13 titik tubuh (hidung, bahu, siku, pergelangan, pinggul, lutut, kaki)].
     */
    private static function cleanPose(?array $pose): ?array
    {
        if (! $pose) {
            return null;
        }

        $out = [];
        foreach (array_slice($pose, 0, 400) as $row) {
            if (! is_array($row) || count($row) !== 40) {
                continue;
            }
            $out[] = array_map(fn ($v) => round((float) $v, 4), array_values($row));
        }

        return $out ?: null;
    }

    /** Link upload video langsung ke Supabase (video tidak lewat server website). */
    public static function signVideo(SwingAnalysis $analysis, string $filename): array
    {
        if (! Uploads::directEnabled()) {
            throw new \RuntimeException('Penyimpanan video belum aktif di server.');
        }

        return Uploads::sign('swing/videos', $filename);
    }

    /** Pasang video yang sudah ter-upload ke hasil analisis (video lama dihapus). */
    public static function attachVideo(SwingAnalysis $analysis, string $path): bool
    {
        if (! preg_match('#^swing/videos/[0-9a-fA-F-]{36}\.(mp4|mov|m4v|webm|3gp|bin)$#', $path)) {
            return false;
        }

        $old = $analysis->video_path;
        $analysis->update(['video_path' => $path]);

        if ($old && $old !== $path) {
            self::deleteFiles([$old]);
        }

        return true;
    }

    public static function deleteVideo(SwingAnalysis $analysis): void
    {
        if ($analysis->video_path) {
            self::deleteFiles([$analysis->video_path]);
            $analysis->update(['video_path' => null]);
        }
    }

    /** Hapus analisis beserta semua file-nya (gambar tahapan, capture, video). */
    public static function deleteAll(SwingAnalysis $analysis): void
    {
        $paths = array_merge(
            array_column($analysis->phases ?? [], 'image'),
            array_column($analysis->captures ?? [], 'image'),
            [$analysis->video_path]
        );

        self::deleteFiles($paths);
        $analysis->delete();
    }

    private static function deleteFiles(array $paths): void
    {
        $paths = array_values(array_filter($paths));

        if (! $paths) {
            return;
        }

        try {
            Storage::disk('public')->delete($paths);
        } catch (\Throwable $e) {
            Log::warning('Gagal menghapus file swing: ' . $e->getMessage());
        }
    }

    /** Simpan satu capture dari Studio Video (frame + garis/sudut). Maksimal 12 per analisis. */
    public static function addCapture(SwingAnalysis $analysis, string $dataUrl): ?array
    {
        $captures = $analysis->captures ?? [];

        if (count($captures) >= 12) {
            return null;
        }

        $path = self::storeImage($dataUrl, $analysis, 'cap-' . (count($captures) + 1) . '-' . substr(md5(uniqid('', true)), 0, 6), 700000);

        if (! $path) {
            return null;
        }

        $captures[] = ['image' => $path, 'at' => now()->toIso8601String()];
        $analysis->update(['captures' => $captures]);

        return ['path' => $path, 'url' => SwingAnalysis::imageUrl($path), 'count' => count($captures)];
    }

    /** Simpan gambar JPEG kecil (data URL dari canvas). Gagal simpan tidak menggagalkan hasil. */
    private static function storeImage(?string $dataUrl, SwingAnalysis $analysis, string $key, int $maxBytes = 300000): ?string
    {
        if (! $dataUrl || ! preg_match('#^data:image/(jpeg|jpg|webp|png);base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $m)) {
            return null;
        }

        $bytes = base64_decode($m[2], true);

        if ($bytes === false || strlen($bytes) > $maxBytes || @getimagesizefromstring($bytes) === false) {
            return null;
        }

        $ext  = $m[1] === 'jpg' ? 'jpeg' : $m[1];
        $path = 'swing/' . $analysis->user_id . '/' . $analysis->id . '-' . $key . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);

        try {
            return Storage::disk('public')->put($path, $bytes) ? $path : null;
        } catch (\Throwable $e) {
            Log::warning('Gagal menyimpan gambar swing: ' . $e->getMessage());

            return null;
        }
    }
}
