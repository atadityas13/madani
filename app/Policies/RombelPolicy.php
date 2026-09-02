<?php

namespace App\Policies;

use App\Models\Rombel;
use App\Models\User;

class RombelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->bisaKelola() || $user->adalahWali();
    }

    public function view(User $user, Rombel $rombel): bool
    {
        if ($user->bisaKelola()) {
            return true;
        }

        return $user->adalahWali() && (int) $user->gtk_id === (int) $rombel->gtk_id;
    }

    public function create(User $user): bool
    {
        return $user->bisaKelola();
    }

    public function update(User $user, Rombel $rombel): bool
    {
        return $user->bisaKelola();
    }

    public function delete(User $user, Rombel $rombel): bool
    {
        return $user->bisaKelola();
    }
}
