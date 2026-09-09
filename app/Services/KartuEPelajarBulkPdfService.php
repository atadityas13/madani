<?php

namespace App\Services;

use App\Models\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class KartuEPelajarBulkPdfService
{
    /**
     * Baris orang (depan|belakang) per halaman A4.
     *
     * ID-1 tinggi 152.98pt + jarak antar baris; margin @page 28pt → usable ≈786pt.
     * 5 baris overflow (DomPDF pecah jadi 4+1 selang-seling); 4 baris muat penuh.
     */
    public const PER_HALAMAN = 4;

    public function __construct(private KartuEPelajarPdfService $kartuPdf) {}

    /**
     * @param  Collection<int, Siswa>  $siswas
     */
    public function stream(Collection $siswas): Response
    {
        return $this->makePdf($siswas)->stream('Kartu E-Pelajar Massal.pdf');
    }

    /**
     * @param  Collection<int, Siswa>  $siswas
     */
    public function download(Collection $siswas): Response
    {
        return $this->makePdf($siswas)->download('Kartu E-Pelajar Massal.pdf');
    }

    /**
     * @param  Collection<int, Siswa>  $siswas
     */
    private function makePdf(Collection $siswas): \Barryvdh\DomPDF\PDF
    {
        $previous = ini_get('memory_limit');
        if ($this->memoryLimitBytes($previous) < 512 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }

        try {
            $rows = $siswas
                ->filter(fn (Siswa $siswa) => filled($siswa->foto))
                ->values()
                ->map(fn (Siswa $siswa) => $this->kartuPdf->viewData($siswa));

            $pages = $rows->chunk(self::PER_HALAMAN)->values();

            return Pdf::loadView('siswa.kartu-e-pelajar-bulk-pdf', [
                'pages' => $pages,
            ])->setPaper('a4', 'portrait');
        } finally {
            if (is_string($previous) && $previous !== '') {
                ini_set('memory_limit', $previous);
            }
        }
    }

    private function memoryLimitBytes(string|false $limit): int
    {
        if ($limit === false || $limit === '' || $limit === '-1') {
            return PHP_INT_MAX;
        }

        $value = (int) $limit;
        $unit = strtolower(substr(trim($limit), -1));

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => (int) $limit,
        };
    }
}
