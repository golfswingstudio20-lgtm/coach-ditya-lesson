<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Event extends Model
{
    public const STATUSES = [
        'open'   => 'Registrasi Dibuka',
        'closed' => 'Registrasi Ditutup',
        'full'   => 'Kuota Penuh',
    ];

    protected $fillable = [
        'title',
        'description',
        'poster',
        'event_date',
        'start_time',
        'end_time',
        'location',
        'registration_status',
        'price',
        'price_unit',
        'note',
        'is_active',
        // Pembayaran ke penyelenggara (diatur admin per event)
        'organizer',
        'pay_qr',
        'pay_accounts',
        'pay_instructions',
        'pay_contact',
        'pay_link',
        'referral_code',
        'referral_note',
    ];

    protected $casts = [
        'event_date' => 'date',
        'is_active'  => 'boolean',
        'price'      => 'integer',
    ];

    /* ---------------------------------------------------------------
     | SCOPES
     * --------------------------------------------------------------- */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('event_date', '>=', today());
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->whereDate('event_date', '<', today());
    }

    public function scopeChronological(Builder $query): Builder
    {
        return $query->orderBy('event_date')->orderBy('start_time');
    }

    /* ---------------------------------------------------------------
     | HELPER
     * --------------------------------------------------------------- */
    public function isPast(): bool
    {
        return $this->event_date && $this->event_date->lt(today());
    }

    public function canRegister(): bool
    {
        return ! $this->isPast() && $this->registration_status === 'open';
    }

    /* ---------------------------------------------------------------
     | ACCESSORS
     * --------------------------------------------------------------- */
    public function getPosterUrlAttribute(): ?string
    {
        $path = $this->poster;

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

    public function getPayQrUrlAttribute(): ?string
    {
        return $this->pay_qr ? \App\Models\Gallery::mediaUrl($this->pay_qr) : null;
    }

    /** Rekening / e-wallet: [['bank' => 'BCA', 'number' => '123', 'name' => 'a.n. X'], ...] */
    public function getPayAccountListAttribute(): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $this->pay_accounts))
            ->map(fn ($l) => trim($l))->filter()
            ->map(function ($line) {
                $p = array_map('trim', explode('|', $line));

                return ['bank' => $p[0] ?? '', 'number' => $p[1] ?? '', 'name' => $p[2] ?? ''];
            })->values()->all();
    }

    /** Nomor WhatsApp penyelenggara dalam format 62xxx. */
    public function getPayWhatsappAttribute(): ?string
    {
        $n = preg_replace('/\D+/', '', (string) $this->pay_contact);
        if ($n === '') {
            return null;
        }

        return str_starts_with($n, '0') ? '62' . substr($n, 1) : $n;
    }

    public function hasPaymentInfo(): bool
    {
        return (bool) ($this->pay_qr || $this->pay_accounts || $this->pay_link || $this->pay_contact || $this->pay_instructions);
    }

    /** Kata pertama judul (warna putih) */
    public function getTitleFirstAttribute(): string
    {
        return Str::before(trim($this->title), ' ');
    }

    /** Sisa judul setelah kata pertama (warna hijau) */
    public function getTitleRestAttribute(): string
    {
        $title = trim($this->title);

        return Str::contains($title, ' ') ? Str::after($title, ' ') : '';
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->isPast()) {
            return 'Event Selesai';
        }

        return self::STATUSES[$this->registration_status] ?? 'Registrasi Dibuka';
    }

    public function getDateLabelAttribute(): string
    {
        return $this->event_date
            ? $this->event_date->locale('id')->translatedFormat('d F Y')
            : '-';
    }

    public function getTimeRangeAttribute(): string
    {
        $start = $this->start_time ? substr($this->start_time, 0, 5) : null;
        $end   = $this->end_time ? substr($this->end_time, 0, 5) : null;

        if ($start && $end) {
            return "{$start} – {$end}";
        }

        return $start ?? '-';
    }

    public function getPriceLabelAttribute(): string
    {
        return $this->price > 0
            ? 'Rp' . number_format($this->price, 0, ',', '.')
            : 'Gratis';
    }
}
