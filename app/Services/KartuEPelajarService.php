<?php

namespace App\Services;

use App\Models\Madrasah;
use App\Models\Siswa;
use App\Models\SiswaPeriodik;
use App\Support\R2Url;
use Illuminate\Support\Facades\URL;

class KartuEPelajarService
{
    /**
     * @return array{
     *     nama: string,
     *     nisn: ?string,
     *     nis: ?string,
     *     ttl: string,
     *     jenis_kelamin: ?string,
     *     jenis_kelamin_label: string,
     *     alamat: string,
     *     foto_url: ?string,
     *     verify_url: string,
     *     madrasah: array{
     *         nama: string,
     *         nama_singkat: string,
     *         alamat: string,
     *         kontak: string,
     *         instansi_1: string,
     *         instansi_2: string,
     *         logo_url: ?string,
     *         logo_kemenag_url: string
     *     }
     * }
     */
    public function payload(Siswa $siswa): array
    {
        $madrasah = Madrasah::saatIni();
        $periodik = $siswa->periodikAktif();

        return [
            'nama' => (string) $siswa->nama,
            'nisn' => $siswa->nisn,
            'nis' => $siswa->nis,
            'ttl' => $this->formatTtl($siswa),
            'jenis_kelamin' => $siswa->jenis_kelamin,
            'jenis_kelamin_label' => $this->formatJenisKelamin($siswa->jenis_kelamin),
            'alamat' => $this->formatAlamatKartu($periodik) ?: '—',
            'foto_url' => R2Url::temporary($siswa->foto),
            'verify_url' => URL::signedRoute('kartu-e-pelajar.cek', ['siswa' => $siswa->id]),
            'madrasah' => [
                'nama' => $madrasah->namaKop(),
                'nama_singkat' => (string) $madrasah->nama,
                'alamat' => $this->formatAlamatMadrasah($madrasah),
                'kontak' => $this->formatKontakMadrasah($madrasah),
                'instansi_1' => 'KEMENTERIAN AGAMA REPUBLIK INDONESIA',
                'instansi_2' => 'KANTOR KEMENTERIAN AGAMA KABUPATEN MAJALENGKA',
                'logo_url' => $madrasah->urlLogo(),
                'logo_kemenag_url' => asset('img/logo-kemenag.png'),
            ],
        ];
    }

    private function formatTtl(Siswa $siswa): string
    {
        $tempat = filled($siswa->tempat_lahir) ? mb_strtoupper((string) $siswa->tempat_lahir) : null;
        $tanggal = $siswa->tanggal_lahir?->locale('id')->translatedFormat('d F Y');
        $tanggal = $tanggal ? mb_strtoupper($tanggal) : null;

        if ($tempat && $tanggal) {
            return $tempat.', '.$tanggal;
        }

        return $tempat ?: ($tanggal ?: '—');
    }

    private function formatJenisKelamin(?string $jk): string
    {
        return match ($jk) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => filled($jk) ? (string) $jk : '—',
        };
    }

    private function formatAlamatKartu(?SiswaPeriodik $periodik): ?string
    {
        if (! $periodik) {
            return null;
        }

        $parts = array_filter([
            filled($periodik->desa) ? 'DESA '.mb_strtoupper((string) $periodik->desa) : null,
            filled($periodik->kecamatan) ? 'KEC. '.mb_strtoupper((string) $periodik->kecamatan) : null,
            filled($periodik->kota) ? 'KAB. '.mb_strtoupper((string) $periodik->kota) : null,
        ]);

        if ($parts !== []) {
            return implode(' ', $parts);
        }

        $fallback = array_filter([
            $periodik->alamat,
            filled($periodik->blok) ? 'Blok '.$periodik->blok : null,
            $periodik->desa,
            $periodik->kecamatan,
            $periodik->kota,
        ], fn ($v) => filled($v));

        return $fallback === [] ? null : implode(', ', $fallback);
    }

    private function formatAlamatMadrasah(Madrasah $madrasah): string
    {
        // Samakan gaya kop surat: alamat jalan + wilayah ringkas, tanpa dobel.
        $parts = [];
        if (filled($madrasah->alamat)) {
            $parts[] = (string) $madrasah->alamat;
        }
        $wilayah = array_filter([
            filled($madrasah->kecamatan) ? 'Kec. '.$madrasah->kecamatan : null,
            filled($madrasah->kota) ? 'Kab. '.$madrasah->kota : null,
            $madrasah->provinsi,
            $madrasah->kode_pos,
        ], fn ($v) => filled($v));
        if ($wilayah !== []) {
            $parts[] = implode(', ', $wilayah);
        }

        $line = trim(implode(' ', $parts));

        return $line !== '' ? $line : (string) config('madrasah.alamat', '');
    }

    private function formatKontakMadrasah(Madrasah $madrasah): string
    {
        return collect([
            filled($madrasah->telepon) ? 'Telp. '.$madrasah->telepon : null,
            filled($madrasah->email) ? 'E-mail: '.$madrasah->email : null,
        ])->filter()->implode(' ');
    }
}
