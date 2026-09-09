<?php

namespace App\Services;

use App\Models\Madrasah;
use App\Models\Siswa;
use BaconQrCode\Common\ErrorCorrectionLevel;
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
        $previous = ini_get('memory_limit');
        if ($this->memoryLimitBytes($previous) < 512 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }

        try {
            $payload = $this->kartu->payload($siswa);
            $madrasah = Madrasah::saatIni();
            // Sumber asli, di-scale tajam (~280px) — bukan aset kartu kecil yang blur.
            $logoBrandMadani = $this->assetDataUri(public_path('images/logo-madani.png'), 320)
                ?? $this->rawAssetDataUri(public_path('img/logo-madani-wordmark-kartu.png'));
            $logoBrandOnDark = $this->assetDataUri(public_path('images/logo-madani.png'), 320)
                ?? $this->rawAssetDataUri(public_path('img/logo-madani-wordmark-on-emerald.png'))
                ?? $logoBrandMadani;
            $logoMadrasah = $this->r2DataUri($madrasah->logo_path, 280) ?? $logoBrandOnDark;

            return [
                'siswa' => $siswa,
                'kartu' => $payload,
                'logoDataUri' => $logoMadrasah,
                'logoKemenagDataUri' => $this->assetDataUri(public_path('img/logo-kemenag.png'), 280)
                    ?? $this->rawAssetDataUri(public_path('img/logo-kemenag-kartu.png')),
                'logoMadaniDataUri' => $logoBrandMadani,
                'fotoPlaceholderDataUri' => $this->rawAssetDataUri(public_path('img/foto-placeholder-kartu.png')),
                'bgBelakangDataUri' => $this->rawAssetDataUri(public_path('img/bg-kartu-belakang-wash.jpg'), 'image/jpeg')
                    ?? $this->washedBackgroundDataUri(public_path('img/bg-kartu-belakang-kartu.jpg'))
                    ?? $this->washedBackgroundDataUri(public_path('img/bg-kartu-belakang.jpg')),
                'fotoDataUri' => $this->r2DataUri($siswa->foto, 220),
                'qrDataUri' => $this->qrDataUri($payload['verify_url']),
                'generatedAt' => now(),
            ];
        } finally {
            if (is_string($previous) && $previous !== '') {
                ini_set('memory_limit', $previous);
            }
        }
    }

    private function rawAssetDataUri(string $absolutePath, string $mime = 'image/png'): ?string
    {
        if (! is_file($absolutePath)) {
            return null;
        }

        $bytes = file_get_contents($absolutePath);
        if ($bytes === false || $bytes === '') {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }

    private function qrDataUri(string $content): string
    {
        // Resolusi tinggi + quiet zone (margin) agar modul tidak “menyatu” saat dicetak kecil.
        $png = (new Writer(new GDLibRenderer(size: 280, margin: 2)))
            ->writeString($content, ecLevel: ErrorCorrectionLevel::L());

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

    private function assetJpegDataUri(string $absolutePath, int $maxWidth): ?string
    {
        if (! is_file($absolutePath)) {
            return null;
        }

        $bytes = file_get_contents($absolutePath);
        if ($bytes === false || $bytes === '') {
            return null;
        }

        return $this->resizedJpegDataUri($bytes, $maxWidth);
    }

    /**
     * Mirror Ta'lim: drone image (~48% opacity) + white wash (~55%).
     */
    private function washedBackgroundDataUri(string $absolutePath): ?string
    {
        if (! is_file($absolutePath)) {
            return null;
        }

        $bytes = file_get_contents($absolutePath);
        if ($bytes === false || $bytes === '') {
            return null;
        }

        $drone = @imagecreatefromstring($bytes);
        if ($drone === false) {
            return null;
        }

        $drone = $this->scaleImage($drone, 520);
        if ($drone === null) {
            return null;
        }

        $width = imagesx($drone);
        $height = imagesy($drone);
        $out = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($out, 255, 255, 255);
        imagefilledrectangle($out, 0, 0, $width, $height, $white);
        imagecopymerge($out, $drone, 0, 0, 0, 0, $width, $height, 48);
        imagedestroy($drone);

        $wash = imagecreatetruecolor($width, $height);
        imagefilledrectangle($wash, 0, 0, $width, $height, imagecolorallocate($wash, 255, 255, 255));
        imagecopymerge($out, $wash, 0, 0, 0, 0, $width, $height, 55);
        imagedestroy($wash);

        ob_start();
        imagejpeg($out, null, 78);
        $jpeg = ob_get_clean();
        imagedestroy($out);

        if ($jpeg === false || $jpeg === '') {
            return null;
        }

        return 'data:image/jpeg;base64,'.base64_encode($jpeg);
    }

    private function resizedPngDataUri(string $bytes, int $maxWidth): ?string
    {
        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            return null;
        }

        $image = $this->scaleImage($image, $maxWidth);
        if ($image === null) {
            return null;
        }

        ob_start();
        imagepng($image, null, 3);
        $png = ob_get_clean();
        imagedestroy($image);

        if ($png === false || $png === '') {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode($png);
    }

    private function resizedJpegDataUri(string $bytes, int $maxWidth): ?string
    {
        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            return null;
        }

        $image = $this->scaleImage($image, $maxWidth);
        if ($image === null) {
            return null;
        }

        ob_start();
        imagejpeg($image, null, 72);
        $jpeg = ob_get_clean();
        imagedestroy($image);

        if ($jpeg === false || $jpeg === '') {
            return null;
        }

        return 'data:image/jpeg;base64,'.base64_encode($jpeg);
    }

    private function scaleImage(\GdImage $image, int $maxWidth): ?\GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        if ($width < 1 || $height < 1) {
            imagedestroy($image);

            return null;
        }

        if ($width <= $maxWidth) {
            return $image;
        }

        $newWidth = $maxWidth;
        $newHeight = (int) max(1, round($height * ($maxWidth / $width)));
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }
}
