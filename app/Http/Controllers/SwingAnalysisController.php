<?php

namespace App\Http\Controllers;

use App\Models\SwingAnalysis;
use App\Support\SwingAnalysisStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $data     = $request->validate(SwingAnalysisStore::rules());
        $analysis = SwingAnalysisStore::save($data, $request->user()->id);

        return response()->json([
            'ok'       => true,
            'id'       => $analysis->id,
            'redirect' => route('swing.show', $analysis),
            'capture_url' => route('swing.capture', $analysis),
            'video_sign_url' => route('swing.video.sign', $analysis),
            'video_attach_url' => route('swing.video.attach', $analysis),
        ]);
    }

    /** Simpan capture dari Studio Video milik customer sendiri. */
    public function capture(Request $request, SwingAnalysis $analysis): JsonResponse
    {
        abort_unless($request->user()->id === $analysis->user_id, 404);

        $request->validate(['image' => ['required', 'string', 'max:1000000']]);
        $saved = SwingAnalysisStore::addCapture($analysis, $request->input('image'));

        return $saved
            ? response()->json(['ok' => true] + $saved)
            : response()->json(['ok' => false, 'message' => 'Capture gagal disimpan (maksimal 12 gambar per analisis).'], 422);
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

    /** Link upload video swing langsung ke Supabase. */
    public function videoSign(Request $request, SwingAnalysis $analysis): JsonResponse
    {
        $this->authorizeOwner($request, $analysis);
        $request->validate(['filename' => ['required', 'string', 'max:255']]);

        try {
            return response()->json(SwingAnalysisStore::signVideo($analysis, $request->input('filename')));
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Video tidak bisa disimpan: ' . $e->getMessage()], 409);
        }
    }

    public function videoAttach(Request $request, SwingAnalysis $analysis): JsonResponse
    {
        $this->authorizeOwner($request, $analysis);
        $request->validate(['path' => ['required', 'string', 'max:255']]);

        return SwingAnalysisStore::attachVideo($analysis, $request->input('path'))
            ? response()->json(['ok' => true])
            : response()->json(['ok' => false, 'message' => 'Path video tidak valid.'], 422);
    }

    public function videoDestroy(Request $request, SwingAnalysis $analysis): RedirectResponse
    {
        $this->authorizeOwner($request, $analysis);
        SwingAnalysisStore::deleteVideo($analysis);

        return back()->with('success', 'Video swing dihapus. Hasil analisis tetap tersimpan.');
    }

    public function destroy(Request $request, SwingAnalysis $analysis): RedirectResponse
    {
        $this->authorizeOwner($request, $analysis);
        SwingAnalysisStore::deleteAll($analysis);

        return redirect()->route('swing.index')->with('success', 'Analisis swing dihapus.');
    }

    public function history(Request $request): View
    {
        $items = SwingAnalysis::where('user_id', $request->user()->id)->latest()->paginate(12);

        $trend = SwingAnalysis::where('user_id', $request->user()->id)
            ->latest()->take(10)->get(['id', 'score', 'created_at'])->reverse()->values();

        return view('swing.history', compact('items', 'trend'));
    }

    private function authorizeOwner(Request $request, SwingAnalysis $analysis): void
    {
        abort_unless($request->user() && $request->user()->id === $analysis->user_id, 404);
    }

    private function authorizeView(Request $request, SwingAnalysis $analysis): void
    {
        $user = $request->user();

        abort_unless($user && ($user->id === $analysis->user_id || $user->role === 'admin'), 404);
    }
}
