<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorJob;
use App\Support\Peran;

class VendorJobPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->bisaKelola() || $user->hasRole(Peran::VENDOR);
    }

    public function view(User $user, VendorJob $vendorJob): bool
    {
        if ($user->bisaKelola()) {
            return true;
        }

        return $user->hasRole(Peran::VENDOR) && (int) $vendorJob->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->bisaKelola();
    }

    public function update(User $user, VendorJob $vendorJob): bool
    {
        return $user->bisaKelola();
    }

    public function delete(User $user, VendorJob $vendorJob): bool
    {
        return $user->bisaKelola();
    }

    public function uploadFoto(User $user, VendorJob $vendorJob): bool
    {
        return $this->view($user, $vendorJob) && ($user->bisaKelola() || $vendorJob->isAktif());
    }

    public function printKartu(User $user, VendorJob $vendorJob): bool
    {
        return $this->view($user, $vendorJob);
    }
}
