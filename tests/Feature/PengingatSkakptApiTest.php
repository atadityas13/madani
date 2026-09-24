<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\TahunAjaran;
use App\Models\TunjanganDokumen;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PengingatSkakptApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guru_tanpa_nrg_tidak_tampil_pengingat(): void
    {
        $this->seed();
        $this->travelTo(now()->setDate(2027, 3, 10));

        Sanctum::actingAs($this->buatGuru(nrg: null));

        $this->getJson('/api/v1/guru/tunjangan/pengingat-skakpt')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tampil', false);
    }

    public function test_sebelum_tanggal_5_tidak_tampil(): void
    {
        $this->seed();
        $this->travelTo(now()->setDate(2027, 3, 4));

        Sanctum::actingAs($this->buatGuru(nrg: 'NRG-SK1'));

        $this->getJson('/api/v1/guru/tunjangan/pengingat-skakpt')
            ->assertOk()
            ->assertJsonPath('data.tampil', false);
    }

    public function test_tampil_jika_belum_upload_skakpt_bulan_sebelumnya(): void
    {
        $this->seed();
        $this->travelTo(now()->setDate(2027, 3, 5));

        Sanctum::actingAs($this->buatGuru(nrg: 'NRG-SK2'));

        $this->getJson('/api/v1/guru/tunjangan/pengingat-skakpt')
            ->assertOk()
            ->assertJsonPath('data.tampil', true)
            ->assertJsonPath('data.bulan', 2)
            ->assertJsonPath('data.nama_bulan', 'Februari')
            ->assertJsonPath(
                'data.judul',
                'Anda belum mengunggah SKAKPT bulan Februari.'
            )
            ->assertJsonPath(
                'data.isi',
                'Silahkan unduh SKAKPT dari EMIS-GTK dan unggah di menu Tunjangan.'
            );
    }

    public function test_tidak_tampil_jika_sudah_upload(): void
    {
        $this->seed();
        $this->travelTo(now()->setDate(2027, 3, 10));

        $guru = $this->buatGuru(nrg: 'NRG-SK3');
        $ta = TahunAjaran::aktif();
        $this->assertNotNull($ta);

        TunjanganDokumen::query()->create([
            'gtk_id' => $guru->gtk_id,
            'jenis' => TunjanganDokumen::JENIS_SKAKPT,
            'slot_key' => TunjanganDokumen::slotKeySkakpt((int) $ta->id, 2),
            'tahun_ajaran_id' => $ta->id,
            'periode' => 2,
            'path' => 'tunjangan/skakpt/feb.pdf',
            'nama_asli' => 'feb.pdf',
        ]);

        Sanctum::actingAs($guru);

        $this->getJson('/api/v1/guru/tunjangan/pengingat-skakpt')
            ->assertOk()
            ->assertJsonPath('data.tampil', false);
    }

    private function buatGuru(?string $nrg): User
    {
        Role::findOrCreate(Peran::GURU);
        $gtk = Gtk::query()->create([
            'nama' => 'Guru Pengingat SKAKPT',
            'nip' => (string) fake()->unique()->numerify('##################'),
            'nuptk' => (string) fake()->unique()->numerify('##############'),
            'nrg' => $nrg,
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $user = User::factory()->create([
            'username' => $gtk->nip,
            'gtk_id' => $gtk->id,
            'is_aktif' => true,
        ]);
        $user->syncRoles([Peran::GURU]);

        return $user->fresh(['gtk']);
    }
}
