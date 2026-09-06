<?php

namespace Tests\Feature;

use App\Models\PeriodePendataan;
use App\Models\Siswa;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PeriodePendataanTest extends TestCase
{
    use RefreshDatabase;

    public function test_siswa_sees_inactive_periode_when_none_configured(): void
    {
        $this->seed();
        $token = $this->siswaToken();

        $this->withToken($token)
            ->getJson('/api/v1/siswa/periode-pendataan')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.active', false);
    }

    public function test_siswa_sees_active_periode_with_countdown(): void
    {
        $this->seed();
        PeriodePendataan::query()->create([
            'judul' => 'Lengkapi biodata',
            'pesan' => 'Segera lengkapi data.',
            'is_active' => true,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDays(2),
        ]);
        $token = $this->siswaToken();

        $this->withToken($token)
            ->getJson('/api/v1/siswa/periode-pendataan')
            ->assertOk()
            ->assertJsonPath('data.active', true)
            ->assertJsonPath('data.judul', 'Lengkapi biodata')
            ->assertJsonPath('data.pesan', 'Segera lengkapi data.')
            ->assertJsonStructure(['data' => ['starts_at', 'ends_at', 'seconds_remaining']]);
    }

    public function test_expired_periode_returns_inactive(): void
    {
        $this->seed();
        PeriodePendataan::query()->create([
            'judul' => 'Sudah lewat',
            'pesan' => null,
            'is_active' => true,
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->subDay(),
        ]);
        $token = $this->siswaToken();

        $this->withToken($token)
            ->getJson('/api/v1/siswa/periode-pendataan')
            ->assertOk()
            ->assertJsonPath('data.active', false);
    }

    public function test_admin_can_update_periode_via_web(): void
    {
        $this->seed();
        Role::findOrCreate(Peran::SUPERADMIN);
        $admin = User::factory()->create(['is_aktif' => true]);
        $admin->syncRoles([Peran::SUPERADMIN]);

        $this->actingAs($admin)
            ->put(route('siswa.periode-pendataan.update'), [
                'judul' => 'Pendataan biodata',
                'pesan' => 'Mohon dilengkapi.',
                'is_active' => '1',
                'starts_at' => now()->format('Y-m-d\\TH:i'),
                'ends_at' => now()->addWeek()->format('Y-m-d\\TH:i'),
            ])
            ->assertRedirect(route('siswa.index'));

        $this->assertDatabaseHas('periode_pendataans', [
            'judul' => 'Pendataan biodata',
            'is_active' => 1,
        ]);
    }

    private function siswaToken(): string
    {
        $siswa = Siswa::query()->create([
            'nama' => 'Siswa Periode',
            'nisn' => '0104257591',
            'status_keaktifan' => 'aktif',
        ]);
        $siswa->gantiPassword('sandibaru1');

        $login = $this->postJson('/api/v1/siswa/login', [
            'nisn' => '0104257591',
            'password' => 'sandibaru1',
        ])->assertOk();

        return (string) $login->json('token');
    }
}
