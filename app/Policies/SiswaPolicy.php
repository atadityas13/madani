<?php

namespace App\Policies;

use App\Models\Siswa;
use App\Models\User;

class SiswaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->bisaKelola() || $user->adalahWali();
    }

    public function view(User $user, Siswa $siswa): bool
    {
        return $user->mengampu($siswa);
    }

    public function create(User $user): bool
    {
        return $user->bisaKelola();
    }

    public function update(User $user, Siswa $siswa): bool
    {
        return $user->mengampu($siswa);
    }
}
