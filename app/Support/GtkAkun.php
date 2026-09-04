<?php

namespace App\Support;

use App\Models\Gtk;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class GtkAkun
{
    public function buat(Gtk $gtk, ?string $password = null): User
    {
        if ($gtk->akun()->exists()) {
            throw ValidationException::withMessages([
                'gtk' => ['GTK ini sudah punya akun pengguna.'],
            ]);
        }

        $nip = trim((string) $gtk->nip);
        if ($nip === '') {
            throw ValidationException::withMessages([
                'nip' => ['NIP wajib diisi sebelum membuat akun Ta\'lim.'],
            ]);
        }

        if (User::query()->where('username', $nip)->exists()) {
            throw ValidationException::withMessages([
                'username' => ['Username (NIP) sudah dipakai akun lain.'],
            ]);
        }

        $password ??= $nip;
        if (strlen($password) < 8) {
            throw ValidationException::withMessages([
                'password' => ['Password awal minimal 8 karakter (biasanya sama dengan NIP).'],
            ]);
        }

        Role::findOrCreate(Peran::GURU);

        $user = User::query()->create([
            'name' => $gtk->nama,
            'username' => $nip,
            'email' => $this->emailUntuk($gtk, $nip),
            'password' => $password,
            'must_change_password' => true,
            'is_aktif' => true,
            'gtk_id' => $gtk->id,
        ]);

        $user->syncRoles([Peran::GURU]);

        return $user;
    }

    public function resetPassword(User $user, ?string $password = null): string
    {
        if (! $user->gtk_id) {
            throw ValidationException::withMessages([
                'user' => ['Akun ini tidak terhubung ke GTK.'],
            ]);
        }

        $password ??= (string) ($user->gtk?->nip ?: $user->username);
        if (strlen($password) < 8) {
            $password = Str::password(10);
        }

        $user->forceFill([
            'password' => $password,
            'must_change_password' => true,
        ])->save();

        $user->tokens()->where('name', 'talim-guru')->delete();

        return $password;
    }

    private function emailUntuk(Gtk $gtk, string $nip): string
    {
        $email = trim((string) $gtk->email);
        if ($email !== '' && ! User::query()->where('email', $email)->exists()) {
            return $email;
        }

        $candidate = $nip.'@gtk.madani.local';
        if (! User::query()->where('email', $candidate)->exists()) {
            return $candidate;
        }

        return $nip.'.'.Str::lower(Str::random(4)).'@gtk.madani.local';
    }
}
