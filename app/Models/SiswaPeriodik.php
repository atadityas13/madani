<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'siswa_id', 'tahun_ajaran_id', 'tempat_tinggal', 'alamat', 'rt', 'rw', 'desa',
    'kecamatan', 'kota', 'provinsi', 'kode_pos', 'kode_wilayah', 'koordinat',
    'transportasi', 'jarak', 'waktu_tempuh', 'pembiaya', 'no_kk', 'kepala_keluarga',
    'no_kip', 'pra_sekolah', 'imunisasi', 'kebutuhan_khusus', 'disabilitas',
])]
class SiswaPeriodik extends Model
{
    protected function casts(): array
    {
        return [
            'imunisasi' => 'array',
            'kebutuhan_khusus' => 'array',
            'disabilitas' => 'array',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }
}
