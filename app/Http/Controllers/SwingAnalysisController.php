<?php

namespace App\Http\Controllers;

use App\Models\SwingAnalysis;
use App\Support\SwingAnalysisStore;
use Illuminate\Http\JsonResponse;
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
}
