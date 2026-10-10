<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class GalleryAlbum extends Model
{
    protected $fillable = ['key', 'name', 'description', 'kind', 'cover', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    /** Dipakai kalau tabel belum ada (sebelum migrasi). */
    public const DEFAULTS = [
        'prestasi'  => ['Prestasi Murid', 'Pencapaian & turnamen murid', 'image'],
        'latihan'   => ['Momen Latihan', 'Suasana sesi latihan & lapangan', 'image'],
        'youtube'   => ['Video YouTube', 'Tutorial & tips dari YouTube', 'video'],
        'instagram' => ['Video Instagram', 'Reel & video latihan', 'video'],
    ];

    private static ?array $cache = null;

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function getCoverUrlAttribute(): ?string
    {
        return Gallery::mediaUrl($this->cover);
    }

    /**
     * Semua folder (urut), key => [nama, keterangan, jenis, aktif, sampul].
     */
    public static function map(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        try {
            if (Schema::hasTable('gallery_albums')) {
                $rows = static::ordered()->get();
                if ($rows->isNotEmpty()) {
                    return self::$cache = $rows->mapWithKeys(fn ($a) => [
                        $a->key => [$a->name, (string) $a->description, $a->kind, $a->is_active, $a->cover_url],
                    ])->all();
                }
            }
        } catch (Throwable $e) {
            report($e);
        }

        return self::$cache = collect(self::DEFAULTS)->map(fn ($d) => [$d[0], $d[1], $d[2], true, null])->all();
    }

    public static function flush(): void
    {
        self::$cache = null;
    }

    /** Key unik dari nama folder. */
    public static function makeKey(string $name): string
    {
        $base = Str::slug($name, '-') ?: 'folder';
        $base = Str::limit($base, 30, '');
        $key  = $base;
        $i    = 2;

        while (static::where('key', $key)->exists()) {
            $key = $base . '-' . $i++;
        }

        return $key;
    }
}
