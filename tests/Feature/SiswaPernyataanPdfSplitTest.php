<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Models\SiswaPernyataan;
use App\Services\PernyataanPdfService;
use App\Support\PernyataanSiswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiswaPernyataanPdfSplitTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_menghasilkan_dua_pdf_terpisah(): void
    {
        Storage::fake('r2');
        $this->seed();

        $siswa = Siswa::query()->create([
            'nama' => 'PDF Split',
            'nisn' => '1999999999',
            'nik' => '3210230911120099',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-05-05',
            'jenis_kelamin' => 'P',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif_tanpa_rombel',
        ]);

        $teks = PernyataanSiswa::teksAktif();
        $pernyataan = SiswaPernyataan::query()->create([
            'siswa_id' => $siswa->id,
            'versi_teks' => $teks['versi'],
            'teks_poin_1' => $teks['poin_1'],
            'teks_poin_2' => $teks['poin_2'],
            'setuju_poin_1' => true,
            'setuju_poin_2' => true,
            'nama_siswa' => $siswa->nama,
            'nama_wali' => 'Ibu Contoh',
            'ttd_siswa_path' => 'siswa/'.$siswa->id.'/ttd-siswa.png',
            'ttd_wali_path' => 'siswa/'.$siswa->id.'/ttd-wali.png',
            'dikonfirmasi_at' => now(),
        ]);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        Storage::disk('r2')->put($pernyataan->ttd_siswa_path, $png);
        Storage::disk('r2')->put($pernyataan->ttd_wali_path, $png);

        /** @var PernyataanPdfService $pdf */
        $pdf = app(PernyataanPdfService::class);

        $biodata = $pdf->downloadSaved($pernyataan, PernyataanPdfService::JENIS_BIODATA);
        $this->assertStringStartsWith('%PDF', $biodata->getContent());
        $this->assertStringContainsString('Pernyataan Biodata', (string) $biodata->headers->get('content-disposition'));

        $peserta = $pdf->downloadSaved($pernyataan, PernyataanPdfService::JENIS_PESERTA_DIDIK);
        $this->assertStringStartsWith('%PDF', $peserta->getContent());
        $this->assertStringContainsString('Peserta Didik', (string) $peserta->headers->get('content-disposition'));
    }
}
