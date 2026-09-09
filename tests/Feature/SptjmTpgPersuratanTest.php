<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\Madrasah;
use App\Models\User;
use App\Services\Persuratan\SptjmTpgPdfService;
use App\Support\Peran;
use Carbon\Carbon;
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

    public function test_admin_bisa_membuka_halaman_persuratan(): void
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
            ->get(route('persuratan.index'))
            ->assertOk()
            ->assertSee('Persuratan', false)
            ->assertSee('SPTJM TPG', false)
            ->assertSee('Pilih guru', false)
            ->assertSee('Drs. Budi Santoso, M.Pd.', false);
    }

    public function test_admin_bisa_generate_pdf_multi_guru(): void
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

        $gtkA = Gtk::query()->create([
            'nama' => 'Budi Santoso',
            'gelar_depan' => 'Drs.',
            'gelar_belakang' => 'M.Pd.',
            'nuptk' => '1234567890123456',
            'nrg' => 'NRG998877',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);
        $gtkB = Gtk::query()->create([
            'nama' => 'Siti Aminah',
            'gelar_belakang' => 'S.Pd.',
            'nuptk' => '1111222233334444',
            'nrg' => 'NRG112233',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('persuratan.sptjm-tpg.generate'), [
                'gtk_ids' => [$gtkA->id, $gtkB->id],
                'tanggal_surat' => '2026-08-15',
            ]);

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertGreaterThan(500, strlen($response->getContent()));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
    }

    public function test_generate_memerlukan_guru_dan_tanggal(): void
    {
        $this->seed();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('persuratan.sptjm-tpg.generate'), [])
            ->assertSessionHasErrors(['gtk_ids', 'tanggal_surat']);
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

        $data = app(SptjmTpgPdfService::class)->viewData(
            $gtk,
            $madrasah,
            Carbon::parse('2026-08-15')->locale('id'),
        );

        $this->assertSame('Siti Aminah, S.Pd.', $data['namaLengkap']);
        $this->assertSame('1111222233334444', $data['nuptk']);
        $this->assertSame('NRG112233', $data['nrg']);
        $this->assertSame('MTsN 11 Majalengka', $data['tempatTugas']);
        $this->assertStringContainsString('Blok Sindanghurip', $data['alamatTempatTugas']);
        $this->assertStringContainsString('Kab. Majalengka', $data['alamatTempatTugas']);
        $this->assertStringNotContainsString('Jawa Barat', $data['alamatTempatTugas']);
        $this->assertSame('15 Agustus 2026', $data['tanggalSurat']);
    }

    public function test_admin_bisa_cetak_pdf_sptjm_tpg(): void
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
            ->post(route('persuratan.sptjm-tpg.generate'), [
                'gtk_ids' => [$gtk->id],
                'tanggal_surat' => '2026-08-15',
                'mode' => 'print',
            ]);

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'));
    }

    public function test_guru_tidak_boleh_akses_persuratan(): void
    {
        $this->seed();
        $guru = $this->guru();
        $gtk = Gtk::query()->create([
            'nama' => 'Guru Biasa',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $this->actingAs($guru)
            ->get(route('persuratan.index'))
            ->assertForbidden();

        $this->actingAs($guru)
            ->post(route('persuratan.sptjm-tpg.generate'), [
                'gtk_ids' => [$gtk->id],
                'tanggal_surat' => '2026-08-15',
            ])
            ->assertForbidden();
    }
}
