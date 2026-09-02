<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['tahun_ajaran_id', 'tingkat', 'nama', 'program', 'wali_kelas_id'])]
class Rombel extends Model
{
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }

    public function siswas(): BelongsToMany
    {
        return $this->belongsToMany(Siswa::class, 'rombel_siswas')
            ->withPivot('status')
            ->withTimestamps();
    }
}
