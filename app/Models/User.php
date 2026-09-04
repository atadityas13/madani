<?php

namespace App\Models;

use App\Support\Peran;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'username', 'email', 'password', 'must_change_password', 'is_aktif', 'gtk_id', 'foto'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'is_aktif' => 'boolean',
        ];
    }

    public function gtk(): BelongsTo
    {
        return $this->belongsTo(Gtk::class);
    }

    public function bisaKelolaPengguna(): bool
    {
        return $this->hasRole(Peran::SUPERADMIN);
    }

    public function bisaUbahIdentitas(): bool
    {
        return $this->hasRole(Peran::SUPERADMIN);
    }

    public function bisaKelola(): bool
    {
        return Peran::cocok($this, Peran::pengelola());
    }

    public function adalahWali(): bool
    {
        return $this->hasRole(Peran::WALI_KELAS) && ! $this->bisaKelola();
    }

    public function peranUtama(): string
    {
        if ($this->hasRole(Peran::SUPERADMIN)) {
            return Peran::SUPERADMIN;
        }

        if ($this->hasAnyRole([Peran::ADMIN, 'operator', 'kamad'])) {
            return Peran::ADMIN;
        }

        if ($this->hasRole(Peran::WALI_KELAS)) {
            return Peran::WALI_KELAS;
        }

        if ($this->hasRole(Peran::GURU)) {
            return Peran::GURU;
        }

        return Peran::ADMIN;
    }

    public function labelPeran(): string
    {
        return Peran::labels()[$this->peranUtama()] ?? $this->peranUtama();
    }

    /**
     * @return list<int>
     */
    public function rombelIdsAktif(): array
    {
        if (! $this->gtk_id) {
            return [];
        }

        return Rombel::query()
            ->where('gtk_id', $this->gtk_id)
            ->when(TahunAjaran::aktif(), fn ($query) => $query->where('tahun_ajaran_id', TahunAjaran::aktif()->id))
            ->pluck('id')
            ->all();
    }

    public function rombelsWali(): HasMany
    {
        return $this->hasMany(Rombel::class, 'gtk_id', 'gtk_id');
    }

    public function mengampu(Siswa $siswa): bool
    {
        if ($this->bisaKelola()) {
            return true;
        }

        $ids = $this->rombelIdsAktif();

        if ($ids === []) {
            return false;
        }

        return $siswa->rombels()
            ->whereIn('rombels.id', $ids)
            ->wherePivot('status', 'aktif')
            ->exists();
    }
}
