<?php

namespace App\Models;

use App\Support\KelengkapanSiswa;
use App\Support\SiswaPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'nisn', 'punya_nisn', 'nik', 'punya_nik', 'nism', 'nis', 'nama',
    'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama',
    'kewarganegaraan', 'anak_ke', 'jumlah_saudara',
    'cita_cita', 'hobi', 'email', 'no_hp',
    'tidak_punya_hp', 'tidak_punya_email', 'foto', 'status_keaktifan', 'tanggal_nonaktif',
    'alasan_nonaktif', 'must_change_password',
])]
#[Hidden(['password', 'remember_token'])]
class Siswa extends Authenticatable
{
    use HasApiTokens, HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_nonaktif' => 'date',
            'punya_nisn' => 'boolean',
            'punya_nik' => 'boolean',
            'tidak_punya_hp' => 'boolean',
            'tidak_punya_email' => 'boolean',
            'must_change_password' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function bisaMasuk(): bool
    {
        return $this->status_keaktifan !== 'nonaktif' && filled($this->nisn);
    }

    public function ensurePasswordAwal(): void
    {
        if (filled($this->password)) {
            return;
        }

        $plain = SiswaPassword::dariTanggalLahir($this->tanggal_lahir);

        if ($plain === null) {
            return;
        }

        $this->password = $plain;
        $this->must_change_password = true;
    }

    public function resetPasswordAwal(): bool
    {
        $plain = SiswaPassword::dariTanggalLahir($this->tanggal_lahir);

        if ($plain === null) {
            return false;
        }

        $this->forceFill([
            'password' => $plain,
            'must_change_password' => true,
        ])->save();

        $this->tokens()->delete();

        return true;
    }

    public function gantiPassword(string $baru): void
    {
        $this->forceFill([
            'password' => $baru,
            'must_change_password' => false,
        ])->save();
    }

    /**
     * @return array{persen: int, wajib_selesai: int, wajib_total: int, wajib_semua_selesai: bool, tab: list<array{id: string, label: string, selesai: bool, wajib: bool, terbuka: bool}>}
     */
    public function kelengkapan(): array
    {
        return KelengkapanSiswa::ringkasan($this);
    }

    public function orangTuas(): HasMany
    {
        return $this->hasMany(OrangTua::class);
    }

    public function ayah(): HasOne
    {
        return $this->hasOne(OrangTua::class)->where('peran', 'ayah');
    }

    public function ibu(): HasOne
    {
        return $this->hasOne(OrangTua::class)->where('peran', 'ibu');
    }

    public function wali(): HasOne
    {
        return $this->hasOne(OrangTua::class)->where('peran', 'wali');
    }

    public function periodiks(): HasMany
    {
        return $this->hasMany(SiswaPeriodik::class);
    }

    public function periodikAktif(): ?SiswaPeriodik
    {
        $this->loadMissing('periodiks');

        $tahunId = TahunAjaran::aktif()?->id;

        if ($tahunId) {
            return $this->periodiks->firstWhere('tahun_ajaran_id', $tahunId);
        }

        return $this->periodiks->sortByDesc('id')->first();
    }

    public function rombels(): BelongsToMany
    {
        return $this->belongsToMany(Rombel::class, 'rombel_siswas')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function dokumens(): HasMany
    {
        return $this->hasMany(Dokumen::class);
    }

    public function pengajuanPerubahans(): HasMany
    {
        return $this->hasMany(PengajuanPerubahanSiswa::class);
    }

    public function beasiswas(): HasMany
    {
        return $this->hasMany(Beasiswa::class);
    }

    public function prestasis(): HasMany
    {
        return $this->hasMany(Prestasi::class);
    }

    public function rekamDidik(): HasOne
    {
        return $this->hasOne(RekamDidik::class);
    }

    public function pernyataan(): HasOne
    {
        return $this->hasOne(SiswaPernyataan::class);
    }

    public function rombelAktif(): ?Rombel
    {
        return $this->rombels()
            ->wherePivot('status', 'aktif')
            ->latest('rombel_siswas.id')
            ->first();
    }

    public function dokumenJenis(string $jenis): ?Dokumen
    {
        $this->loadMissing('dokumens');

        return $this->dokumens->firstWhere('jenis', $jenis);
    }

    /**
     * Status masuk dan sekolah asal, dari Rekam didik (siswa baru) atau Mutasi (pindahan).
     *
     * @return array{status: string, alasan: ?string, nama_sekolah_asal: ?string, npsn_asal: ?string}
     */
    public function dataMasukAkademik(): array
    {
        $this->loadMissing(['rekamDidik', 'periodiks']);

        $periodik = $this->periodikAktif();
        $rekam = $this->rekamDidik;
        $mutasi = $this->dataMutasiMasuk();
        $pindahan = $mutasi !== null || $periodik?->alasan_masuk === 'Pindahan';

        return [
            'status' => $pindahan ? 'Pindahan' : 'Baru',
            'alasan' => $pindahan ? ($mutasi['alasan'] ?? null) : null,
            'nama_sekolah_asal' => $pindahan
                ? ($mutasi['nama_sekolah'] ?? $periodik?->nama_sekolah_asal)
                : ($rekam?->nama_sd ?: $periodik?->nama_sekolah_asal),
            'npsn_asal' => $pindahan
                ? ($mutasi['npsn'] ?? $periodik?->npsn_asal)
                : ($rekam?->npsn ?: $periodik?->npsn_asal),
        ];
    }

    /**
     * Mutasi masuk akan diisi setelah modul Mutasi tersedia.
     *
     * @return array{alasan: ?string, nama_sekolah: ?string, npsn: ?string}|null
     */
    public function dataMutasiMasuk(): ?array
    {
        return null;
    }
}
