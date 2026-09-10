<?php

namespace App\Support;

use App\Models\User;

class TunjanganAkses
{
    public static function bolehLoginWeb(User $user): bool
    {
        if ($user->hasAnyRole(Peran::aksesWeb())) {
            return true;
        }

        return $user->hasRole(Peran::GURU) && self::guruSertifikasi($user);
    }

    public static function guruSertifikasi(User $user): bool
    {
        $gtk = $user->gtk;

        return $gtk !== null
            && $gtk->isAktif()
            && filled($gtk->nrg);
    }

    public static function homeRoute(User $user): string
    {
        if ($user->hasRole(Peran::VENDOR) && ! $user->bisaKelola()) {
            return route('vendor.dashboard');
        }

        if ($user->hasRole(Peran::GURU) && ! $user->bisaKelola()) {
            return route('tunjangan.index');
        }

        return route('dashboard');
    }
}
