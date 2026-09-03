<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'siswa_id', 'field', 'nilai_lama', 'nilai_baru', 'alasan', 'status',
])]
class PengajuanPerubahanSiswa extends Model
{
    public const FIELDS = [
        'nama' => 'Nama lengkap',
        'jenis_kelamin' => 'Jenis kelamin',
        'nisn' => 'NISN',
        'nis' => 'NIS lokal',
        'nik' => 'NIK',
        'tempat_lahir' => 'Tempat lahir',
        'tanggal_lahir' => 'Tanggal lahir',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
}
