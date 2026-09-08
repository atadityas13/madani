<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'siswa_id', 'nama_ijazah',
    'tempat_lahir_ijazah', 'tanggal_lahir_ijazah', 'jenis_kelamin_ijazah',
    'nama_ayah_ijazah', 'nama_sd', 'npsn', 'tahun_ajaran_kelulusan', 'nip_kepala_sekolah',
    'nama_kepala_sekolah', 'nomor_seri_ijazah', 'tanggal_terbit_ijazah',
    'status_verval', 'ijazah_sesuai', 'ijazah_sesuai_fields',
])]
class RekamDidik extends Model
{
    protected function casts(): array
    {
        return [
            'tanggal_lahir_ijazah' => 'date',
            'tanggal_terbit_ijazah' => 'date',
            'ijazah_sesuai' => 'boolean',
            'ijazah_sesuai_fields' => 'array',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
}
