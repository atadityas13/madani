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
    'lampiran_path',
    'jenis_bukti',
    'nama_wali',
    'dilaporkan_oleh',
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

    public const JENIS_ALPA = 'alpa';

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    /** @var array<string, string> */
    public const JENIS_LABEL = [
        self::JENIS_IZIN => 'Izin',
        self::JENIS_SAKIT => 'Sakit',
        self::JENIS_ALPA => 'Alpa',
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

    public function dilaporkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dilaporkan_oleh');
    }

    public function isAktif(): bool
    {
        return $this->status === self::STATUS_AKTIF;
    }

    public function punyaSuratOrtu(): bool
    {
        return in_array($this->jenis, [self::JENIS_IZIN, self::JENIS_SAKIT], true)
            && filled($this->ttd_wali_path);
    }

    public function labelJenis(): string
    {
        return self::JENIS_LABEL[$this->jenis] ?? $this->jenis;
    }
}
