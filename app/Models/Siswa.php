<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'nisn', 'nik', 'nism', 'nis', 'nama', 'tempat_lahir', 'tanggal_lahir',
    'jenis_kelamin', 'agama', 'kewarganegaraan', 'anak_ke', 'jumlah_saudara',
    'cita_cita', 'hobi', 'email', 'no_hp', 'foto', 'status_keaktifan',
    'tanggal_nonaktif', 'alasan_nonaktif',
])]
class Siswa extends Model
{
    use HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_nonaktif' => 'date',
        ];
    }

    public function orangTuas(): HasMany
    {
        return $this->hasMany(OrangTua::class);
    }

    public function periodiks(): HasMany
    {
        return $this->hasMany(SiswaPeriodik::class);
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

    public function rombelAktif(): ?Rombel
    {
        return $this->rombels()
            ->wherePivot('status', 'aktif')
            ->latest('rombel_siswas.id')
            ->first();
    }
}
