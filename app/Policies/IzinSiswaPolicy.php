<?php

namespace App\Policies;

use App\Models\IzinSiswa;
use App\Models\User;

class IzinSiswaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->bisaKelola() || $user->adalahWali();
    }

    public function view(User $user, IzinSiswa $izin): bool
    {
        if ($user->bisaKelola()) {
            return true;
        }

        $ids = $user->rombelIdsAktif();

        return $izin->rombel_id !== null && in_array((int) $izin->rombel_id, $ids, true);
    }
}
