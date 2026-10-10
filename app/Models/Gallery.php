<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Gallery extends Model
{
    protected $fillable = [
        'title',
        'description',
        'type',        // image | video
        'image',       // foto, atau thumbnail untuk video
        'video_url',   // link YouTube
        'video_file',  // file video hasil upload
        'category',
        'status',      // active | inactive
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /* ---------------------------------------------------------------
     | SCOPES
     * --------------------------------------------------------------- */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeImages(Builder $query): Builder
    {
        return $query->where('type', 'image');
    }

    public function scopeVideos(Builder $query): Builder
    {
        return $query->where('type', 'video');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('created_at');
    }

    /* ---------------------------------------------------------------
     | HELPER & ACCESSORS
     * --------------------------------------------------------------- */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Mengubah path menjadi URL.
     * Mendukung: URL penuh, file di folder public/, dan file di storage.
     */
    public static function mediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        return Storage::disk('public')->url($path);
    }

    public function getImageUrlAttribute(): ?string
    {
        return static::mediaUrl($this->image);
    }

    public function getVideoFileUrlAttribute(): ?string
    {
        return static::mediaUrl($this->video_file);
    }

    public function getYoutubeIdAttribute(): ?string
    {
        if (! $this->video_url) {
            return null;
        }

        $pattern = '~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})~';

        return preg_match($pattern, $this->video_url, $match) ? $match[1] : null;
    }

    /** Kode postingan Instagram: ['p'|'reel', 'ABC123'] atau null. */
    public function getInstagramPostAttribute(): ?array
    {
        if (! $this->video_url) {
            return null;
        }

        if (! preg_match('~instagram\.com/(p|reel|reels|tv)/([A-Za-z0-9_-]+)~i', $this->video_url, $m)) {
            return null;
        }

        $kind = strtolower($m[1]) === 'p' ? 'p' : (strtolower($m[1]) === 'tv' ? 'tv' : 'reel');

        return [$kind, $m[2]];
    }

    /** youtube | instagram | file | null */
    public function getVideoPlatformAttribute(): ?string
    {
        if ($this->type !== 'video') {
            return null;
        }
        if ($this->youtube_id) {
            return 'youtube';
        }
        if ($this->instagram_post) {
            return 'instagram';
        }

        return $this->video_file ? 'file' : null;
    }

    public function getEmbedUrlAttribute(): ?string
    {
        if ($this->youtube_id) {
            return "https://www.youtube.com/embed/{$this->youtube_id}";
        }

        if ($ig = $this->instagram_post) {
            return "https://www.instagram.com/{$ig[0]}/{$ig[1]}/embed/captioned/";
        }

        return null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->image_url) {
            return $this->image_url;
        }

        return $this->youtube_id ? "https://img.youtube.com/vi/{$this->youtube_id}/hqdefault.jpg" : null;
    }
}
