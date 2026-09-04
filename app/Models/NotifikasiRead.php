<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'notifikasi_id',
    'reader_type',
    'reader_id',
    'read_at',
    'cleared_at',
])]
class NotifikasiRead extends Model
{
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'cleared_at' => 'datetime',
        ];
    }

    public function notifikasi(): BelongsTo
    {
        return $this->belongsTo(Notifikasi::class);
    }

    public function reader(): MorphTo
    {
        return $this->morphTo();
    }
}
