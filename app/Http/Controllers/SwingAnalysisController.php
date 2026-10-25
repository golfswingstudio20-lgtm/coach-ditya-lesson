<?php

namespace App\Http\Controllers;

use App\Models\SwingAnalysis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Golf Analyzer (AI Swing Analysis).
 *
 * Analisis pose dijalankan di browser customer memakai MediaPipe Pose (public/js/swing-analyzer.js).
 * Server hanya menerima ringkasan hasil (skor, sudut, tahapan, saran) + gambar kecil tiap tahapan.
 * Video asli tidak pernah di-upload, sehingga hemat kuota dan menjaga privasi customer.
 */
class SwingAnalysisController extends Controller
{
    private const PHASE_KEYS = ['setup', 'backswing', 'top', 'impact', 'finish'];

    public function index(Request $request): View
    {
        $mine = SwingAnalysis::where('user_id', $request->user()->id);

        return view('swing.index', [
            'recent' => (clone $mine)->latest()->take(3)->get(),
            'total'  => (clone $mine)->count(),
            'best'   => (int) (clone $mine)->max('score'),
        ]);
    }

    public function create(): View
    {
        return view('swing.create', [
            'views' => SwingAnalysis::VIEWS,
            'hands' => SwingAnalysis::HANDS,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
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
            'tips'              => ['nullable', 'array', 'max:8'],
            'tips.*.title'      => ['required', 'string', 'max:60'],
            'tips.*.text'       => ['required', 'string', 'max:220'],
        ]);

        $analysis = SwingAnalysis::create([
            'user_id'     => $request->user()->id,
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
        ]);

        $phases = [];
        foreach ($data['phases'] as $phase) {
            $phases[] = [
                'key'   => $phase['key'],
                'name'  => $phase['name'],
                'score' => (int) $phase['score'],
                'note'  => $phase['note'] ?? null,
                'image' => $this->storeImage($phase['image'] ?? null, $analysis, $phase['key']),
            ];
        }

        $analysis->update(['phases' => $phases]);

        return response()->json([
            'ok'       => true,
            'id'       => $analysis->id,
            'redirect' => route('swing.show', $analysis),
        ]);
    }

    public function show(Request $request, SwingAnalysis $analysis): View
    {
        $this->authorizeView($request, $analysis);

        $previous = SwingAnalysis::where('user_id', $analysis->user_id)
            ->where('id', '<', $analysis->id)
            ->latest('id')
            ->first();

        return view('swing.show', compact('analysis', 'previous'));
    }

    public function history(Request $request): View
    {
        $items = SwingAnalysis::where('user_id', $request->user()->id)->latest()->paginate(12);

        $trend = SwingAnalysis::where('user_id', $request->user()->id)
            ->latest()->take(10)->get(['id', 'score', 'created_at'])->reverse()->values();

        return view('swing.history', compact('items', 'trend'));
    }

    private function authorizeView(Request $request, SwingAnalysis $analysis): void
    {
        $user = $request->user();

        abort_unless($user && ($user->id === $analysis->user_id || $user->role === 'admin'), 404);
    }

    /** Simpan gambar JPEG kecil (data URL dari canvas). Gagal simpan tidak menggagalkan hasil. */
    private function storeImage(?string $dataUrl, SwingAnalysis $analysis, string $key): ?string
    {
        if (! $dataUrl || ! preg_match('#^data:image/(jpeg|jpg|webp|png);base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $m)) {
            return null;
        }

        $bytes = base64_decode($m[2], true);

        if ($bytes === false || strlen($bytes) > 300000 || @getimagesizefromstring($bytes) === false) {
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
