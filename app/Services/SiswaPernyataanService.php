<?php

namespace App\Services;

use App\Models\Siswa;
use App\Models\SiswaPernyataan;
use App\Support\KelengkapanSiswa;
use App\Support\PernyataanSiswa;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SiswaPernyataanService
{
    public function __construct(private PernyataanPdfService $pdf) {}

    public function pastikanWajibLengkap(Siswa $siswa): void
    {
        $kelengkapan = KelengkapanSiswa::ringkasan($siswa);
        if (! ($kelengkapan['wajib_semua_selesai'] ?? false)) {
            throw ValidationException::withMessages([
                'kelengkapan' => 'Lengkapi semua data wajib terlebih dahulu sebelum mengirim pernyataan.',
            ]);
        }
    }

    /**
     * @return array{ttd_siswa_data_uri: string, ttd_wali_data_uri: string, nama_wali: string, tanggal: CarbonInterface, bytes_siswa: string, bytes_wali: string}
     */
    public function siapkanTandaTangan(Siswa $siswa, string $ttdSiswaBase64, string $ttdWaliBase64): array
    {
        $bytesSiswa = $this->decodePng($ttdSiswaBase64, 'ttd_siswa');
        $bytesWali = $this->decodePng($ttdWaliBase64, 'ttd_wali');
        $namaWali = PernyataanSiswa::namaWaliEfektif($siswa);

        if ($namaWali === '') {
            throw ValidationException::withMessages([
                'nama_wali' => 'Nama orang tua/wali belum tersedia. Lengkapi data orang tua terlebih dahulu.',
            ]);
        }

        return [
            'ttd_siswa_data_uri' => 'data:image/png;base64,'.base64_encode($bytesSiswa),
            'ttd_wali_data_uri' => 'data:image/png;base64,'.base64_encode($bytesWali),
            'nama_wali' => $namaWali,
            'tanggal' => now(),
            'bytes_siswa' => $bytesSiswa,
            'bytes_wali' => $bytesWali,
        ];
    }

    public function simpan(Siswa $siswa, bool $setuju1, bool $setuju2, string $ttdSiswaBase64, string $ttdWaliBase64): SiswaPernyataan
    {
        $this->pastikanWajibLengkap($siswa);

        if (! $setuju1 || ! $setuju2) {
            throw ValidationException::withMessages([
                'setuju_poin_1' => 'Kedua pernyataan wajib disetujui.',
            ]);
        }

        $tanda = $this->siapkanTandaTangan($siswa, $ttdSiswaBase64, $ttdWaliBase64);
        $teks = PernyataanSiswa::teksAktif();
        $existing = $siswa->pernyataan;

        $pathSiswa = $this->simpanPng($siswa->id, 'siswa', $tanda['bytes_siswa']);
        $pathWali = $this->simpanPng($siswa->id, 'wali', $tanda['bytes_wali']);

        if ($existing) {
            $this->hapusLama($existing->ttd_siswa_path, $pathSiswa);
            $this->hapusLama($existing->ttd_wali_path, $pathWali);
            if (filled($existing->getAttribute('pdf_path'))) {
                Storage::disk('r2')->delete((string) $existing->getAttribute('pdf_path'));
            }
        }

        return SiswaPernyataan::query()->updateOrCreate(
            ['siswa_id' => $siswa->id],
            [
                'versi_teks' => $teks['versi'],
                'teks_poin_1' => $teks['poin_1'],
                'teks_poin_2' => $teks['poin_2'],
                'setuju_poin_1' => true,
                'setuju_poin_2' => true,
                'nama_siswa' => $siswa->nama,
                'nama_wali' => $tanda['nama_wali'],
                'ttd_siswa_path' => $pathSiswa,
                'ttd_wali_path' => $pathWali,
                'dikonfirmasi_at' => $tanda['tanggal'],
            ]
        );
    }

    private function decodePng(string $raw, string $field): string
    {
        $raw = trim($raw);
        if (str_starts_with($raw, 'data:image')) {
            $parts = explode(',', $raw, 2);
            $raw = $parts[1] ?? '';
        }

        $bytes = base64_decode($raw, true);
        if ($bytes === false || $bytes === '') {
            throw ValidationException::withMessages([
                $field => 'Tanda tangan tidak valid.',
            ]);
        }

        if (@imagecreatefromstring($bytes) === false) {
            throw ValidationException::withMessages([
                $field => 'Tanda tangan harus berupa gambar PNG/JPEG.',
            ]);
        }

        if (strlen($bytes) > 1024 * 1024) {
            throw ValidationException::withMessages([
                $field => 'Ukuran tanda tangan maksimal 1 MB.',
            ]);
        }

        return $bytes;
    }

    private function simpanPng(string $siswaId, string $peran, string $bytes): string
    {
        $path = 'siswa/'.$siswaId.'/pernyataan/'.now()->format('YmdHis').'-ttd-'.$peran.'.png';
        Storage::disk('r2')->put($path, $bytes, 'private');

        return $path;
    }

    private function hapusLama(?string $lama, string $baru): void
    {
        if (! $lama || $lama === $baru) {
            return;
        }

        Storage::disk('r2')->delete($lama);
    }
}
