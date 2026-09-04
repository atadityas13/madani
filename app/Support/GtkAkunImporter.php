<?php

namespace App\Support;

use App\Models\Gtk;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class GtkAkunImporter
{
    public function __construct(private GtkAkun $gtkAkun) {}

    /**
     * Import akun guru Simpatisans → User Madani (by username/NIP → gtk.nip).
     * Password hash bcrypt disalin apa adanya (bukan password = NIP baru).
     *
     * @param  array<string, mixed>  $row  baris users Simpatisans
     * @return array{action: string, user?: User, reason?: string}
     */
    public function importFromSimpatisansUser(array $row, bool $overwrite = false): array
    {
        $username = trim((string) ($row['username'] ?? ''));
        if ($username === '') {
            return ['action' => 'skipped', 'reason' => 'no_username'];
        }

        $role = (string) ($row['role'] ?? 'guru');
        if ($role !== 'guru') {
            return ['action' => 'skipped', 'reason' => 'not_guru'];
        }

        $gtk = Gtk::query()->where('nip', $username)->first();
        if (! $gtk) {
            return ['action' => 'skipped', 'reason' => 'gtk_missing'];
        }

        $passwordHash = (string) ($row['password'] ?? '');
        if ($passwordHash === '' || ! Hash::isHashed($passwordHash)) {
            return ['action' => 'skipped', 'reason' => 'password_invalid'];
        }

        $mustChange = filled($row['plain_password'] ?? null);
        $isAktif = array_key_exists('is_active', $row)
            ? (bool) $row['is_active']
            : true;
        $name = trim((string) ($row['nama_lengkap'] ?? $gtk->nama));
        if ($name === '') {
            $name = $gtk->nama;
        }

        Role::findOrCreate(Peran::GURU);

        $existing = User::query()->where('username', $username)->first();

        if ($existing) {
            if ($existing->gtk_id && (int) $existing->gtk_id !== (int) $gtk->id) {
                return ['action' => 'skipped', 'reason' => 'username_conflict'];
            }

            if (! $overwrite) {
                return ['action' => 'unchanged', 'user' => $existing];
            }

            $existing->forceFill([
                'name' => $name !== '' ? $name : $existing->name,
                'gtk_id' => $gtk->id,
                'is_aktif' => $isAktif,
                'must_change_password' => $mustChange,
            ])->save();

            $this->setPasswordHash($existing, $passwordHash);

            if (! $existing->hasRole(Peran::GURU) && ! $existing->hasRole(Peran::WALI_KELAS)) {
                $existing->syncRoles([Peran::GURU]);
            }

            return ['action' => 'updated', 'user' => $existing->fresh()];
        }

        if ($gtk->akun()->exists()) {
            return ['action' => 'skipped', 'reason' => 'gtk_has_other_user'];
        }

        $user = User::query()->create([
            'name' => $name,
            'username' => $username,
            'email' => $this->gtkAkun->emailUntuk($gtk, $username),
            'password' => 'temporary-placeholder-password',
            'must_change_password' => $mustChange,
            'is_aktif' => $isAktif,
            'gtk_id' => $gtk->id,
        ]);

        $this->setPasswordHash($user, $passwordHash);
        $user->syncRoles([Peran::GURU]);

        return ['action' => 'created', 'user' => $user->fresh()];
    }

    private function setPasswordHash(User $user, string $hash): void
    {
        DB::table('users')->where('id', $user->id)->update([
            'password' => $hash,
            'updated_at' => now(),
        ]);
        $user->refresh();
    }
}
