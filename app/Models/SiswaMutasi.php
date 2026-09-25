<?php

namespace App\Models;

use Database\Factories\SiswaMutasiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'jenis',
    'siswa_id',
    'tanggal',
    'alasan',
    'jenis_sekolah',
    'nomor_dokumen_emis',
    'nama_sekolah',
    'rombel_id',
    'tahun_ajaran_id',
    'nomor_surat',
    'path_surat',
    'dicatat_oleh',
])]
class SiswaMutasi extends Model
{
    /** @use HasFactory<SiswaMutasiFactory> */
    use HasFactory;

    public const JENIS_MASUK = 'masuk';

    public const JENIS_KELUAR = 'keluar';

    public const JENIS_DO = 'do';

    public const SEKOLAH_MADRASAH = 'madrasah';

    public const SEKOLAH_UMUM = 'umum';

    /**
     * @return array<string, string>
     */
    public static function tabOptions(): array
    {
        return [
            self::JENIS_MASUK => 'Masuk',
            self::JENIS_KELUAR => 'Keluar',
            self::JENIS_DO => 'Dropout',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function alasanOptions(): array
    {
        return [
            'Kendala ekonomi' => 'Kendala ekonomi',
            'Kendala akademik' => 'Kendala akademik',
            'Ikut pindah orang tua' => 'Ikut pindah orang tua',
            'Pengaruh teman/lingkungan' => 'Pengaruh teman/lingkungan',
            'Lainnya' => 'Lainnya',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function jenisSekolahOptions(): array
    {
        return [
            self::SEKOLAH_MADRASAH => 'Madrasah',
            self::SEKOLAH_UMUM => 'Umum',
        ];
    }

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
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

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    public function isMasuk(): bool
    {
        return $this->jenis === self::JENIS_MASUK;
    }

    public function isKeluar(): bool
    {
        return $this->jenis === self::JENIS_KELUAR;
    }

    public function isDo(): bool
    {
        return $this->jenis === self::JENIS_DO;
    }

    public function isNonaktif(): bool
    {
        return $this->isKeluar() || $this->isDo();
    }
}
