<?php

namespace App\Services\Persuratan;

use App\Models\Gtk;
use App\Models\Madrasah;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class SptjmTpgPdfService
{
    public function download(Gtk $gtk): Response
    {
        $data = $this->viewData($gtk);
        $filename = $this->filename($gtk);

        return Pdf::loadView('persuratan.sptjm-tpg.pdf', $data)
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function stream(Gtk $gtk): Response
    {
        $data = $this->viewData($gtk);

        return Pdf::loadView('persuratan.sptjm-tpg.pdf', $data)
            ->setPaper('a4', 'portrait')
            ->stream($this->filename($gtk));
    }

    /**
     * @return array{
     *     gtk: Gtk,
     *     namaLengkap: string,
     *     nuptk: string,
     *     nrg: string,
     *     tempatTugas: string,
     *     alamatTempatTugas: string,
     *     tanggalSurat: string,
     *     kotaTtd: string
     * }
     */
    public function viewData(Gtk $gtk): array
    {
        $madrasah = Madrasah::saatIni();
        $tanggal = now()->timezone(config('app.timezone'))->locale('id');

        return [
            'gtk' => $gtk,
            'namaLengkap' => $gtk->nama_lengkap,
            'nuptk' => filled($gtk->nuptk) ? (string) $gtk->nuptk : '—',
            'nrg' => filled($gtk->nrg) ? (string) $gtk->nrg : '—',
            'tempatTugas' => (string) ($madrasah->nama ?: config('madrasah.nama')),
            'alamatTempatTugas' => $this->formatAlamatTempatTugas($madrasah),
            'tanggalSurat' => $tanggal->translatedFormat('d F Y'),
            'kotaTtd' => (string) ($madrasah->kota ?: 'Majalengka'),
        ];
    }

    public function formatAlamatTempatTugas(Madrasah $madrasah): string
    {
        $alamat = trim((string) ($madrasah->alamat ?? ''));
        $haystack = mb_strtolower($alamat);

        $extras = [];
        foreach ([
            filled($madrasah->desa) ? ['Desa '.$madrasah->desa, (string) $madrasah->desa] : null,
            filled($madrasah->kecamatan) ? ['Kec. '.$madrasah->kecamatan, (string) $madrasah->kecamatan] : null,
            filled($madrasah->kota) ? ['Kab. '.$madrasah->kota, (string) $madrasah->kota] : null,
        ] as $pair) {
            if ($pair === null) {
                continue;
            }

            [$label, $needle] = $pair;
            if ($needle !== '' && ! str_contains($haystack, mb_strtolower($needle))) {
                $extras[] = $label;
            }
        }

        $line = trim($alamat.' '.implode(' ', $extras));

        return $line !== '' ? $line : '—';
    }

    private function filename(Gtk $gtk): string
    {
        $nama = preg_replace('/[^A-Za-z0-9 _-]+/', '', $gtk->nama) ?: 'pegawai';

        return trim($nama).' - SPTJM TPG.pdf';
    }
}
