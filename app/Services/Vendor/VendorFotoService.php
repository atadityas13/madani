<?php

namespace App\Services\Vendor;

use App\Models\Siswa;
use App\Models\VendorJob;
use App\Support\R2Url;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class VendorFotoService
{
    public const MAX_KB = 500;

    public const MIN_WIDTH = 300;

    public const RATIO = 3 / 4;

    public const RATIO_TOLERANCE = 0.02;

    public function simpanDariUpload(Siswa $siswa, UploadedFile $file): void
    {
        $this->assertValidImage($file);

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'jpg';
        $lama = $siswa->foto;
        $path = $file->storeAs("foto/{$siswa->id}", 'profil.'.$ext, 'r2');
        $siswa->update(['foto' => $path]);

        if ($lama && $lama !== $path) {
            Storage::disk('r2')->delete($lama);
        }
    }

    /**
     * @return array{
     *     imported: int,
     *     skipped: list<string>,
     *     missing: list<string>,
     *     invalid: list<string>
     * }
     */
    public function imporZip(VendorJob $job, UploadedFile $zipFile): array
    {
        if (strtolower($zipFile->getClientOriginalExtension()) !== 'zip') {
            throw ValidationException::withMessages(['zip' => 'File harus berformat .zip.']);
        }

        $tmp = $zipFile->getRealPath();
        if ($tmp === false) {
            throw ValidationException::withMessages(['zip' => 'File ZIP tidak bisa dibaca.']);
        }

        $zip = new ZipArchive;
        if ($zip->open($tmp) !== true) {
            throw ValidationException::withMessages(['zip' => 'ZIP rusak atau tidak valid.']);
        }

        $siswaByNisn = $job->siswas()
            ->get(['siswas.id', 'siswas.nisn', 'siswas.foto'])
            ->filter(fn (Siswa $s) => filled($s->nisn))
            ->keyBy(fn (Siswa $s) => (string) $s->nisn);

        $imported = 0;
        $skipped = [];
        $missing = [];
        $invalid = [];
        $seen = [];

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if ($name === false || str_ends_with($name, '/')) {
                    continue;
                }

                $base = basename(str_replace('\\', '/', $name));
                if ($base === '' || str_starts_with($base, '.')) {
                    continue;
                }

                $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
                if (! in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                    $invalid[] = $base.' (bukan jpg/png)';

                    continue;
                }

                $nisn = pathinfo($base, PATHINFO_FILENAME);
                $nisn = preg_replace('/\s+/', '', (string) $nisn) ?: '';
                if ($nisn === '') {
                    $invalid[] = $base.' (NISN kosong)';

                    continue;
                }

                if (isset($seen[$nisn])) {
                    $skipped[] = $nisn.' (duplikat di ZIP)';

                    continue;
                }
                $seen[$nisn] = true;

                /** @var Siswa|null $siswa */
                $siswa = $siswaByNisn->get($nisn);
                if ($siswa === null) {
                    $missing[] = $nisn;

                    continue;
                }

                $stream = $zip->getStream($name);
                if ($stream === false) {
                    $invalid[] = $base.' (tidak terbaca)';

                    continue;
                }

                $tmpImg = tempnam(sys_get_temp_dir(), 'vfoto_');
                if ($tmpImg === false) {
                    fclose($stream);
                    $invalid[] = $base.' (temp gagal)';

                    continue;
                }

                $out = fopen($tmpImg, 'wb');
                if ($out === false) {
                    fclose($stream);
                    @unlink($tmpImg);
                    $invalid[] = $base.' (temp gagal)';

                    continue;
                }

                stream_copy_to_stream($stream, $out);
                fclose($stream);
                fclose($out);

                try {
                    $upload = new UploadedFile($tmpImg, $base, mime_content_type($tmpImg) ?: null, null, true);
                    $this->assertValidImage($upload);
                    $this->simpanDariUpload($siswa, $upload);
                    $imported++;
                } catch (ValidationException $e) {
                    $msg = collect($e->errors())->flatten()->first() ?: 'validasi gagal';
                    $invalid[] = $nisn.' ('.$msg.')';
                } finally {
                    @unlink($tmpImg);
                }
            }
        } finally {
            $zip->close();
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'missing' => $missing,
            'invalid' => $invalid,
        ];
    }

    public function assertValidImage(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages(['foto' => 'File foto tidak valid.']);
        }

        $mime = (string) $file->getMimeType();
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/jpg'], true)) {
            throw ValidationException::withMessages(['foto' => 'Foto harus JPG atau PNG.']);
        }

        $kb = (int) ceil($file->getSize() / 1024);
        if ($kb > self::MAX_KB) {
            throw ValidationException::withMessages([
                'foto' => 'Ukuran foto maksimal '.self::MAX_KB.' KB (sekarang '.$kb.' KB).',
            ]);
        }

        $info = @getimagesize($file->getRealPath() ?: '');
        if ($info === false) {
            throw ValidationException::withMessages(['foto' => 'File gambar tidak bisa dibaca.']);
        }

        [$width, $height] = $info;
        if ($width < self::MIN_WIDTH) {
            throw ValidationException::withMessages([
                'foto' => 'Lebar foto minimal '.self::MIN_WIDTH.' piksel.',
            ]);
        }

        $ratio = $width / max($height, 1);
        if (abs($ratio - self::RATIO) > self::RATIO_TOLERANCE) {
            throw ValidationException::withMessages([
                'foto' => 'Rasio foto harus 3:4 (contoh 300×400).',
            ]);
        }
    }

    public function thumbnailUrl(?Siswa $siswa): ?string
    {
        if ($siswa === null || ! filled($siswa->foto)) {
            return null;
        }

        return R2Url::temporary((string) $siswa->foto);
    }

    public function jobMemilikiSiswa(VendorJob $job, Siswa $siswa): bool
    {
        return $job->siswas()->where('siswas.id', $siswa->id)->exists();
    }
}
