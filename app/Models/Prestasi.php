<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['siswa_id', 'nama', 'jenis', 'tingkat', 'tahun', 'penyelenggara', 'sertifikat_path'])]
class Prestasi extends Model
{
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
}
