<?php

namespace Tests\Feature;

use App\Models\AppMaintenance;
use App\Models\Gtk;
use App\Models\Siswa;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppStatusApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_status_reports_inactive_maintenance_by_default(): void
    {
        $this->getJson('/api/v1/app-status')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.maintenance', false)
            ->assertJsonPath('data.title', null);
    }

    public function test_app_status_reports_active_maintenance_message(): void
    {
        AppMaintenance::query()->create([
            'is_active' => true,
            'title' => 'Sedang dilakukan perbaikan pada server',
            'message' => 'Migrasi sedang berjalan.',
            'show_countdown' => true,
            'ends_at' => now('Asia/Jakarta')->addDay(),
        ]);

        $this->getJson('/api/v1/app-status')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.maintenance', true)
            ->assertJsonPath('data.title', 'Sedang dilakukan perbaikan pada server')
            ->assertJsonPath('data.message', 'Migrasi sedang berjalan.')
            ->assertJsonPath('data.show_countdown', true)
            ->assertJsonStructure(['data' => ['ends_at']]);
    }

    public function test_app_status_hides_countdown_when_disabled(): void
    {
        AppMaintenance::query()->create([
            'is_active' => true,
            'title' => 'Maintenance',
            'message' => 'Tunggu',
            'show_countdown' => false,
            'ends_at' => now('Asia/Jakarta')->addHours(2),
        ]);

        $this->getJson('/api/v1/app-status')
            ->assertOk()
            ->assertJsonPath('data.show_countdown', false)
            ->assertJsonPath('data.ends_at', null);
    }

    public function test_guru_login_returns_503_during_maintenance(): void
    {
        AppMaintenance::query()->create([
            'is_active' => true,
            'title' => 'Maintenance',
            'message' => 'Server sedang diperbaiki.',
        ]);

        Role::findOrCreate(Peran::GURU);
        $gtk = Gtk::query()->create([
            'nama' => 'Budi',
            'nip' => '198001012005011001',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);
        $user = User::factory()->create([
            'username' => '198001012005011001',
            'gtk_id' => $gtk->id,
            'is_aktif' => true,
            'password' => Hash::make('password123'),
        ]);
        $user->syncRoles([Peran::GURU]);

        $this->postJson('/api/v1/guru/login', [
            'username' => '198001012005011001',
            'password' => 'password123',
        ])
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('maintenance', true);
    }

    public function test_siswa_login_returns_503_during_maintenance(): void
    {
        AppMaintenance::query()->create([
            'is_active' => true,
            'title' => 'Maintenance',
            'message' => 'Server sedang diperbaiki.',
        ]);

        Siswa::query()->create([
            'nama' => 'Siswa Tes',
            'nisn' => '1234567890',
            'nik' => '3210010101120001',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-09-02',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif',
            'password' => Hash::make('password123'),
        ]);

        $this->postJson('/api/v1/siswa/login', [
            'nisn' => '1234567890',
            'password' => 'password123',
        ])
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('maintenance', true);
    }
}
