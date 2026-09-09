<?php

namespace Database\Seeders;

use App\Models\Madrasah;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['superadmin', 'admin', 'wali_kelas', 'operator', 'kamad', 'guru', 'vendor'] as $role) {
            Role::findOrCreate($role);
        }

        // Idempotent: production may already have this email under another username (or vice versa).
        $admin = User::query()->where('username', 'admin')->first()
            ?? User::query()->where('email', 'admin@mtsn11majalengka.sch.id')->first();

        if ($admin === null) {
            $admin = User::query()->create([
                'username' => 'admin',
                'name' => 'Operator MADANI',
                'email' => 'admin@mtsn11majalengka.sch.id',
                'password' => Hash::make('madani-admin'),
                'is_aktif' => true,
            ]);
        } else {
            $admin->forceFill([
                'is_aktif' => true,
            ])->save();
        }

        $admin->syncRoles(['superadmin']);

        Madrasah::saatIni();

        TahunAjaran::query()->updateOrCreate(
            ['nama' => '2026/2027'],
            [
                'tanggal_mulai' => '2026-07-13',
                'tanggal_selesai' => '2027-06-12',
                'is_aktif' => true,
                'status' => TahunAjaran::STATUS_AKTIF,
            ]
        );

        $this->call(AppMenuSeeder::class);
    }
}
