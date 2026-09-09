<?php

namespace App\Services;

use App\Models\Madrasah;
use App\Models\Siswa;
use App\Models\SiswaPeriodik;
use App\Support\R2Url;

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
            'foto_url' => R2Url::readable($siswa->foto),
            'verify_url' => $this->verifyUrl($siswa),
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

    /**
     * URL pendek untuk QR kartu (tanpa query signature panjang).
     * Signed URL Laravel terlalu panjang → QR padat → sulit discan di ukuran ID-1.
     */
    public function verifyUrl(Siswa $siswa): string
    {
        return url('/k/'.$siswa->id.'/'.$this->verifySignature((string) $siswa->id));
    }

    public function verifySignature(string $siswaId): string
    {
        return substr(hash_hmac('sha256', 'kartu-e-pelajar:'.$siswaId, (string) config('app.key')), 0, 12);
    }

    public function signatureValid(string $siswaId, string $signature): bool
    {
        return hash_equals($this->verifySignature($siswaId), $signature);
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

        $base = trim((string) ($periodik->alamat ?? ''));
        $haystack = mb_strtolower(preg_replace('/\s+/', ' ', $base) ?? $base);

        // Jika kolom alamat sudah berisi format lengkap Madani, jangan dobel Blok/RT/Desa.
        if ($base !== '' && (
            str_contains($haystack, 'blok')
            || str_contains($haystack, 'rt')
            || str_contains($haystack, 'desa')
            || str_contains($haystack, 'kec')
        )) {
            $segments = [$base];
            foreach ([
                filled($periodik->kota) ? [(string) $periodik->kota, (string) $periodik->kota] : null,
                filled($periodik->provinsi) ? [(string) $periodik->provinsi, (string) $periodik->provinsi] : null,
                filled($periodik->kode_pos) ? [(string) $periodik->kode_pos, (string) $periodik->kode_pos] : null,
            ] as $pair) {
                if ($pair === null) {
                    continue;
                }
                [$label, $needle] = $pair;
                $needle = mb_strtolower(trim($needle));
                if ($needle === '' || str_contains($haystack, $needle)) {
                    continue;
                }
                $segments[] = $label;
                $haystack .= ' '.$needle;
            }

            return implode(', ', $segments);
        }

        $parts = array_filter([
            $base !== '' ? $base : null,
            filled($periodik->blok) ? 'Blok '.$periodik->blok : null,
            filled($periodik->rt) || filled($periodik->rw)
                ? 'RT '.($periodik->rt ?: '-').'/RW '.($periodik->rw ?: '-')
                : null,
            $periodik->desa,
            filled($periodik->kecamatan) ? 'Kec. '.$periodik->kecamatan : null,
            $periodik->kota,
            $periodik->provinsi,
            $periodik->kode_pos,
        ], fn ($v) => filled($v));

        return $parts === [] ? null : implode(', ', $parts);
    }

    private function formatAlamatMadrasah(Madrasah $madrasah): string
    {
        $alamat = trim((string) ($madrasah->alamat ?? ''));
        $haystack = mb_strtolower(preg_replace('/\s+/', ' ', $alamat) ?? $alamat);

        $segments = [];
        if ($alamat !== '') {
            $segments[] = $alamat;
        }

        foreach ([
            filled($madrasah->desa) ? ['Desa '.$madrasah->desa, (string) $madrasah->desa] : null,
            filled($madrasah->kecamatan) ? ['Kec. '.$madrasah->kecamatan, (string) $madrasah->kecamatan] : null,
            filled($madrasah->kota) ? ['Kab. '.$madrasah->kota, (string) $madrasah->kota] : null,
            filled($madrasah->provinsi) ? [(string) $madrasah->provinsi, (string) $madrasah->provinsi] : null,
            filled($madrasah->kode_pos) ? [(string) $madrasah->kode_pos, (string) $madrasah->kode_pos] : null,
        ] as $pair) {
            if ($pair === null) {
                continue;
            }
            [$label, $needle] = $pair;
            $needle = mb_strtolower(trim($needle));
            if ($needle === '' || str_contains($haystack, $needle)) {
                continue;
            }
            $segments[] = $label;
            $haystack .= ' '.$needle;
        }

        $line = trim(implode(', ', $segments));

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
