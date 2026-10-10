<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Gallery extends Model
{
    /** Folder galeri dari database (diatur admin): key => [nama, keterangan, jenis, aktif, sampul]. */
    public static function albums(): array
    {
        return GalleryAlbum::map();
    }

    protected $fillable = [
        'title',
        'description',
        'type',        // image | video
        'image',       // foto, atau thumbnail untuk video
        'video_url',   // link YouTube
        'video_file',  // file video hasil upload
        'social_url',  // link Instagram / YouTube tambahan untuk video upload
        'album',       // folder: latihan | prestasi | youtube | instagram
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

    public function scopeAlbum(Builder $query, string $album): Builder
    {
        return $query->where('album', $album);
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

    /** Link "Lihat di ..." untuk tombol di bawah video: [url, label] atau null. */
    public function getSocialLinkAttribute(): ?array
    {
        $url = $this->social_url ?: ($this->instagram_post ? $this->video_url : null);
        if (! $url) {
            return null;
        }

        $label = match (true) {
            str_contains($url, 'instagram.com') => 'Instagram',
            str_contains($url, 'youtu')         => 'YouTube',
            str_contains($url, 'tiktok.com')    => 'TikTok',
            str_contains($url, 'facebook.com'), str_contains($url, 'fb.watch') => 'Facebook',
            default                             => 'sumber asli',
        };

        return [$url, $label];
    }

    /** Folder yang dipakai; kalau kosong ditebak dari jenis media. */
    public function getAlbumKeyAttribute(): string
    {
        if ($this->album && isset(self::albums()[$this->album])) {
            return $this->album;
        }

        return static::guessAlbum($this->type, (string) $this->video_url . ' ' . (string) $this->social_url);
    }

    public function getAlbumLabelAttribute(): string
    {
        return self::albums()[$this->album_key][0] ?? 'Galeri';
    }

    public static function guessAlbum(?string $type, string $links = ''): string
    {
        $albums = self::albums();

        if ($type === 'video') {
            $want = str_contains($links, 'youtu') && ! str_contains($links, 'instagram.com') ? 'youtube' : 'instagram';
        } else {
            $want = 'latihan';
        }

        if (isset($albums[$want])) {
            return $want;
        }

        // Folder bawaan sudah dihapus/diganti: pakai folder pertama dengan jenis yang sama.
        foreach ($albums as $key => $meta) {
            if ($meta[2] === ($type === 'video' ? 'video' : 'image')) {
                return $key;
            }
        }

        return (string) array_key_first($albums);
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
            return "https://www.instagram.com/{$ig[0]}/{$ig[1]}/embed/";
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
