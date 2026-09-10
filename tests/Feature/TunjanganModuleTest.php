<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\TahunAjaran;
use App\Models\TunjanganDokumen;
use App\Models\User;
use App\Services\Tunjangan\TunjanganDokumenService;
use App\Services\Tunjangan\TunjanganZipImportService;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

class TunjanganModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bisa_membuka_hub_tunjangan(): void
    {
        $this->seed();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('tunjangan.index'))
            ->assertOk()
            ->assertSee('SKMT', false)
            ->assertSee('Surat Keterangan Melaksanakan Tugas', false)
            ->assertSee('SKBK', false)
            ->assertSee('Surat Keterangan Beban Kerja', false)
            ->assertSee('SPTJM', false)
            ->assertSee('Surat Pertanggungjawaban Mutlak', false)
            ->assertSee('SKAKPT', false)
            ->assertDontSee('Upload per semester', false)
            ->assertDontSee('Upload per bulan', false);
    }

    public function test_guru_tanpa_nrg_tidak_boleh_akses(): void
    {
        $this->seed();
        $guru = $this->buatGuru(nrg: null);

        $this->actingAs($guru)
            ->get(route('tunjangan.index'))
            ->assertForbidden();
    }

    public function test_guru_tersertifikasi_hanya_lihat_milik_sendiri(): void
    {
        $this->seed();
        $milik = $this->buatGtk(['nama' => 'Guru Sendiri', 'nrg' => 'NRG1', 'nuptk' => '111']);
        $lain = $this->buatGtk(['nama' => 'Guru Lain', 'nrg' => 'NRG2', 'nuptk' => '222']);
        $guru = $this->buatGuru(nrg: 'NRG1', gtk: $milik);

        $this->actingAs($guru)
            ->get(route('tunjangan.jenis.show', ['jenis' => 'skakpt', 'gtk' => $milik]))
            ->assertOk()
            ->assertSee('Guru Sendiri', false)
            ->assertSee('Semester I (Juli–Desember)', false)
            ->assertSee('Juli', false)
            ->assertSee('Januari', false);

        $this->actingAs($guru)
            ->get(route('tunjangan.jenis.show', ['jenis' => 'skakpt', 'gtk' => $lain]))
            ->assertForbidden();
    }

    public function test_skakpt_bulan_belum_berlalu_terkunci(): void
    {
        Storage::fake('r2');
        $this->seed();
        $this->travelTo(now()->setDate(2027, 3, 15));

        $ta = TahunAjaran::aktif();
        $this->assertNotNull($ta);
        $gtk = $this->buatGtk(['nrg' => 'NRG9']);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('tunjangan.jenis.upload', ['jenis' => 'skakpt', 'gtk' => $gtk]), [
                'periode' => 3,
                'tahun_ajaran_id' => $ta->id,
                'file' => UploadedFile::fake()->create('x.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');

        $this->actingAs($admin)
            ->post(route('tunjangan.jenis.upload', ['jenis' => 'skakpt', 'gtk' => $gtk]), [
                'periode' => 2,
                'tahun_ajaran_id' => $ta->id,
                'file' => UploadedFile::fake()->create('ok.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tunjangan_dokumens', [
            'gtk_id' => $gtk->id,
            'jenis' => 'skakpt',
            'periode' => 2,
            'tahun_ajaran_id' => $ta->id,
            'slot_key' => TunjanganDokumen::slotKeySkakpt((int) $ta->id, 2),
        ]);
    }

    public function test_preview_stream_inline_bukan_attachment(): void
    {
        Storage::fake('r2');
        $this->seed();
        $this->travelTo(now()->setDate(2027, 3, 15));

        $ta = TahunAjaran::aktif();
        $gtk = $this->buatGtk(['nrg' => 'NRG12']);
        $admin = $this->admin();

        $dokumen = app(TunjanganDokumenService::class)->simpanPdf(
            $gtk,
            'skakpt',
            2,
            UploadedFile::fake()->create('preview.pdf', 100, 'application/pdf'),
            null,
            $ta,
        );

        $response = $this->actingAs($admin)
            ->get(route('tunjangan.jenis.stream', ['jenis' => 'skakpt', 'gtk' => $gtk, 'dokumen' => $dokumen]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('inline', $disposition);
        $this->assertStringNotContainsString('attachment', $disposition);
    }

    public function test_zip_skmt_memetakan_nama_file_ke_guru(): void
    {
        Storage::fake('r2');
        $this->seed();

        $ta = TahunAjaran::aktif();
        $this->assertNotNull($ta);
        $this->assertStringContainsString('2026', (string) $ta->nama);

        $gtk = $this->buatGtk(['nama' => 'A. ABD. MANAN', 'nrg' => 'NRG77', 'nuptk' => '777']);
        $admin = $this->admin();

        $zipPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'skmt-'.uniqid().'.zip';
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE));
        $pdfPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'skmt-sample.pdf';
        file_put_contents($pdfPath, '%PDF-1.4 sample');
        $zip->addFile($pdfPath, 'Rekap_Penilaian_SKMT_A._ABD._MANAN_TA2026_Sem1.pdf');
        $zip->close();
        @unlink($pdfPath);

        $this->actingAs($admin)
            ->post(route('tunjangan.jenis.zip', 'skmt'), [
                'zip' => new UploadedFile($zipPath, 'skmt.zip', 'application/zip', null, true),
            ])
            ->assertRedirect(route('tunjangan.jenis.index', 'skmt'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('tunjangan_dokumens', [
            'gtk_id' => $gtk->id,
            'jenis' => 'skmt',
            'periode' => 1,
            'tahun_ajaran_id' => $ta->id,
        ]);
        @unlink($zipPath);
    }

    public function test_zip_skbk_wajib_tahun_ajaran_dan_semester(): void
    {
        Storage::fake('r2');
        $this->seed();
        $admin = $this->admin();

        $zipPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'skbk-'.uniqid().'.zip';
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE));
        $pdfPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'skbk-sample.pdf';
        file_put_contents($pdfPath, '%PDF-1.4 sample');
        $zip->addFile($pdfPath, 'SKBK_A._ABD._MANAN.pdf');
        $zip->close();
        @unlink($pdfPath);

        $this->actingAs($admin)
            ->post(route('tunjangan.jenis.zip', 'skbk'), [
                'zip' => new UploadedFile($zipPath, 'skbk.zip', 'application/zip', null, true),
            ])
            ->assertSessionHasErrors(['tahun_ajaran_id', 'semester']);

        @unlink($zipPath);
    }

    public function test_parse_nama_file_skmt_dan_skbk(): void
    {
        $service = app(TunjanganZipImportService::class);

        $skmt = $service->parseSkmt('Rekap_Penilaian_SKMT_A._ABD._MANAN_TA2026_Sem1.pdf');
        $this->assertNotNull($skmt);
        $this->assertSame('AABDMANAN', $skmt['nama_key']);
        $this->assertSame(2026, $skmt['ta']);
        $this->assertSame(1, $skmt['semester']);

        $skbk = $service->parseSkbk('SKBK_A._ABD._MANAN.pdf');
        $this->assertNotNull($skbk);
        $this->assertSame('AABDMANAN', $skbk['nama_key']);
    }

    public function test_sptjm_unduh_personal(): void
    {
        $this->seed();
        $gtk = $this->buatGtk(['nrg' => 'NRG88', 'nama' => 'Guru SPTJM']);
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->post(route('tunjangan.sptjm.download', $gtk), [
                'tanggal_surat' => '2026-08-15',
                'mode' => 'download',
            ]);

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_normalize_nama_key(): void
    {
        $service = app(TunjanganDokumenService::class);
        $this->assertSame('AABDMANAN', $service->normalizeNamaKey('A._ABD._MANAN'));
        $this->assertSame('AABDMANAN', $service->normalizeNamaKey('A. ABD. MANAN'));
        $this->assertSame('ABDULMANAN', $service->normalizeNamaKey('H. ABDUL MANAN'));
        $this->assertSame('ABDULMANAN', $service->normalizeNamaKey('H._ABDUL_MANAN'));
        $this->assertSame('ABDULMANAN', $service->normalizeNamaKey('H.ABDUL MANAN'));
        $this->assertSame('SITIAMINAH', $service->normalizeNamaKey('Hj. SITI AMINAH'));
        $this->assertSame('SITIAMINAH', $service->normalizeNamaKey('Hj._SITI_AMINAH'));
        $this->assertSame('HASAN', $service->normalizeNamaKey('HASAN'));
        $this->assertSame('HAJI', $service->normalizeNamaKey('HAJI'));
        $this->assertSame('ABDULMANAN', $service->normalizeNamaKey('Haji Abdul Manan'));
    }

    public function test_cari_gtk_abaikan_prefix_haji_hj(): void
    {
        $this->seed();
        $gtk = $this->buatGtk([
            'nrg' => 'NRG-HAJI-1',
            'nama' => 'H. ABDUL MANAN',
        ]);

        $service = app(TunjanganDokumenService::class);
        $matches = $service->cariGtkByNamaKey($service->normalizeNamaKey('ABDUL_MANAN'));

        $this->assertCount(1, $matches);
        $this->assertTrue($matches[0]->is($gtk));
    }

    public function test_admin_skakpt_default_filter_tahun_aktif_dan_bulan_sebelumnya(): void
    {
        Storage::fake('r2');
        $this->seed();
        $this->travelTo(now()->setDate(2027, 3, 15));

        $ta = TahunAjaran::aktif();
        $this->assertNotNull($ta);
        $sudah = $this->buatGtk(['nama' => 'Guru Sudah', 'nrg' => 'NRG-S1', 'nuptk' => '1001', 'duk' => '2']);
        $belum = $this->buatGtk(['nama' => 'Guru Belum', 'nrg' => 'NRG-B1', 'nuptk' => '1002', 'duk' => '1']);
        $admin = $this->admin();

        app(TunjanganDokumenService::class)->simpanPdf(
            $sudah,
            'skakpt',
            2,
            UploadedFile::fake()->create('februari.pdf', 100, 'application/pdf'),
            null,
            $ta,
            enforcePeriodeLock: false,
        );

        $response = $this->actingAs($admin)
            ->get(route('tunjangan.jenis.index', 'skakpt'))
            ->assertOk()
            ->assertSee('Sudah upload', false)
            ->assertSee('Belum upload', false)
            ->assertSee('Keterangan', false)
            ->assertSee('Unduh PDF', false)
            ->assertDontSee('bulan berjalan', false)
            ->assertDontSee('Klik untuk filter', false)
            ->assertDontSee('Unduh ZIP', false)
            ->assertSee('Guru Sudah', false)
            ->assertSee('Guru Belum', false);

        $response->assertViewHas('skakptFilter', function (array $filter) use ($ta): bool {
            return (int) $filter['tahun_ajaran']->id === (int) $ta->id
                && (int) $filter['bulan'] === 2
                && $filter['status_upload'] === null
                && (int) $filter['jumlah_sudah'] === 1
                && (int) $filter['jumlah_belum'] >= 1;
        });

        $this->actingAs($admin)
            ->get(route('tunjangan.jenis.index', [
                'jenis' => 'skakpt',
                'tahun_ajaran_id' => $ta->id,
                'bulan' => 2,
                'status' => 'sudah',
            ]))
            ->assertOk()
            ->assertSee('Guru Sudah', false)
            ->assertDontSee('Guru Belum', false);

        $this->actingAs($admin)
            ->get(route('tunjangan.jenis.index', [
                'jenis' => 'skakpt',
                'tahun_ajaran_id' => $ta->id,
                'bulan' => 2,
                'status' => 'belum',
            ]))
            ->assertOk()
            ->assertSee('Guru Belum', false)
            ->assertDontSee('Guru Sudah', false);
    }

    public function test_admin_unduh_massal_skakpt_satu_pdf_urut_duk(): void
    {
        Storage::fake('r2');
        $this->seed();
        $this->travelTo(now()->setDate(2027, 3, 15));

        $ta = TahunAjaran::aktif();
        $admin = $this->admin();
        $dua = $this->buatGtk(['nama' => 'Guru Dua', 'nrg' => 'NRG-D2', 'nuptk' => '2002', 'duk' => '2']);
        $satu = $this->buatGtk(['nama' => 'Guru Satu', 'nrg' => 'NRG-D1', 'nuptk' => '2001', 'duk' => '1']);

        $service = app(TunjanganDokumenService::class);
        foreach ([$dua, $satu] as $gtk) {
            $tmp = tempnam(sys_get_temp_dir(), 'skakpt_pdf_');
            $this->assertNotFalse($tmp);
            file_put_contents($tmp, $this->minimalPdf());
            $service->simpanPdf(
                $gtk,
                'skakpt',
                2,
                new UploadedFile($tmp, $gtk->nama.'.pdf', 'application/pdf', null, true),
                null,
                $ta,
                enforcePeriodeLock: false,
            );
            @unlink($tmp);
        }

        $entries = $service->entriUnduhMassalSkakpt((int) $ta->id, 2, 'sudah');
        $this->assertCount(2, $entries);
        $this->assertSame($satu->id, $entries[0]['gtk']->id);
        $this->assertSame($dua->id, $entries[1]['gtk']->id);

        $response = $this->actingAs($admin)
            ->get(route('tunjangan.jenis.unduh-massal', [
                'jenis' => 'skakpt',
                'tahun_ajaran_id' => $ta->id,
                'bulan' => 2,
                'status' => 'sudah',
            ]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    private function minimalPdf(): string
    {
        $pdf = new \FPDF;
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(40, 10, 'SKAKPT');

        return $pdf->Output('S');
    }

    private function admin(): User
    {
        return User::query()->where('username', 'admin')->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function buatGtk(array $overrides = []): Gtk
    {
        return Gtk::query()->create(array_merge([
            'nama' => 'Guru Tunjangan',
            'nip' => '198001012005011001',
            'nuptk' => (string) fake()->unique()->numerify('##############'),
            'nrg' => 'NRG-TEST',
            'golongan' => 'III/c',
            'status_pegawai' => 'PNS',
            'jenis' => 'guru',
            'status' => 'aktif',
        ], $overrides));
    }

    private function buatGuru(?string $nrg, ?Gtk $gtk = null): User
    {
        Role::findOrCreate(Peran::GURU);
        $gtk ??= $this->buatGtk(['nrg' => $nrg, 'nuptk' => (string) fake()->unique()->numerify('##############')]);
        if ($nrg === null) {
            $gtk->update(['nrg' => null]);
        }

        $user = User::factory()->create([
            'is_aktif' => true,
            'gtk_id' => $gtk->id,
        ]);
        $user->syncRoles([Peran::GURU]);

        return $user;
    }
}
