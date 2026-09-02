<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'siswa_id', 'tahun_ajaran_id', 'tempat_tinggal', 'alamat', 'rt', 'rw', 'desa',
    'kecamatan', 'kota', 'provinsi', 'kode_pos', 'kode_wilayah', 'koordinat',
    'transportasi', 'jarak', 'waktu_tempuh', 'pembiaya', 'no_kk', 'kepala_keluarga',
    'no_kip', 'no_kks', 'no_pkh', 'pra_sekolah', 'pernah_tk_ra', 'pernah_paud',
    'imunisasi', 'kebutuhan_khusus', 'kebutuhan_khusus_lainnya', 'disabilitas',
    'disabilitas_lainnya', 'tanggal_masuk', 'alasan_masuk', 'npsn_asal',
    'nama_sekolah_asal',
])]
class SiswaPeriodik extends Model
{
    protected function casts(): array
    {
        return [
            'imunisasi' => 'array',
            'kebutuhan_khusus' => 'array',
            'disabilitas' => 'array',
            'pernah_tk_ra' => 'boolean',
            'pernah_paud' => 'boolean',
            'tanggal_masuk' => 'date',
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

    public function kebutuhanKhususLabel(): ?string
    {
        $items = $this->kebutuhan_khusus ?? [];

        return is_array($items) ? ($items[0] ?? null) : $items;
    }

    public function disabilitasLabel(): ?string
    {
        $items = $this->disabilitas ?? [];

        return is_array($items) ? ($items[0] ?? null) : $items;
    }
}
