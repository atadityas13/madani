<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'siswa_id',
    'rombel_id',
    'jenis',
    'tanggal',
    'alasan',
    'pernyataan_disetujui',
    'ttd_wali_path',
    'status',
    'dibatalkan_oleh',
    'dibatalkan_at',
    'alasan_batal',
])]
class IzinSiswa extends Model
{
    use HasUuids;

    public const JENIS_IZIN = 'izin';

    public const JENIS_SAKIT = 'sakit';

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    /** @var array<string, string> */
    public const JENIS_LABEL = [
        self::JENIS_IZIN => 'Izin',
        self::JENIS_SAKIT => 'Sakit',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'pernyataan_disetujui' => 'boolean',
            'dibatalkan_at' => 'datetime',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function rombel(): BelongsTo
    {
        return $this->belongsTo(Rombel::class);
    }

    public function dibatalkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh');
    }

    public function isAktif(): bool
    {
        return $this->status === self::STATUS_AKTIF;
    }

    public function labelJenis(): string
    {
        return self::JENIS_LABEL[$this->jenis] ?? $this->jenis;
    }
}
