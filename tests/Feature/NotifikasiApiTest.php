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

    public function test_lonceng_kanal_excludes_pengingat(): void
    {
        $guru = $this->guruUser();
        Notifikasi::query()->create([
            'judul' => 'Push lonceng',
            'isi' => 'A',
            'jenis' => Notifikasi::JENIS_NOTIFIKASI,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
            'is_active' => true,
            'published_at' => now(),
        ]);
        Notifikasi::query()->create([
            'judul' => 'Section saja',
            'isi' => 'B',
            'jenis' => Notifikasi::JENIS_PENGINGAT,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
            'is_active' => true,
            'published_at' => now(),
        ]);

        Sanctum::actingAs($guru);

        $this->getJson('/api/v1/notifikasi?kanal=lonceng')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Push lonceng')
            ->assertJsonPath('data.0.dismissible', true);
    }

    public function test_pengingat_periode_is_not_dismissible(): void
    {
        $guru = $this->guruUser();
        Notifikasi::query()->create([
            'judul' => 'Periode',
            'isi' => 'Wajib',
            'jenis' => Notifikasi::JENIS_PENGINGAT,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
            'use_periode' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(3),
            'is_active' => true,
            'published_at' => now(),
        ]);

        Sanctum::actingAs($guru);

        $this->getJson('/api/v1/notifikasi?jenis=pengingat')
            ->assertOk()
            ->assertJsonPath('data.0.use_periode', true)
            ->assertJsonPath('data.0.dismissible', false);
    }

    public function test_clear_hides_lonceng_items(): void
    {
        $guru = $this->guruUser();
        Notifikasi::query()->create([
            'judul' => 'Bersihkan saya',
            'isi' => 'A',
            'jenis' => Notifikasi::JENIS_PENGUMUMAN,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
            'is_active' => true,
            'published_at' => now(),
        ]);

        Sanctum::actingAs($guru);

        $this->postJson('/api/v1/notifikasi/clear?kanal=lonceng')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('cleared', 1);

        $this->getJson('/api/v1/notifikasi?kanal=lonceng')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('unread_count', 0);

        $this->getJson('/api/v1/notifikasi?jenis=pengumuman')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_read', true);
    }

    public function test_judul_isi_are_personalized_for_reader(): void
    {
        $guru = $this->guruUser();
        Notifikasi::query()->create([
            'judul' => 'Halo {{nama}}',
            'isi' => 'NIP Anda {{nip}}',
            'jenis' => Notifikasi::JENIS_NOTIFIKASI,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
            'audio_url' => 'https://example.com/a.mp3',
            'sound_key' => Notifikasi::SOUND_ALARM,
            'priority' => Notifikasi::PRIORITY_HIGH,
            'is_active' => true,
            'published_at' => now(),
            'sent_at' => now(),
        ]);

        Sanctum::actingAs($guru);

        $this->getJson('/api/v1/notifikasi?kanal=lonceng')
            ->assertOk()
            ->assertJsonPath('data.0.judul', 'Halo Budi')
            ->assertJsonPath('data.0.isi', 'NIP Anda 198001012005011001')
            ->assertJsonPath('data.0.audio_url', 'https://example.com/a.mp3')
            ->assertJsonPath('data.0.sound_key', 'alarm')
            ->assertJsonPath('data.0.priority', 'high');
    }

    public function test_future_scheduled_notifikasi_hidden_until_due(): void
    {
        $guru = $this->guruUser();
        Notifikasi::query()->create([
            'judul' => 'Nanti',
            'isi' => 'A',
            'jenis' => Notifikasi::JENIS_NOTIFIKASI,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
            'is_active' => true,
            'published_at' => now(),
            'scheduled_at' => now()->addHour(),
            'sent_at' => null,
        ]);

        Sanctum::actingAs($guru);

        $this->getJson('/api/v1/notifikasi?kanal=lonceng')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
