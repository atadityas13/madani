<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\JurnalPembelajaran;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuruJurnalApiTest extends TestCase
{
    use RefreshDatabase;

    private function buatAkunGuru(): User
    {
        Role::findOrCreate(Peran::GURU);

        $gtk = Gtk::query()->create([
            'nama' => 'Budi Santoso',
            'nip' => '198001012005011001',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $user = User::factory()->create([
            'name' => $gtk->nama,
            'username' => $gtk->nip,
            'password' => 'password123',
            'is_aktif' => true,
            'gtk_id' => $gtk->id,
        ]);
        $user->syncRoles([Peran::GURU]);

        return $user->fresh()->load('gtk');
    }

    public function test_guru_can_crud_jurnal_with_simpatisans_shaped_payload(): void
    {
        config(['jurnal.writes_enabled' => true]);
        $user = $this->buatAkunGuru();
        Sanctum::actingAs($user);

        $create = $this->postJson('/api/v1/guru/jurnal', [
            'kelas_id' => 12,
            'nama_kelas' => '9A',
            'mapel_id' => 3,
            'nama_mapel' => 'Matematika',
            'tanggal' => '2026-09-01',
            'jam_list' => [1, 2],
            'jadwal_ids' => [100, 101],
            'materi_pokok' => 'Aljabar dasar',
            'ketercapaian' => 'tercapai',
            'penugasan_siswa' => 'Latihan 1',
            'catatan_guru' => null,
            'semester_tipe' => 'Ganjil',
            'semester_nama_tahun' => '2025/2026',
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kelas_id', 12)
            ->assertJsonPath('data.jam_list.0', 1);

        $id = (int) $create->json('data.id');

        $this->getJson('/api/v1/guru/jurnal')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.kelas_id', 12)
            ->assertJsonPath('data.0.jumlah_entri', 1);

        $this->getJson('/api/v1/guru/jurnal/12')
            ->assertOk()
            ->assertJsonPath('data.0.materi_pokok', 'Aljabar dasar')
            ->assertJsonPath('mapel.0.nama', 'Matematika');

        $this->getJson('/api/v1/guru/jurnal/entries-by-tanggal?tanggal=2026-09-01')
            ->assertOk()
            ->assertJsonPath('data.0.id', $id);

        $this->putJson("/api/v1/guru/jurnal/{$id}", [
            'kelas_id' => 12,
            'nama_kelas' => '9A',
            'mapel_id' => 3,
            'nama_mapel' => 'Matematika',
            'tanggal' => '2026-09-01',
            'jam_list' => [1, 2],
            'jadwal_ids' => [100, 101],
            'materi_pokok' => 'Aljabar lanjutan',
            'ketercapaian' => 'belum',
        ])
            ->assertOk()
            ->assertJsonPath('data.materi_pokok', 'Aljabar lanjutan')
            ->assertJsonPath('data.ketercapaian', 'belum');

        $this->deleteJson("/api/v1/guru/jurnal/{$id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('jurnal_pembelajarans', ['id' => $id]);
    }

    public function test_writes_blocked_when_flag_disabled(): void
    {
        config(['jurnal.writes_enabled' => false]);
        $user = $this->buatAkunGuru();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/guru/jurnal', [
            'kelas_id' => 1,
            'mapel_id' => 1,
            'tanggal' => '2026-09-01',
            'materi_pokok' => 'X',
            'ketercapaian' => 'tercapai',
        ])->assertStatus(503)
            ->assertJsonPath('success', false);

        $this->getJson('/api/v1/guru/jurnal')->assertOk();
    }

    public function test_guru_cannot_access_other_guru_jurnal(): void
    {
        config(['jurnal.writes_enabled' => true]);
        $owner = $this->buatAkunGuru();
        $other = $this->buatAkunGuruLain();

        $entry = JurnalPembelajaran::query()->create([
            'user_id' => $owner->id,
            'kelas_id' => 1,
            'nama_kelas' => '7A',
            'mapel_id' => 2,
            'nama_mapel' => 'IPA',
            'tanggal' => '2026-09-02',
            'hari' => 'Rabu',
            'jam_ke' => 3,
            'jam_list' => [3],
            'materi_pokok' => 'Milik owner',
            'ketercapaian' => 'tercapai',
        ]);

        Sanctum::actingAs($other);
        $this->putJson("/api/v1/guru/jurnal/{$entry->id}", [
            'kelas_id' => 1,
            'mapel_id' => 2,
            'tanggal' => '2026-09-02',
            'materi_pokok' => 'Hack',
            'ketercapaian' => 'tercapai',
        ])->assertNotFound();

        $this->deleteJson("/api/v1/guru/jurnal/{$entry->id}")->assertNotFound();
    }

    public function test_cetak_html_matches_simpatisans_layout_hooks(): void
    {
        Gtk::query()->create([
            'nama' => 'Kepala Contoh',
            'nip' => '197001011990031001',
            'jenis' => 'guru',
            'status' => 'aktif',
            'jabatan' => 'Kepala Madrasah',
        ]);

        $user = $this->buatAkunGuru();
        JurnalPembelajaran::query()->create([
            'user_id' => $user->id,
            'kelas_id' => 9,
            'nama_kelas' => '8B',
            'mapel_id' => 4,
            'nama_mapel' => 'B. Indonesia',
            'tanggal' => '2026-09-03',
            'hari' => 'Kamis',
            'jam_ke' => 1,
            'jam_list' => [1, 2],
            'materi_pokok' => 'Puisi',
            'ketercapaian' => 'tercapai',
            'semester_tipe' => 'Ganjil',
            'semester_nama_tahun' => '2025/2026',
        ]);

        Sanctum::actingAs($user);
        $this->get('/api/v1/guru/jurnal/cetak')
            ->assertOk()
            ->assertSee('Jurnal Pembelajaran', false)
            ->assertSee('Semester Ganjil Tahun Pelajaran 2025/2026', false)
            ->assertSee('Kelas : 8B', false)
            ->assertSee('Puisi', false)
            ->assertSee('B. Indonesia', false)
            ->assertSee('prepareGuruPrint', false)
            ->assertSee('fitGuruApp', false)
            ->assertSee('logo-kemenag.png', false)
            ->assertSee('Kepala Contoh', false)
            ->assertSee('MTsN 11 Majalengka', false);
    }

    public function test_cetak_empty_returns_422(): void
    {
        $user = $this->buatAkunGuru();
        Sanctum::actingAs($user);

        $this->get('/api/v1/guru/jurnal/cetak')
            ->assertStatus(422)
            ->assertSee('Belum ada entri jurnal untuk dicetak', false);
    }

    private function buatAkunGuruLain(): User
    {
        Role::findOrCreate(Peran::GURU);

        $gtk = Gtk::query()->create([
            'nama' => 'Siti Aminah',
            'nip' => '198101012005011002',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $user = User::factory()->create([
            'name' => $gtk->nama,
            'username' => $gtk->nip,
            'password' => 'password123',
            'is_aktif' => true,
            'gtk_id' => $gtk->id,
        ]);
        $user->syncRoles([Peran::GURU]);

        return $user->fresh();
    }
}
