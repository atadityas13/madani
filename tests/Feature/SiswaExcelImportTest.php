<?php

namespace Tests\Feature;

use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\Manajemen\SiswaExcelImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SiswaExcelImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_download_template(): void
    {
        $this->actingAsSuperadmin()
            ->get(route('manajemen.database.siswa.template'))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_impor_siswa_berhasil_dengan_rombel_dan_trim_nik(): void
    {
        $this->actingAsSuperadmin();
        $tahun = TahunAjaran::aktif();

        Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => '1',
        ]);

        $file = $this->buatExcel([
            [1, 'Siswa Satu', '99', '1234567890', "'3210230911120003", 'Majalengka', '2012-11-09', 'Laki-laki', 'VII', '1', 'Ayah Satu', 'Ibu Satu'],
        ]);

        $this->post(route('manajemen.database.siswa.impor'), [
            'file' => $file,
        ])->assertRedirect(route('manajemen.database'))
            ->assertSessionHas('status');

        $siswa = Siswa::query()->where('nisn', '1234567890')->first();
        $this->assertNotNull($siswa);
        $this->assertSame('3210230911120003', $siswa->nik);
        $this->assertSame('L', $siswa->jenis_kelamin);
        $this->assertSame('VII', $siswa->angkatan);
        $this->assertSame('Islam', $siswa->agama);
        $this->assertSame('aktif', $siswa->status_keaktifan);
        $this->assertSame('Ibu Satu', $siswa->ibu?->nama);
        $this->assertSame('Ayah Satu', $siswa->ayah?->nama);
        $this->assertTrue($siswa->rombels()->wherePivot('status', 'aktif')->exists());
    }

    public function test_impor_tanpa_rombel_status_aktif_tanpa_rombel(): void
    {
        $this->actingAsSuperadmin();

        $file = $this->buatExcel([
            [1, 'Siswa Dua', '', '2234567890', '3210230911120004', 'Majalengka', '2012-01-01', 'Perempuan', 'VIII', '', '', 'Ibu Dua'],
        ]);

        $this->post(route('manajemen.database.siswa.impor'), [
            'file' => $file,
        ])->assertRedirect(route('manajemen.database'));

        $siswa = Siswa::query()->where('nisn', '2234567890')->first();
        $this->assertNotNull($siswa);
        $this->assertSame('aktif_tanpa_rombel', $siswa->status_keaktifan);
        $this->assertSame('P', $siswa->jenis_kelamin);
        $this->assertNull($siswa->ayah?->nama);
        $this->assertSame('Ibu Dua', $siswa->ibu?->nama);
    }

    public function test_impor_gagal_jika_rombel_tidak_ada(): void
    {
        $this->actingAsSuperadmin();

        $file = $this->buatExcel([
            [1, 'Siswa Tiga', '', '3234567890', '3210230911120005', 'Majalengka', '2012-01-01', 'Laki-laki', 'VII', '99', '', 'Ibu Tiga'],
        ]);

        $this->post(route('manajemen.database.siswa.impor'), [
            'file' => $file,
        ])->assertRedirect()
            ->assertSessionHasErrors('file');

        $this->assertNull(Siswa::query()->where('nisn', '3234567890')->first());
    }

    public function test_impor_gagal_tanpa_nama_ibu(): void
    {
        $this->actingAsSuperadmin();

        $file = $this->buatExcel([
            [1, 'Siswa Empat', '', '4234567890', '3210230911120006', 'Majalengka', '2012-01-01', 'Laki-laki', 'VII', '', '', ''],
        ]);

        $this->post(route('manajemen.database.siswa.impor'), [
            'file' => $file,
        ])->assertSessionHasErrors('file');

        $this->assertSame(0, Siswa::query()->count());
    }

    public function test_daftar_siswa_dan_anggota_rombel_urut_nama(): void
    {
        $this->actingAsSuperadmin();
        $tahun = TahunAjaran::aktif();

        $rombel = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => '1',
        ]);

        $file = $this->buatExcel([
            [1, 'Zainab Putri', '', '1111111111', '3210230911120011', 'Majalengka', '2012-01-01', 'Perempuan', 'VII', '1', '', 'Ibu Z'],
            [2, 'Ahmad Budi', '', '2222222222', '3210230911120012', 'Majalengka', '2012-02-02', 'Laki-laki', 'VII', '1', '', 'Ibu A'],
        ]);

        $this->post(route('manajemen.database.siswa.impor'), [
            'file' => $file,
        ])->assertRedirect(route('manajemen.database'));

        $index = $this->get(route('siswa.index', ['per_page' => 'all']))->assertOk();
        $content = $index->getContent();
        $this->assertTrue(
            strpos($content, 'Ahmad Budi') < strpos($content, 'Zainab Putri'),
            'Index siswa harus alfabetis berdasarkan nama'
        );

        $show = $this->get(route('rombel.show', $rombel))->assertOk();
        $showContent = $show->getContent();
        $this->assertTrue(
            strpos($showContent, 'Ahmad Budi') < strpos($showContent, 'Zainab Putri'),
            'Anggota rombel harus alfabetis berdasarkan nama'
        );
    }

    public function test_impor_gagal_jika_nis_sudah_ada(): void
    {
        $this->actingAsSuperadmin();

        Siswa::query()->create([
            'nama' => 'Sudah Ada',
            'nis' => '2026001',
            'nisn' => '9999999999',
            'nik' => '3210230911120099',
            'status_keaktifan' => 'aktif_tanpa_rombel',
            'agama' => 'Islam',
        ]);

        $file = $this->buatExcel([
            [1, 'Siswa Baru', '2026001', '5555555555', '3210230911120055', 'Majalengka', '2012-01-01', 'Laki-laki', 'VII', '', '', 'Ibu Baru'],
        ]);

        $this->post(route('manajemen.database.siswa.impor'), [
            'file' => $file,
        ])->assertSessionHasErrors('file');

        $this->assertNull(Siswa::query()->where('nisn', '5555555555')->first());
    }

    public function test_normalisasi_nik_membuang_apostrof(): void
    {
        $service = app(SiswaExcelImportService::class);

        $this->assertSame('3210230911120003', $service->normalisasiNik("'3210230911120003"));
        $this->assertSame('3210230911120003', $service->normalisasiNik(' 3210230911120003 '));
    }

    public function test_database_page_shows_siswa_import_controls(): void
    {
        $this->actingAsSuperadmin()
            ->get(route('manajemen.database'))
            ->assertOk()
            ->assertSee(route('manajemen.database.siswa.template'), false)
            ->assertSee('Impor Excel', false)
            ->assertSee('Unduh template', false);
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function buatExcel(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([SiswaExcelImportService::HEADERS], null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'siswa-xlsx-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile(
            $path,
            'impor-siswa.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    private function actingAsSuperadmin(): static
    {
        $this->seed();

        return $this->actingAs(User::query()->where('username', 'admin')->first());
    }
}
