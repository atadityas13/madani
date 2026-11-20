<?php

namespace Tests\Feature;

use App\Models\Gtk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SimpatisansGtkExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_ekspor_ditolak_tanpa_secret(): void
    {
        config(['services.madani_introspect.secret' => 'rahasia']);

        $this->getJson('/api/v1/simpatisans/gtks')
            ->assertUnauthorized();
    }

    public function test_ekspor_hanya_berisi_guru_yang_bisa_diubah_di_madani(): void
    {
        config(['services.madani_introspect.secret' => 'rahasia']);

        Gtk::query()->create([
            'nama' => 'Budi Santoso',
            'nip' => '198001012005011001',
            'duk' => '2',
            'email' => 'budi@example.com',
            'status_pegawai' => 'PNS',
            'kode_internal' => 'BD',
            'mapel_ijazah' => 'Matematika',
            'rumpun_ijazah' => null,
            'status_sertifikasi' => true,
            'jenis' => Gtk::JENIS_GURU,
            'status' => 'aktif',
        ]);
        Gtk::query()->create([
            'nama' => 'Tendik Kantor',
            'nip' => '196501011990011002',
            'jenis' => Gtk::JENIS_TENDIK,
            'status' => 'aktif',
        ]);

        $this->getJson('/api/v1/simpatisans/gtks', [
            'X-Madani-Introspect-Secret' => 'rahasia',
        ])
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.nip', '198001012005011001')
            ->assertJsonPath('data.0.nama', 'Budi Santoso')
            ->assertJsonPath('data.0.duk', '2')
            ->assertJsonPath('data.0.email', 'budi@example.com')
            ->assertJsonPath('data.0.kode_internal', 'BD')
            ->assertJsonPath('data.0.mapel_ijazah', 'Matematika')
            ->assertJsonPath('data.0.rumpun_ijazah', null)
            ->assertJsonPath('data.0.status_sertifikasi', true)
            ->assertJsonPath('data.0.status', 'aktif')
            ->assertJsonMissing(['nama' => 'Tendik Kantor']);
    }

    public function test_tanggal_lahir_tidak_valid_tidak_menggagalkan_ekspor(): void
    {
        config(['services.madani_introspect.secret' => 'rahasia']);

        $gtk = Gtk::query()->create([
            'nama' => 'Budi Santoso',
            'nip' => '198001012005011001',
            'jenis' => Gtk::JENIS_GURU,
            'status' => 'aktif',
        ]);
        DB::table('gtks')->where('id', $gtk->id)->update(['tanggal_lahir' => '0000-00-00']);

        $this->getJson('/api/v1/simpatisans/gtks', [
            'X-Madani-Introspect-Secret' => 'rahasia',
        ])
            ->assertOk()
            ->assertJsonPath('data.0.tanggal_lahir', null);
    }
}
