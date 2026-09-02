<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'siswa_id', 'peran', 'nama', 'nik', 'kewarganegaraan', 'kitas', 'negara_asal',
    'status', 'status_hidup', 'tempat_lahir', 'tanggal_lahir', 'pendidikan',
    'pekerjaan', 'penghasilan', 'no_hp', 'tidak_punya_hp', 'domisili',
    'status_tempat_tinggal', 'alamat', 'rt', 'rw', 'desa', 'kecamatan', 'kota',
    'provinsi', 'kode_pos', 'sama_dengan_ayah',
])]
class OrangTua extends Model
{
    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'sama_dengan_ayah' => 'boolean',
            'tidak_punya_hp' => 'boolean',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
}
