<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama', 'tanggal_mulai', 'tanggal_selesai', 'is_aktif', 'status'])]
class TahunAjaran extends Model
{
    public const STATUS_AKTIF = 'aktif';

    public const STATUS_BELUM_AKTIF = 'belum_aktif';

    public const STATUS_ARSIP = 'arsip';

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'is_aktif' => 'boolean',
        ];
    }

    public function rombels(): HasMany
    {
        return $this->hasMany(Rombel::class);
    }

    public function periodiks(): HasMany
    {
        return $this->hasMany(SiswaPeriodik::class);
    }

    public function label(): string
    {
        return (string) $this->nama;
    }

    public function labelStatus(): string
    {
        return match ($this->status) {
            self::STATUS_AKTIF => 'Aktif',
            self::STATUS_ARSIP => 'Arsip',
            default => 'Belum Aktif',
        };
    }

    public function adalahAktif(): bool
    {
        return $this->status === self::STATUS_AKTIF || $this->is_aktif;
    }

    public function punyaData(): bool
    {
        return $this->rombels()->exists() || $this->periodiks()->exists();
    }

    public function bisaDihapus(): bool
    {
        return ! $this->adalahAktif() && ! $this->punyaData();
    }

    public static function aktif(): ?self
    {
        return static::query()
            ->where(function ($query) {
                $query->where('status', self::STATUS_AKTIF)
                    ->orWhere('is_aktif', true);
            })
            ->first();
    }
}
