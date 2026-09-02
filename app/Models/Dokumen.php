<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['siswa_id', 'jenis', 'path', 'nama_asli'])]
class Dokumen extends Model
{
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
}
