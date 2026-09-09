<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\Madrasah;
use App\Models\User;
use App\Services\Persuratan\SptjmTpgPdfService;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SptjmTpgPersuratanTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate(Peran::SUPERADMIN);
        $user = User::factory()->create(['is_aktif' => true]);
        $user->syncRoles([Peran::SUPERADMIN]);

        return $user;
    }

    private function guru(): User
    {
        Role::findOrCreate(Peran::GURU);
        $user = User::factory()->create(['is_aktif' => true]);
        $user->syncRoles([Peran::GURU]);

        return $user;
    }

    public function test_admin_bisa_membuka_index_sptjm_tpg(): void
    {
        $this->seed();
        $admin = $this->admin();
        Gtk::query()->create([
            'nama' => 'Budi Santoso',
            'gelar_depan' => 'Drs.',
            'gelar_belakang' => 'M.Pd.',
            'nuptk' => '1234567890123456',
            'nrg' => '1234567890',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->get(route('persuratan.sptjm-tpg.index'))
            ->assertOk()
            ->assertSee('SPTJM TPG', false)
            ->assertSee('Drs. Budi Santoso, M.Pd.', false)
            ->assertSee('1234567890123456', false)
            ->assertSee('1234567890', false);
    }

    public function test_admin_bisa_unduh_pdf_sptjm_tpg(): void
    {
        $this->seed();
        $admin = $this->admin();
        $madrasah = Madrasah::saatIni();
        $madrasah->update([
            'nama' => 'MTsN 11 Majalengka',
            'alamat' => 'Blok Sindanghurip',
            'desa' => 'Maniis',
            'kecamatan' => 'Cingambul',
            'kota' => 'Majalengka',
        ]);

        $gtk = Gtk::query()->create([
            'nama' => 'Budi Santoso',
            'gelar_depan' => 'Drs.',
            'gelar_belakang' => 'M.Pd.',
            'nuptk' => '1234567890123456',
            'nrg' => 'NRG998877',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('persuratan.sptjm-tpg.pdf', $gtk));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertGreaterThan(500, strlen($response->getContent()));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
    }

    public function test_pdf_payload_memuat_data_pegawai_dan_alamat_sampai_kabupaten(): void
    {
        $this->seed();
        $madrasah = Madrasah::saatIni();
        $madrasah->update([
            'nama' => 'MTsN 11 Majalengka',
            'alamat' => 'Blok Sindanghurip',
            'desa' => 'Maniis',
            'kecamatan' => 'Cingambul',
            'kota' => 'Majalengka',
            'provinsi' => 'Jawa Barat',
        ]);

        $gtk = Gtk::query()->create([
            'nama' => 'Siti Aminah',
            'gelar_belakang' => 'S.Pd.',
            'nuptk' => '1111222233334444',
            'nrg' => 'NRG112233',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $data = app(SptjmTpgPdfService::class)->viewData($gtk);

        $this->assertSame('Siti Aminah, S.Pd.', $data['namaLengkap']);
        $this->assertSame('1111222233334444', $data['nuptk']);
        $this->assertSame('NRG112233', $data['nrg']);
        $this->assertSame('MTsN 11 Majalengka', $data['tempatTugas']);
        $this->assertStringContainsString('Blok Sindanghurip', $data['alamatTempatTugas']);
        $this->assertStringContainsString('Kab. Majalengka', $data['alamatTempatTugas']);
        $this->assertStringNotContainsString('Jawa Barat', $data['alamatTempatTugas']);
    }

    public function test_guru_tidak_boleh_akses_sptjm_tpg(): void
    {
        $this->seed();
        $guru = $this->guru();
        $gtk = Gtk::query()->create([
            'nama' => 'Guru Biasa',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $this->actingAs($guru)
            ->get(route('persuratan.sptjm-tpg.index'))
            ->assertForbidden();

        $this->actingAs($guru)
            ->get(route('persuratan.sptjm-tpg.pdf', $gtk))
            ->assertForbidden();
    }
}
