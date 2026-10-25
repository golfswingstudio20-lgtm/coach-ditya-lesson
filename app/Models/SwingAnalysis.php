<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu hasil AI Swing Analysis milik customer.
 *
 * metrics: [{key, label, score, value, unit, note}]
 * phases:  [{key, name, score, note, image}]   image = path di disk public (boleh null)
 * tips:    [{title, text}]
 */
class SwingAnalysis extends Model
{
    public const VIEWS = [
        'face_on'       => 'Face-on (dari depan)',
        'down_the_line' => 'Down-the-line (dari samping)',
    ];

    public const HANDS = [
        'right' => 'Tangan kanan',
        'left'  => 'Kidal',
    ];

    protected $fillable = [
        'user_id', 'camera_view', 'handedness', 'score', 'metrics', 'phases', 'tips',
        'insight', 'duration_ms', 'frames', 'coach_note',
    ];

    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'phases'  => 'array',
            'tips'    => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function gradeFor(int $score): string
    {
        return $score >= 80 ? 'Baik' : ($score >= 65 ? 'Cukup' : 'Perlu Evaluasi');
    }

    public static function toneFor(int $score): string
    {
        return $score >= 80 ? 'good' : ($score >= 65 ? 'mid' : 'low');
    }

    public function getGradeLabelAttribute(): string
    {
        return self::gradeFor((int) $this->score);
    }

    public function getViewLabelAttribute(): string
    {
        return self::VIEWS[$this->camera_view] ?? $this->camera_view;
    }

    /** URL gambar tahapan dari disk public (Supabase di Vercel, lokal di laptop). */
    public static function imageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        try {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
