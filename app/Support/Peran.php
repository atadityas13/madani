<?php

namespace App\Support;

use App\Models\User;

class Peran
{
    public const SUPERADMIN = 'superadmin';

    public const ADMIN = 'admin';

    public const WALI_KELAS = 'wali_kelas';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::SUPERADMIN => 'Super admin',
            self::ADMIN => 'Admin',
            self::WALI_KELAS => 'Wali kelas',
        ];
    }

    /**
     * @return list<string>
     */
    public static function pengelola(): array
    {
        return [self::SUPERADMIN, self::ADMIN, 'operator', 'kamad'];
    }

    /**
     * @return list<string>
     */
    public static function wali(): array
    {
        return [self::WALI_KELAS, 'guru'];
    }

    /**
     * @param  list<string>  $roles
     */
    public static function cocok(?User $user, array $roles): bool
    {
        if (! $user) {
            return false;
        }

        $dibolehkan = [];

        foreach ($roles as $role) {
            $dibolehkan[] = $role;

            if ($role === self::ADMIN) {
                array_push($dibolehkan, 'operator', 'kamad');
            }

            if ($role === self::WALI_KELAS) {
                $dibolehkan[] = 'guru';
            }
        }

        return $user->hasAnyRole(array_unique($dibolehkan));
    }
}
