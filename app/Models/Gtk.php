<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'nama',
    'gelar_depan',
    'gelar_belakang',
    'nip',
    'nuptk',
    'jenis_kelamin',
    'tempat_lahir',
    'tanggal_lahir',
    'agama',
    'nomor_hp',
    'email',
    'alamat',
    'jabatan',
    'golongan',
    'status_pegawai',
    'kode_internal',
    'duk',
    'foto_url',
    'jenis',
    'status',
    'meta',
    'simpatisans_guru_id',
])]
class Gtk extends Model
{
    public const JENIS_GURU = 'guru';

    public const JENIS_TENDIK = 'tendik';

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'meta' => 'array',
        ];
    }

    public function rombels(): HasMany
    {
        return $this->hasMany(Rombel::class);
    }

    public function akun(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function isAktif(): bool
    {
        return $this->status === 'aktif';
    }

    public function isGuru(): bool
    {
        return $this->jenis === self::JENIS_GURU;
    }

    public function isTendik(): bool
    {
        return $this->jenis === self::JENIS_TENDIK;
    }

    /**
     * @return array<string, string>
     */
    public static function jenisOptions(): array
    {
        return [
            self::JENIS_GURU => 'Guru',
            self::JENIS_TENDIK => 'Tenaga kependidikan',
        ];
    }

    protected function namaLengkap(): Attribute
    {
        return Attribute::get(function (): string {
            $depan = trim((string) $this->gelar_depan);
            $belakang = trim((string) $this->gelar_belakang);
            $nama = trim((string) $this->nama);

            $prefix = ($depan !== '' && $depan !== '-') ? $depan.' ' : '';
            $suffix = ($belakang !== '' && $belakang !== '-') ? ', '.$belakang : '';

            return $prefix.$nama.$suffix;
        });
    }

    public function metaGet(string $key, mixed $default = null): mixed
    {
        return data_get($this->meta ?? [], $key, $default);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function metaMerge(array $values): void
    {
        $this->meta = array_merge($this->meta ?? [], $values);
    }
}
