<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama', 'nip', 'nuptk', 'jenis_kelamin', 'status'])]
class Gtk extends Model
{
    public function rombels(): HasMany
    {
        return $this->hasMany(Rombel::class);
    }

    public function isAktif(): bool
    {
        return $this->status === 'aktif';
    }
}
