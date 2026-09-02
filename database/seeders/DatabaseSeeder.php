<?php

namespace Database\Seeders;

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

        foreach (['superadmin', 'admin', 'wali_kelas', 'operator', 'kamad', 'guru'] as $role) {
            Role::findOrCreate($role);
        }

        $admin = User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Operator MADANI',
                'email' => 'admin@mtsn11majalengka.sch.id',
                'password' => Hash::make('madani-admin'),
                'is_aktif' => true,
            ]
        );
        $admin->syncRoles(['superadmin']);

        TahunAjaran::query()->updateOrCreate(
            ['nama' => '2026/2027', 'semester' => 'ganjil'],
            [
                'tanggal_mulai' => '2026-07-13',
                'tanggal_selesai' => '2026-12-19',
                'is_aktif' => true,
            ]
        );
    }
}
