<?php

namespace App\Services;

use App\Models\IzinSiswa;
use App\Support\SuratIzinSiswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Symfony\Component\HttpFoundation\Response;

class SuratIzinSiswaPdfService
{
    public function stream(IzinSiswa $izin): Response
    {
        $name = $this->filename($izin);

        return response($this->raw($izin), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$name.'"',
        ]);
    }

    public function download(IzinSiswa $izin): Response
    {
        $name = $this->filename($izin);

        return response($this->raw($izin), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }

    public function raw(IzinSiswa $izin): string
    {
        $izin->loadMissing(['siswa', 'rombel']);
        $payload = SuratIzinSiswa::payload($izin);
        $ttdDataUri = $this->r2DataUri($izin->ttd_wali_path);

        $suratPdf = Pdf::loadView('izin.surat-pdf', [
            'kota' => $payload['kota'],
            'tanggalSurat' => $payload['tanggal_surat'],
            'hal' => $payload['hal'],
            'lampiranLabel' => $payload['lampiran_label'],
            'kepadaYth' => $payload['kepada_yth'],
            'waliKelas' => $payload['wali_kelas'],
            'madrasah' => $payload['madrasah'],
            'diTempat' => $payload['di_tempat'],
            'salamPembuka' => $payload['salam_pembuka'],
            'pengantar' => $payload['pengantar'],
            'namaSiswa' => $payload['nama_siswa'],
            'kelasSiswa' => $payload['kelas_siswa'],
            'paragraf' => $payload['paragraf'],
            'penutup' => $payload['penutup'],
            'salamPenutup' => $payload['salam_penutup'],
            'hormatKami' => $payload['hormat_kami'],
            'peranPenandatangan' => $payload['peran_penandatangan'],
            'namaWali' => $payload['nama_wali'],
            'ttdWaliDataUri' => $ttdDataUri,
        ])->setPaper('a4', 'portrait')->output();

        if (! filled($izin->lampiran_path)) {
            return $suratPdf;
        }

        $lampiranBytes = Storage::disk('r2')->get($izin->lampiran_path);
        if ($lampiranBytes === null || $lampiranBytes === '') {
            return $suratPdf;
        }

        if (str_starts_with($lampiranBytes, '%PDF')) {
            return $this->mergePdfs([$suratPdf, $lampiranBytes]);
        }

        $mime = Storage::disk('r2')->mimeType($izin->lampiran_path) ?: 'image/jpeg';
        if (! str_starts_with($mime, 'image/')) {
            $mime = 'image/jpeg';
        }

        $lampiranPdf = Pdf::loadView('izin.lampiran-pdf', [
            'imageDataUri' => 'data:'.$mime.';base64,'.base64_encode($lampiranBytes),
            'jenisBukti' => $payload['jenis_bukti'],
        ])->setPaper('a4', 'portrait')->output();

        return $this->mergePdfs([$suratPdf, $lampiranPdf]);
    }

    /**
     * @param  list<string>  $binaries
     */
    private function mergePdfs(array $binaries): string
    {
        $pdf = new Fpdi;
        foreach ($binaries as $binary) {
            if ($binary === '') {
                continue;
            }
            $pageCount = $pdf->setSourceFile(StreamReader::createByString($binary));
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }
        }

        return $pdf->Output('S');
    }

    private function filename(IzinSiswa $izin): string
    {
        $nama = preg_replace('/[^A-Za-z0-9 _-]+/', '', (string) ($izin->siswa?->nama ?? 'siswa')) ?: 'siswa';
        $tanggal = $izin->tanggal?->format('Ymd') ?? now()->format('Ymd');

        return trim($nama).' - Surat Izin '.$tanggal.'.pdf';
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
