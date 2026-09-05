<?php

namespace Tests\Feature;

use App\Models\AppMaintenance;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate(Peran::SUPERADMIN);
        $user = User::factory()->create(['is_aktif' => true]);
        $user->syncRoles([Peran::SUPERADMIN]);

        return $user;
    }

    public function test_admin_can_enable_maintenance_with_custom_message(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('app-maintenance.store'), [
                'title' => 'Sedang dilakukan perbaikan pada server',
                'message' => 'Migrasi data Simpatisans ke Madani.',
                'is_active' => '1',
                'show_countdown' => '1',
                'ends_at' => now('Asia/Jakarta')->addDay()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('app-maintenance.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('app_maintenances', [
            'is_active' => true,
            'message' => 'Migrasi data Simpatisans ke Madani.',
            'show_countdown' => true,
        ]);
    }

    public function test_admin_requires_ends_at_when_countdown_enabled(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('app-maintenance.index'))
            ->post(route('app-maintenance.store'), [
                'title' => 'Perbaikan',
                'message' => 'Tunggu',
                'is_active' => '1',
                'show_countdown' => '1',
            ])
            ->assertRedirect(route('app-maintenance.index'))
            ->assertSessionHasErrors('ends_at');
    }

    public function test_admin_can_disable_maintenance(): void
    {
        $admin = $this->admin();
        AppMaintenance::query()->create([
            'is_active' => true,
            'title' => 'Perbaikan',
            'message' => 'Tunggu',
        ]);

        $this->actingAs($admin)
            ->post(route('app-maintenance.store'), [
                'title' => 'Perbaikan',
                'message' => 'Tunggu',
            ])
            ->assertRedirect(route('app-maintenance.index'));

        $this->assertDatabaseHas('app_maintenances', [
            'is_active' => false,
        ]);
    }
}
