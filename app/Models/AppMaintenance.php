<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'is_active',
    'title',
    'message',
    'show_countdown',
    'ends_at',
    'updated_by',
])]
class AppMaintenance extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_countdown' => 'boolean',
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

    public static function isActive(): bool
    {
        return (bool) static::current()?->is_active;
    }

    /**
     * @return array{show_countdown: bool, ends_at: ?string}
     */
    public function countdownPayload(): array
    {
        $show = (bool) $this->show_countdown && $this->ends_at !== null;

        return [
            'show_countdown' => $show,
            'ends_at' => $show
                ? $this->ends_at->copy()->timezone('Asia/Jakarta')->toIso8601String()
                : null,
        ];
    }
}
