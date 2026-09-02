<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'siswa_id', 'peran', 'nama', 'nik', 'status', 'tempat_lahir', 'tanggal_lahir',
    'pendidikan', 'pekerjaan', 'penghasilan', 'no_hp', 'alamat', 'desa',
    'kecamatan', 'kota', 'provinsi', 'sama_dengan_ayah',
])]
class OrangTua extends Model
{
    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'sama_dengan_ayah' => 'boolean',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
}
