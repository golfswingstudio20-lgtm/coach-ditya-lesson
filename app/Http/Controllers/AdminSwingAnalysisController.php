<?php

namespace App\Http\Controllers;

use App\Models\SwingAnalysis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Golf Analyzer di panel admin: coach melihat semua hasil AI Swing Analysis customer
 * dan menambahkan "Catatan Coach" yang tampil di halaman hasil milik customer.
 */
class AdminSwingAnalysisController extends Controller
{
    public function index(Request $request): View
    {
        $q     = trim((string) $request->query('q', ''));
        $grade = (string) $request->query('nilai', '');
        $note  = (string) $request->query('catatan', '');

        $query = SwingAnalysis::with('user:id,name,email')->latest();

        if ($q !== '') {
            $like = '%' . mb_strtolower($q) . '%';
            $query->whereHas('user', fn ($u) => $u->whereRaw('lower(name) like ?', [$like])->orWhereRaw('lower(email) like ?', [$like]));
        }

        if ($grade === 'baik') {
            $query->where('score', '>=', 80);
        } elseif ($grade === 'cukup') {
            $query->whereBetween('score', [65, 79]);
        } elseif ($grade === 'evaluasi') {
            $query->where('score', '<', 65);
        }

        if ($note === 'belum') {
            $query->where(fn ($w) => $w->whereNull('coach_note')->orWhere('coach_note', ''));
        } elseif ($note === 'sudah') {
            $query->whereNotNull('coach_note')->where('coach_note', '!=', '');
        }

        $all = SwingAnalysis::query();

        return view('admin.swing.index', [
            'items'  => $query->paginate(15)->withQueryString(),
            'q'      => $q,
            'grade'  => $grade,
            'note'   => $note,
            'stats'  => [
                'total'    => (clone $all)->count(),
                'avg'      => (int) round((float) (clone $all)->avg('score')),
                'players'  => (clone $all)->distinct()->count('user_id'),
                'no_note'  => (clone $all)->where(fn ($w) => $w->whereNull('coach_note')->orWhere('coach_note', ''))->count(),
            ],
        ]);
    }

    public function show(SwingAnalysis $analysis): View
    {
        $analysis->load('user:id,name,email');

        $history = SwingAnalysis::where('user_id', $analysis->user_id)
            ->latest()->take(8)->get(['id', 'score', 'created_at']);

        return view('admin.swing.show', compact('analysis', 'history'));
    }

    public function updateNote(Request $request, SwingAnalysis $analysis): RedirectResponse
    {
        $data = $request->validate([
            'coach_note' => ['nullable', 'string', 'max:1500'],
        ]);

        $analysis->update(['coach_note' => trim((string) ($data['coach_note'] ?? '')) ?: null]);

        return back()->with('success', 'Catatan coach disimpan. Customer bisa melihatnya di halaman hasil analisis.');
    }
}
