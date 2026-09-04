<?php

namespace App\Models;

use App\Support\Peran;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable([
    'judul',
    'isi',
    'jenis',
    'audience',
    'audience_ids',
    'starts_at',
    'ends_at',
    'is_active',
    'published_at',
    'created_by',
])]
class Notifikasi extends Model
{
    public const JENIS_PENGUMUMAN = 'pengumuman';

    public const JENIS_PENGINGAT = 'pengingat';

    public const JENIS_PERIODE = 'periode';

    public const AUDIENCE_SEMUA_GURU = 'semua_guru';

    public const AUDIENCE_SEMUA_SISWA = 'semua_siswa';

    public const AUDIENCE_GTK = 'gtk';

    public const AUDIENCE_SISWA = 'siswa';

    public const AUDIENCE_ROMBEL = 'rombel';

    protected function casts(): array
    {
        return [
            'audience_ids' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(NotifikasiRead::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $q): void {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    /**
     * @return array<string, string>
     */
    public static function jenisOptions(): array
    {
        return [
            self::JENIS_PENGUMUMAN => 'Pengumuman',
            self::JENIS_PENGINGAT => 'Pengingat',
            self::JENIS_PERIODE => 'Periode pendataan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function audienceOptions(): array
    {
        return [
            self::AUDIENCE_SEMUA_GURU => 'Semua guru',
            self::AUDIENCE_SEMUA_SISWA => 'Semua siswa',
            self::AUDIENCE_GTK => 'Guru tertentu',
            self::AUDIENCE_SISWA => 'Siswa tertentu',
            self::AUDIENCE_ROMBEL => 'Rombel',
        ];
    }

    public function isVisibleToGuru(User $user): bool
    {
        return match ($this->audience) {
            self::AUDIENCE_SEMUA_GURU => true,
            self::AUDIENCE_GTK => $user->gtk_id !== null
                && in_array((int) $user->gtk_id, array_map('intval', $this->audience_ids ?? []), true),
            default => false,
        };
    }

    public function isVisibleToSiswa(Siswa $siswa): bool
    {
        return match ($this->audience) {
            self::AUDIENCE_SEMUA_SISWA => true,
            self::AUDIENCE_SISWA => in_array((string) $siswa->id, array_map('strval', $this->audience_ids ?? []), true),
            self::AUDIENCE_ROMBEL => $this->siswaInAudienceRombels($siswa),
            default => false,
        };
    }

    private function siswaInAudienceRombels(Siswa $siswa): bool
    {
        $rombelIds = array_map('intval', $this->audience_ids ?? []);
        if ($rombelIds === []) {
            return false;
        }

        return $siswa->rombels()
            ->whereIn('rombels.id', $rombelIds)
            ->wherePivot('status', 'aktif')
            ->exists();
    }

    /**
     * @return Collection<int, string>
     */
    public function resolveFcmTokens(): Collection
    {
        return match ($this->audience) {
            self::AUDIENCE_SEMUA_GURU => DeviceToken::query()
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', User::role(Peran::GURU)->pluck('id')->map(fn ($id) => (string) $id))
                ->pluck('fcm_token'),
            self::AUDIENCE_SEMUA_SISWA => DeviceToken::query()
                ->where('tokenable_type', Siswa::class)
                ->pluck('fcm_token'),
            self::AUDIENCE_GTK => DeviceToken::query()
                ->where('tokenable_type', User::class)
                ->whereIn(
                    'tokenable_id',
                    User::query()
                        ->whereIn('gtk_id', array_map('intval', $this->audience_ids ?? []))
                        ->pluck('id')
                        ->map(fn ($id) => (string) $id),
                )
                ->pluck('fcm_token'),
            self::AUDIENCE_SISWA => DeviceToken::query()
                ->where('tokenable_type', Siswa::class)
                ->whereIn('tokenable_id', array_map('strval', $this->audience_ids ?? []))
                ->pluck('fcm_token'),
            self::AUDIENCE_ROMBEL => DeviceToken::query()
                ->where('tokenable_type', Siswa::class)
                ->whereIn(
                    'tokenable_id',
                    Siswa::query()
                        ->whereHas('rombels', function (Builder $q): void {
                            $q->whereIn('rombels.id', array_map('intval', $this->audience_ids ?? []))
                                ->wherePivot('status', 'aktif');
                        })
                        ->pluck('id')
                        ->map(fn ($id) => (string) $id),
                )
                ->pluck('fcm_token'),
            default => collect(),
        };
    }
}
