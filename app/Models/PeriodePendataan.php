<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'judul',
    'pesan',
    'is_active',
    'starts_at',
    'ends_at',
    'updated_by',
])]
class PeriodePendataan extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function current(): ?self
    {
        return static::query()->orderByDesc('id')->first();
    }

    public function isCurrentlyOpen(): bool
    {
        if (! $this->is_active || $this->starts_at === null || $this->ends_at === null) {
            return false;
        }

        $now = now();

        return $this->starts_at->lte($now) && $this->ends_at->gte($now);
    }

    /**
     * @return array{
     *     active: bool,
     *     judul?: string,
     *     pesan?: ?string,
     *     starts_at?: string,
     *     ends_at?: string,
     *     seconds_remaining?: int
     * }
     */
    public function siswaPayload(): array
    {
        if (! $this->isCurrentlyOpen()) {
            return ['active' => false];
        }

        $seconds = max(0, $this->ends_at->getTimestamp() - now()->getTimestamp());

        return [
            'active' => true,
            'judul' => $this->judul,
            'pesan' => $this->pesan,
            'starts_at' => $this->starts_at->copy()->timezone('Asia/Jakarta')->toIso8601String(),
            'ends_at' => $this->ends_at->copy()->timezone('Asia/Jakarta')->toIso8601String(),
            'seconds_remaining' => $seconds,
        ];
    }
}
