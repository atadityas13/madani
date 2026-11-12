<?php

namespace App\Support;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class TalimSession
{
    public const GURU_TOKEN = 'talim-guru';

    public static function idleMinutes(): int
    {
        $minutes = (int) config('services.talim.session_idle_minutes', 120);

        return max(1, $minutes);
    }

    public static function cutoff(): CarbonInterface
    {
        return now()->subMinutes(self::idleMinutes());
    }

    /**
     * Token guru dianggap hidup hanya selama aktivitas terakhir masih di dalam jendela idle.
     * Token selain talim-guru (misalnya siswa) tidak dibatasi di sini.
     */
    public static function isLive(PersonalAccessToken $token): bool
    {
        if ($token->name !== self::GURU_TOKEN) {
            return true;
        }

        $activity = $token->last_used_at ?? $token->created_at;
        if ($activity === null) {
            return false;
        }

        return $activity->gt(self::cutoff());
    }

    /**
     * Hapus token guru yang sudah idle. Mengembalikan true bila token dicabut.
     */
    public static function revokeIfStale(PersonalAccessToken $token): bool
    {
        if ($token->name !== self::GURU_TOKEN || self::isLive($token)) {
            return false;
        }

        $token->delete();

        return true;
    }

    /**
     * @param  Builder<PersonalAccessToken>  $query
     */
    public static function constrainLiveGuruTokens(Builder $query): void
    {
        $cutoff = self::cutoff();

        $query->where('name', self::GURU_TOKEN)
            ->where(function (Builder $query) use ($cutoff): void {
                $query->where('last_used_at', '>', $cutoff)
                    ->orWhere(function (Builder $query) use ($cutoff): void {
                        $query->whereNull('last_used_at')
                            ->where('created_at', '>', $cutoff);
                    });
            });
    }

    public static function pruneStaleGuruTokens(): int
    {
        $cutoff = self::cutoff();

        return PersonalAccessToken::query()
            ->where('name', self::GURU_TOKEN)
            ->where(function (Builder $query) use ($cutoff): void {
                $query->where(function (Builder $query) use ($cutoff): void {
                    $query->whereNotNull('last_used_at')
                        ->where('last_used_at', '<=', $cutoff);
                })->orWhere(function (Builder $query) use ($cutoff): void {
                    $query->whereNull('last_used_at')
                        ->where(function (Builder $query) use ($cutoff): void {
                            $query->whereNull('created_at')
                                ->orWhere('created_at', '<=', $cutoff);
                        });
                });
            })
            ->delete();
    }

    /**
     * Sesi web guru yang sudah melewati jendela idle tidak dihitung sebagai perangkat lain.
     * Sesi yang masih baru (misalnya double-request Chrome Custom Tabs) dibiarkan.
     */
    public static function forgetOtherWebSessions(User $user, string $exceptSessionId): int
    {
        if (config('session.driver') !== 'database') {
            return 0;
        }

        return DB::table(self::sessionTable())
            ->where('user_id', $user->id)
            ->where('id', '!=', $exceptSessionId)
            ->where('last_activity', '<=', self::cutoff()->getTimestamp())
            ->delete();
    }

    public static function pruneExpiredWebSessions(): int
    {
        if (config('session.driver') !== 'database') {
            return 0;
        }

        $lifetime = max(1, (int) config('session.lifetime', 120));

        return DB::table(self::sessionTable())
            ->where('last_activity', '<', now()->subMinutes($lifetime)->getTimestamp())
            ->delete();
    }

    private static function sessionTable(): string
    {
        return (string) config('session.table', 'sessions');
    }
}
