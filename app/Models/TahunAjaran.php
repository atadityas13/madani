<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama', 'tanggal_mulai', 'tanggal_selesai', 'is_aktif'])]
class TahunAjaran extends Model
{
    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'is_aktif' => 'boolean',
        ];
    }

    public function rombels(): HasMany
    {
        return $this->hasMany(Rombel::class);
    }

    public static function aktif(): ?self
    {
        return static::query()->where('is_aktif', true)->first();
    }
}
