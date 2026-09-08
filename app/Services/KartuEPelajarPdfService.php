<?php

namespace App\Services;

use App\Models\Madrasah;
use App\Models\Siswa;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class KartuEPelajarPdfService
{
    public function __construct(private KartuEPelajarService $kartu) {}

    public function stream(Siswa $siswa): Response
    {
        return $this->makePdf($siswa)->stream($this->filename($siswa));
    }

    public function download(Siswa $siswa): Response
    {
        return $this->makePdf($siswa)->download($this->filename($siswa));
    }

    private function makePdf(Siswa $siswa): \Barryvdh\DomPDF\PDF
    {
        $previous = ini_get('memory_limit');
        if ($this->memoryLimitBytes($previous) < 512 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }

        try {
            return Pdf::loadView('siswa.kartu-e-pelajar-pdf', $this->viewData($siswa))
                ->setPaper('a4', 'portrait');
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

    private function filename(Siswa $siswa): string
    {
        $nama = preg_replace('/[^A-Za-z0-9 _-]+/', '', $siswa->nama) ?: 'siswa';

        return trim($nama).' - Kartu E-Pelajar.pdf';
    }

    /**
     * @return array<string, mixed>
     */
    public function viewData(Siswa $siswa): array
    {
        $payload = $this->kartu->payload($siswa);
        $madrasah = Madrasah::saatIni();

        return [
            'siswa' => $siswa,
            'kartu' => $payload,
            'logoDataUri' => $this->r2DataUri($madrasah->logo_path, 96)
                ?? $this->assetDataUri(public_path('images/logo-madani.png'), 96),
            'logoKemenagDataUri' => $this->assetDataUri(public_path('img/logo-kemenag.png'), 96),
            'fotoDataUri' => $this->r2DataUri($siswa->foto, 180),
            'qrDataUri' => $this->qrDataUri($payload['verify_url']),
            'generatedAt' => now(),
        ];
    }

    private function qrDataUri(string $content): string
    {
        $png = (new Writer(new GDLibRenderer(120)))->writeString($content);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    private function r2DataUri(?string $path, int $maxWidth): ?string
    {
        if (! filled($path) || ! Storage::disk('r2')->exists($path)) {
            return null;
        }

        $bytes = Storage::disk('r2')->get($path);
        if ($bytes === null || $bytes === '') {
            return null;
        }

        return $this->resizedPngDataUri($bytes, $maxWidth);
    }

    private function assetDataUri(string $absolutePath, int $maxWidth): ?string
    {
        if (! is_file($absolutePath)) {
            return null;
        }

        $bytes = file_get_contents($absolutePath);
        if ($bytes === false || $bytes === '') {
            return null;
        }

        return $this->resizedPngDataUri($bytes, $maxWidth);
    }

    private function resizedPngDataUri(string $bytes, int $maxWidth): ?string
    {
        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if ($width < 1 || $height < 1) {
            imagedestroy($image);

            return null;
        }

        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) max(1, round($height * ($maxWidth / $width)));
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        ob_start();
        imagepng($image, null, 6);
        $png = ob_get_clean();
        imagedestroy($image);

        if ($png === false || $png === '') {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
