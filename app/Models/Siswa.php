<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'nisn', 'punya_nisn', 'nik', 'punya_nik', 'nism', 'nis', 'nama',
    'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama',
    'kewarganegaraan', 'kitas', 'negara_asal', 'anak_ke', 'jumlah_saudara',
    'cita_cita', 'cita_cita_lainnya', 'hobi', 'email', 'no_hp',
    'tidak_punya_hp', 'foto', 'status_keaktifan', 'tanggal_nonaktif',
    'alasan_nonaktif',
])]
class Siswa extends Model
{
    use HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_nonaktif' => 'date',
            'punya_nisn' => 'boolean',
            'punya_nik' => 'boolean',
            'tidak_punya_hp' => 'boolean',
        ];
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

    public function rombelAktif(): ?Rombel
    {
        return $this->rombels()
            ->wherePivot('status', 'aktif')
            ->latest('rombel_siswas.id')
            ->first();
    }

    public function dokumenJenis(string $jenis): ?Dokumen
    {
        return $this->dokumens->firstWhere('jenis', $jenis);
    }
}
