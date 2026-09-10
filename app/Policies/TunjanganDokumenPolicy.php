<?php

namespace App\Policies;

use App\Models\Gtk;
use App\Models\TunjanganDokumen;
use App\Models\User;
use App\Support\Peran;

class TunjanganDokumenPolicy
{
    public function aksesModul(User $user): bool
    {
        if (Peran::cocok($user, Peran::pengelola())) {
            return true;
        }

        return $user->hasRole(Peran::GURU) && $this->gtkSertifikasi($user) !== null;
    }

    public function kelolaSemua(User $user): bool
    {
        return Peran::cocok($user, Peran::pengelola());
    }

    public function viewGtk(User $user, Gtk $gtk): bool
    {
        if (! $this->aksesModul($user)) {
            return false;
        }

        if (! $this->adalahGtkSertifikasi($gtk)) {
            return false;
        }

        if ($this->kelolaSemua($user)) {
            return true;
        }

        return (int) $user->gtk_id === (int) $gtk->id;
    }

    public function upload(User $user, Gtk $gtk): bool
    {
        return $this->viewGtk($user, $gtk);
    }

    public function hapus(User $user, TunjanganDokumen $dokumen): bool
    {
        $gtk = $dokumen->gtk;

        return $gtk !== null && $this->upload($user, $gtk);
    }

    public function uploadZip(User $user): bool
    {
        return $this->kelolaSemua($user);
    }

    public function unduhMassal(User $user): bool
    {
        return $this->kelolaSemua($user);
    }

    public function gtkSertifikasi(User $user): ?Gtk
    {
        $gtk = $user->gtk;

        if ($gtk === null || ! $this->adalahGtkSertifikasi($gtk)) {
            return null;
        }

        return $gtk;
    }

    public function adalahGtkSertifikasi(Gtk $gtk): bool
    {
        return $gtk->isAktif() && filled($gtk->nrg);
    }
}
