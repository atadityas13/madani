<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WebLoginAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_login_via_web(): void
    {
        $this->seed();

        $this->post(route('login'), [
            'login' => 'admin',
            'password' => 'madani-admin',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_admin_can_login_via_web(): void
    {
        Role::findOrCreate(Peran::ADMIN);

        $user = User::factory()->create([
            'username' => 'adminops',
            'password' => 'password123',
            'is_aktif' => true,
        ]);
        $user->syncRoles([Peran::ADMIN]);

        $this->post(route('login'), [
            'login' => 'adminops',
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_guru_cannot_login_via_web(): void
    {
        Role::findOrCreate(Peran::GURU);

        $gtk = Gtk::query()->create([
            'nama' => 'Budi Guru',
            'nip' => '198001012005011001',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $user = User::factory()->create([
            'username' => $gtk->nip,
            'password' => 'password123',
            'is_aktif' => true,
            'gtk_id' => $gtk->id,
        ]);
        $user->syncRoles([Peran::GURU]);

        $this->from(route('login'))
            ->post(route('login'), [
                'login' => $gtk->nip,
                'password' => 'password123',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_guru_tersertifikasi_bisa_login_ke_tunjangan(): void
    {
        Role::findOrCreate(Peran::GURU);

        $gtk = Gtk::query()->create([
            'nama' => 'Siti Sertifikasi',
            'nip' => '198101012005012002',
            'nrg' => 'NRG-123',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $user = User::factory()->create([
            'username' => $gtk->nip,
            'password' => 'password123',
            'is_aktif' => true,
            'gtk_id' => $gtk->id,
        ]);
        $user->syncRoles([Peran::GURU]);

        $this->post(route('login'), [
            'login' => $gtk->nip,
            'password' => 'password123',
        ])->assertRedirect(route('tunjangan.index'));

        $this->assertAuthenticatedAs($user);
    }
}
