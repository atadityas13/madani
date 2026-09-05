<?php

namespace App\Services;

use App\Models\Madrasah;
use App\Models\RekamDidik;
use App\Models\Siswa;
use App\Models\SiswaPernyataan;
use App\Support\PernyataanSiswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PernyataanPdfService
{
    public function __construct(private PortofolioPdfService $portofolio) {}

    /**
     * @param  array{ttd_siswa_data_uri: string, ttd_wali_data_uri: string, nama_wali: string, tanggal: CarbonInterface}  $tandaTangan
     */
    public function preview(Siswa $siswa, array $tandaTangan): Response
    {
        return $this->makePdf($siswa, $tandaTangan)->stream($this->filename($siswa));
    }

    /**
     * @param  array{ttd_siswa_data_uri: string, ttd_wali_data_uri: string, nama_wali: string, tanggal: CarbonInterface}  $tandaTangan
     */
    public function raw(Siswa $siswa, array $tandaTangan): string
    {
        return $this->makePdf($siswa, $tandaTangan)->output();
    }

    public function downloadSaved(SiswaPernyataan $pernyataan): Response
    {
        $siswa = $pernyataan->siswa()->firstOrFail();
        $tandaTangan = [
            'ttd_siswa_data_uri' => $this->r2DataUri($pernyataan->ttd_siswa_path) ?? '',
            'ttd_wali_data_uri' => $this->r2DataUri($pernyataan->ttd_wali_path) ?? '',
            'nama_wali' => $pernyataan->nama_wali,
            'tanggal' => $pernyataan->dikonfirmasi_at ?? now(),
        ];

        return $this->makePdf($siswa, $tandaTangan)->download($this->filename($siswa));
    }

    /**
     * @param  array{ttd_siswa_data_uri: string, ttd_wali_data_uri: string, nama_wali: string, tanggal: CarbonInterface}  $tandaTangan
     */
    private function makePdf(Siswa $siswa, array $tandaTangan): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('siswa.biodata-pernyataan-pdf', $this->viewData($siswa, $tandaTangan))
            ->setPaper('a4', 'portrait');
    }

    private function filename(Siswa $siswa): string
    {
        $nama = preg_replace('/[^A-Za-z0-9 _-]+/', '', $siswa->nama) ?: 'siswa';

        return trim($nama).' - Biodata dan Surat Pernyataan.pdf';
    }

    /**
     * @param  array{ttd_siswa_data_uri: string, ttd_wali_data_uri: string, nama_wali: string, tanggal: CarbonInterface}  $tandaTangan
     * @return array<string, mixed>
     */
    public function viewData(Siswa $siswa, array $tandaTangan): array
    {
        $base = $this->portofolio->viewData($siswa);
        $siswa->loadMissing(['rekamDidik', 'ayah']);
        $teks = PernyataanSiswa::teksAktif();
        $tanggal = $tandaTangan['tanggal'];

        return array_merge($base, [
            'qrDataUri' => null,
            'jenjangRows' => $this->jenjangRows($siswa, $siswa->rekamDidik),
            'teksPoin1' => $teks['poin_1'],
            'teksPenutupBiodata' => $teks['penutup_biodata'],
            'ttdSiswaDataUri' => $tandaTangan['ttd_siswa_data_uri'],
            'ttdWaliDataUri' => $tandaTangan['ttd_wali_data_uri'],
            'namaWaliEfektif' => $tandaTangan['nama_wali'],
            'tanggalSurat' => $tanggal->timezone(config('app.timezone'))->locale('id')->translatedFormat('d F Y'),
            'madrasahKota' => Madrasah::saatIni()->kota ?: 'Majalengka',
        ]);
    }

    /**
     * @return list<array{0: string, 1: ?string}>
     */
    private function jenjangRows(Siswa $siswa, ?RekamDidik $rd): array
    {
        $jk = match ($siswa->jenis_kelamin) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => $siswa->jenis_kelamin,
        };
        $namaAyah = $siswa->ayah?->nama
            ?: $rd?->nama_ayah_ijazah
            ?: $rd?->nama_ayah_kk;

        return [
            ['Nama lengkap', $siswa->nama],
            ['NISN', $siswa->nisn],
            ['Tempat lahir', $siswa->tempat_lahir],
            ['Tanggal lahir', $siswa->tanggal_lahir?->locale('id')->translatedFormat('d F Y')],
            ['Jenis kelamin', $jk],
            ['Nama ayah kandung', $namaAyah],
            ['Nama SD/MI', $rd?->nama_sd],
            ['NPSN', $rd?->npsn],
            ['Tahun ajaran kelulusan', $rd?->tahun_ajaran_kelulusan],
            ['NIP kepala sekolah', $rd?->nip_kepala_sekolah],
            ['Nama kepala sekolah', $rd?->nama_kepala_sekolah],
            ['Nomor seri ijazah', $rd?->nomor_seri_ijazah],
            ['Tanggal terbit ijazah', $rd?->tanggal_terbit_ijazah?->locale('id')->translatedFormat('d F Y')],
        ];
    }

    private function r2DataUri(?string $path): ?string
    {
        if (! filled($path) || ! Storage::disk('r2')->exists($path)) {
            return null;
        }

        $bytes = Storage::disk('r2')->get($path);
        if ($bytes === null || $bytes === '') {
            return null;
        }

        $mime = Storage::disk('r2')->mimeType($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }
}
