<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'siswa_id',
    'versi_teks',
    'teks_poin_1',
    'teks_poin_2',
    'setuju_poin_1',
    'setuju_poin_2',
    'nama_siswa',
    'nama_wali',
    'ttd_siswa_path',
    'ttd_wali_path',
    'dikonfirmasi_at',
])]
class SiswaPernyataan extends Model
{
    protected $table = 'siswa_pernyataan';

    protected function casts(): array
    {
        return [
            'setuju_poin_1' => 'boolean',
            'setuju_poin_2' => 'boolean',
            'dikonfirmasi_at' => 'datetime',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
}
