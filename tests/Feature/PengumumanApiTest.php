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

class PengumumanApiTest extends TestCase
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

    public function test_pengumuman_api_requires_auth(): void
    {
        $this->getJson('/api/v1/pengumuman')->assertUnauthorized();
    }

    public function test_pengumuman_api_returns_published_items(): void
    {
        Notifikasi::query()->create([
            'judul' => 'Aktif',
            'isi' => 'Isi aktif',
            'jenis' => Notifikasi::JENIS_PENGUMUMAN,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);
        Notifikasi::query()->create([
            'judul' => 'Nonaktif',
            'isi' => 'Sembunyi',
            'jenis' => Notifikasi::JENIS_PENGUMUMAN,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
            'is_active' => false,
            'published_at' => now()->subHour(),
        ]);
        Notifikasi::query()->create([
            'judul' => 'Jadwal depan',
            'isi' => 'Belum tayang',
            'jenis' => Notifikasi::JENIS_PENGUMUMAN,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
            'is_active' => true,
            'published_at' => now()->addDay(),
        ]);

        Sanctum::actingAs($this->guruUser());

        $this->getJson('/api/v1/pengumuman')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Aktif')
            ->assertJsonPath('data.0.isi', 'Isi aktif');
    }
}
