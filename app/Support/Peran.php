<?php

namespace App\Support;

use App\Models\User;

class Peran
{
    public const SUPERADMIN = 'superadmin';

    public const ADMIN = 'admin';

    public const WALI_KELAS = 'wali_kelas';

    public const GURU = 'guru';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::SUPERADMIN => 'Super admin',
            self::ADMIN => 'Admin',
            self::WALI_KELAS => 'Wali kelas',
            self::GURU => 'Guru (Ta\'lim)',
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
        return [self::WALI_KELAS, self::GURU];
    }

    /**
     * Peran yang wajib terhubung ke GTK (akun Ta'lim / wali).
     *
     * @return list<string>
     */
    public static function butuhGtk(): array
    {
        return [self::WALI_KELAS, self::GURU];
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
        }

        return $user->hasAnyRole(array_unique($dibolehkan));
    }
}
