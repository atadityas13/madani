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
            ->put(route('manajemen.periode-pendataan.update'), [
                'judul' => 'Pendataan biodata',
                'pesan' => 'Mohon dilengkapi.',
                'is_active' => '1',
                'starts_at' => now()->format('Y-m-d\\TH:i'),
                'ends_at' => now()->addWeek()->format('Y-m-d\\TH:i'),
            ])
            ->assertRedirect(route('manajemen.periode-pendataan.index'));

        $this->assertDatabaseHas('periode_pendataans', [
            'judul' => 'Pendataan biodata',
            'is_active' => 1,
        ]);
    }

    public function test_admin_bisa_membuka_halaman_periode_pendataan(): void
    {
        $this->seed();
        Role::findOrCreate(Peran::SUPERADMIN);
        $admin = User::factory()->create(['is_aktif' => true]);
        $admin->syncRoles([Peran::SUPERADMIN]);

        $this->actingAs($admin)
            ->get(route('manajemen.periode-pendataan.index'))
            ->assertOk()
            ->assertSee('Periode pendataan', false)
            ->assertSee('Aktifkan periode pendataan', false);
    }

    public function test_siswa_cannot_edit_when_periode_closed(): void
    {
        $this->seed();
        PeriodePendataan::query()->create([
            'judul' => 'Ditutup',
            'pesan' => null,
            'is_active' => false,
            'starts_at' => null,
            'ends_at' => null,
        ]);
        $token = $this->siswaToken();

        $this->withToken($token)
            ->getJson('/api/v1/siswa/me')
            ->assertOk()
            ->assertJsonPath('data.pernyataan.data_terkunci', true)
            ->assertJsonPath('data.pernyataan.periode_terbuka', false)
            ->assertJsonPath('data.pernyataan.alasan_kunci', 'periode');

        $this->withToken($token)
            ->putJson('/api/v1/siswa/data-siswa', ['hobi' => 'Olahraga'])
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_siswa_can_edit_when_periode_open(): void
    {
        $this->seed();
        PeriodePendataan::query()->create([
            'judul' => 'Dibuka',
            'pesan' => null,
            'is_active' => true,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDays(2),
        ]);
        $token = $this->siswaToken();

        $this->withToken($token)
            ->getJson('/api/v1/siswa/me')
            ->assertOk()
            ->assertJsonPath('data.pernyataan.data_terkunci', false)
            ->assertJsonPath('data.pernyataan.periode_terbuka', true)
            ->assertJsonPath('data.pernyataan.alasan_kunci', null);
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
