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
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class PernyataanPdfService
{
    public const JENIS_BIODATA = 'biodata';

    public const JENIS_PESERTA_DIDIK = 'peserta-didik';

    /** @var list<string> */
    public const JENIS_VALID = [
        self::JENIS_BIODATA,
        self::JENIS_PESERTA_DIDIK,
    ];

    public function __construct(private PortofolioPdfService $portofolio) {}

    /**
     * @param  array{ttd_siswa_data_uri: string, ttd_wali_data_uri: string, nama_wali: string, tanggal: CarbonInterface}  $tandaTangan
     */
    public function preview(Siswa $siswa, array $tandaTangan, string $jenis): Response
    {
        return $this->makePdf($siswa, $tandaTangan, $jenis)->stream($this->filename($siswa, $jenis));
    }

    /**
     * @param  array{ttd_siswa_data_uri: string, ttd_wali_data_uri: string, nama_wali: string, tanggal: CarbonInterface}  $tandaTangan
     */
    public function raw(Siswa $siswa, array $tandaTangan, string $jenis): string
    {
        return $this->makePdf($siswa, $tandaTangan, $jenis)->output();
    }

    public function downloadSaved(SiswaPernyataan $pernyataan, string $jenis): Response
    {
        return $this->responseSaved($pernyataan, $jenis, download: true);
    }

    public function streamSaved(SiswaPernyataan $pernyataan, string $jenis): Response
    {
        return $this->responseSaved($pernyataan, $jenis, download: false);
    }

    private function responseSaved(SiswaPernyataan $pernyataan, string $jenis, bool $download): Response
    {
        $siswa = $pernyataan->siswa()->firstOrFail();
        $tandaTangan = [
            'ttd_siswa_data_uri' => $this->r2DataUri($pernyataan->ttd_siswa_path) ?? '',
            'ttd_wali_data_uri' => $this->r2DataUri($pernyataan->ttd_wali_path) ?? '',
            'nama_wali' => $pernyataan->nama_wali,
            'tanggal' => $pernyataan->dikonfirmasi_at ?? now(),
        ];

        $pdf = $this->makePdf($siswa, $tandaTangan, $jenis);
        $name = $this->filename($siswa, $jenis);

        return $download ? $pdf->download($name) : $pdf->stream($name);
    }

    /**
     * @param  array{ttd_siswa_data_uri: string, ttd_wali_data_uri: string, nama_wali: string, tanggal: CarbonInterface}  $tandaTangan
     */
    private function makePdf(Siswa $siswa, array $tandaTangan, string $jenis): \Barryvdh\DomPDF\PDF
    {
        $jenis = $this->normalizeJenis($jenis);
        $view = $jenis === self::JENIS_PESERTA_DIDIK
            ? 'siswa.pernyataan-peserta-didik-pdf'
            : 'siswa.pernyataan-biodata-pdf';

        return Pdf::loadView($view, $this->viewData($siswa, $tandaTangan))
            ->setPaper('a4', 'portrait');
    }

    public function normalizeJenis(string $jenis): string
    {
        if (! in_array($jenis, self::JENIS_VALID, true)) {
            throw new InvalidArgumentException('Jenis pernyataan tidak valid.');
        }

        return $jenis;
    }

    private function filename(Siswa $siswa, string $jenis): string
    {
        $nama = preg_replace('/[^A-Za-z0-9 _-]+/', '', $siswa->nama) ?: 'siswa';
        $suffix = $jenis === self::JENIS_PESERTA_DIDIK
            ? 'Surat Pernyataan Peserta Didik'
            : 'Pernyataan Biodata';

        return trim($nama).' - '.$suffix.'.pdf';
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
            ?: $rd?->nama_ayah_ijazah;

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
