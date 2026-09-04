<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\Notifikasi;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotifikasiApiTest extends TestCase
{
    use RefreshDatabase;

    private function guruUser(): User
    {
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
        ]);
        $user->syncRoles([Peran::GURU]);

        return $user;
    }

    public function test_guru_receives_targeted_pengumuman_and_can_mark_read(): void
    {
        $guru = $this->guruUser();
        Notifikasi::query()->create([
            'judul' => 'Untuk semua guru',
            'isi' => 'Halo',
            'jenis' => Notifikasi::JENIS_PENGUMUMAN,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
            'is_active' => true,
            'published_at' => now()->subMinute(),
        ]);
        Notifikasi::query()->create([
            'judul' => 'Untuk siswa',
            'isi' => 'Rahasia',
            'jenis' => Notifikasi::JENIS_PENGUMUMAN,
            'audience' => Notifikasi::AUDIENCE_SEMUA_SISWA,
            'is_active' => true,
            'published_at' => now()->subMinute(),
        ]);

        Sanctum::actingAs($guru);

        $response = $this->getJson('/api/v1/notifikasi')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Untuk semua guru');

        $id = $response->json('data.0.id');

        $this->postJson("/api/v1/notifikasi/{$id}/read")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/notifikasi')
            ->assertOk()
            ->assertJsonPath('unread_count', 0)
            ->assertJsonPath('data.0.is_read', true);
    }

    public function test_jenis_filter_returns_pengumuman_only(): void
    {
        $guru = $this->guruUser();
        Notifikasi::query()->create([
            'judul' => 'Pengumuman',
            'isi' => 'A',
            'jenis' => Notifikasi::JENIS_PENGUMUMAN,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
            'is_active' => true,
            'published_at' => now(),
        ]);
        Notifikasi::query()->create([
            'judul' => 'Pengingat',
            'isi' => 'B',
            'jenis' => Notifikasi::JENIS_PENGINGAT,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
            'is_active' => true,
            'published_at' => now(),
        ]);

        Sanctum::actingAs($guru);

        $this->getJson('/api/v1/notifikasi?jenis=pengumuman')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Pengumuman');
    }
}
