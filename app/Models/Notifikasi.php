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
    'gambar_url',
    'link',
    'audio_url',
    'sound_key',
    'priority',
    'jenis',
    'audience',
    'audience_ids',
    'use_periode',
    'starts_at',
    'ends_at',
    'is_active',
    'published_at',
    'scheduled_at',
    'sent_at',
    'created_by',
])]
class Notifikasi extends Model
{
    public const JENIS_NOTIFIKASI = 'notifikasi';

    public const JENIS_PENGUMUMAN = 'pengumuman';

    public const JENIS_PENGINGAT = 'pengingat';

    public const AUDIENCE_SEMUA_GURU = 'semua_guru';

    public const AUDIENCE_SEMUA_SISWA = 'semua_siswa';

    public const AUDIENCE_GTK = 'gtk';

    public const AUDIENCE_SISWA = 'siswa';

    public const AUDIENCE_ROMBEL = 'rombel';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_HIGH = 'high';

    public const SOUND_DEFAULT = 'default';

    public const SOUND_ALARM = 'alarm';

    /** @deprecated Diganti SOUND_ALARM; tetap dikenali untuk data lama. */
    public const SOUND_SOFT = 'soft';

    /** @deprecated Diganti SOUND_ALARM; tetap dikenali untuk data lama. */
    public const SOUND_URGENT = 'urgent';

    protected function casts(): array
    {
        return [
            'audience_ids' => 'array',
            'use_periode' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
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
            })
            ->where(function (Builder $q): void {
                $q->whereNull('scheduled_at')
                    ->orWhereNotNull('sent_at')
                    ->orWhere('scheduled_at', '<=', now());
            });
    }

    public function scopeDueForDispatch(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereNull('sent_at')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->where(function (Builder $q): void {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function appearsInLonceng(): bool
    {
        return in_array($this->jenis, [self::JENIS_NOTIFIKASI, self::JENIS_PENGUMUMAN], true);
    }

    public function isDismissible(): bool
    {
        if ($this->jenis !== self::JENIS_PENGINGAT) {
            return true;
        }

        return ! $this->use_periode;
    }

    public function androidChannelId(): string
    {
        return match ($this->sound_key) {
            self::SOUND_ALARM, self::SOUND_URGENT => 'madani_push_alarm',
            default => 'madani_push_default',
        };
    }

    public function usesAlarmChannel(): bool
    {
        return in_array($this->sound_key, [self::SOUND_ALARM, self::SOUND_URGENT], true);
    }

    /**
     * @return array<string, string>
     */
    public static function jenisOptions(): array
    {
        return [
            self::JENIS_NOTIFIKASI => 'Notifikasi',
            self::JENIS_PENGUMUMAN => 'Pengumuman',
            self::JENIS_PENGINGAT => 'Pengingat',
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

    /**
     * @return array<string, string>
     */
    public static function soundOptions(): array
    {
        return [
            self::SOUND_DEFAULT => 'Default',
            self::SOUND_ALARM => 'Alarm (bunyi meski hening)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function priorityOptions(): array
    {
        return [
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'Tinggi',
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
     * @return Collection<int, User|Siswa>
     */
    public function resolveRecipients(): Collection
    {
        return match ($this->audience) {
            self::AUDIENCE_SEMUA_GURU => User::role(Peran::GURU)->with('gtk')->get(),
            self::AUDIENCE_SEMUA_SISWA => Siswa::query()
                ->where('status_keaktifan', '!=', 'nonaktif')
                ->get(),
            self::AUDIENCE_GTK => User::query()
                ->with('gtk')
                ->whereIn('gtk_id', array_map('intval', $this->audience_ids ?? []))
                ->get(),
            self::AUDIENCE_SISWA => Siswa::query()
                ->whereIn('id', array_map('strval', $this->audience_ids ?? []))
                ->get(),
            self::AUDIENCE_ROMBEL => Siswa::query()
                ->whereHas('rombels', function (Builder $q): void {
                    $q->whereIn('rombels.id', array_map('intval', $this->audience_ids ?? []))
                        ->wherePivot('status', 'aktif');
                })
                ->get(),
            default => collect(),
        };
    }

    /**
     * @return Collection<int, string>
     */
    public function resolveFcmTokens(): Collection
    {
        return $this->resolveRecipients()
            ->flatMap(function (User|Siswa $recipient) {
                return DeviceToken::query()
                    ->where('tokenable_type', $recipient::class)
                    ->where('tokenable_id', (string) $recipient->getKey())
                    ->pluck('fcm_token');
            })
            ->filter()
            ->unique()
            ->values();
    }
}
