<?php

namespace App\Services\Tunjangan;

use App\Models\TahunAjaran;
use App\Models\TunjanganDokumen;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class TunjanganZipImportService
{
    public function __construct(
        private TunjanganDokumenService $dokumen,
    ) {}

    /**
     * @return array{
     *     imported: int,
     *     missing: list<string>,
     *     invalid: list<string>,
     *     ambiguous: list<string>
     * }
     */
    public function impor(
        string $jenis,
        UploadedFile $zipFile,
        ?TahunAjaran $tahunAjaran = null,
        ?int $semester = null,
    ): array {
        if (! in_array($jenis, [TunjanganDokumen::JENIS_SKMT, TunjanganDokumen::JENIS_SKBK], true)) {
            throw ValidationException::withMessages(['zip' => 'Jenis ZIP tidak didukung.']);
        }

        if (strtolower($zipFile->getClientOriginalExtension()) !== 'zip') {
            throw ValidationException::withMessages(['zip' => 'File harus berformat .zip.']);
        }

        if ($jenis === TunjanganDokumen::JENIS_SKBK) {
            if ($tahunAjaran === null || ! in_array($semester, [1, 2], true)) {
                throw ValidationException::withMessages([
                    'tahun_ajaran_id' => 'Pilih tahun ajaran dan semester untuk impor SKBK.',
                ]);
            }
        }

        $tmp = $zipFile->getRealPath();
        if ($tmp === false) {
            throw ValidationException::withMessages(['zip' => 'ZIP tidak bisa dibaca.']);
        }

        $zip = new ZipArchive;
        if ($zip->open($tmp) !== true) {
            throw ValidationException::withMessages(['zip' => 'ZIP rusak atau tidak valid.']);
        }

        $imported = 0;
        $missing = [];
        $invalid = [];
        $ambiguous = [];

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

                if (! str_ends_with(strtolower($base), '.pdf')) {
                    $invalid[] = $base.' (bukan PDF)';

                    continue;
                }

                $parsed = $jenis === TunjanganDokumen::JENIS_SKMT
                    ? $this->parseSkmt($base)
                    : $this->parseSkbk($base);

                if ($parsed === null) {
                    $invalid[] = $base.' (pola nama tidak cocok)';

                    continue;
                }

                $ta = $tahunAjaran;
                $periode = $semester;

                if ($jenis === TunjanganDokumen::JENIS_SKMT) {
                    $ta = $this->dokumen->cariTahunAjaranByKodeTa($parsed['ta']);
                    if ($ta === null) {
                        $invalid[] = $base.' (tahun ajaran TA'.$parsed['ta'].' tidak unik/tidak ditemukan)';

                        continue;
                    }
                    $periode = $parsed['semester'];
                }

                $matches = $this->dokumen->cariGtkByNamaKey($parsed['nama_key']);
                if ($matches === []) {
                    $missing[] = $base;

                    continue;
                }
                if (count($matches) > 1) {
                    $ambiguous[] = $base;

                    continue;
                }

                $stream = $zip->getStream($name);
                if ($stream === false) {
                    $invalid[] = $base.' (tidak terbaca)';

                    continue;
                }

                $tmpPdf = tempnam(sys_get_temp_dir(), 'tunj_');
                if ($tmpPdf === false) {
                    fclose($stream);
                    $invalid[] = $base.' (temp gagal)';

                    continue;
                }

                $out = fopen($tmpPdf, 'wb');
                if ($out === false) {
                    fclose($stream);
                    @unlink($tmpPdf);
                    $invalid[] = $base.' (temp gagal)';

                    continue;
                }

                stream_copy_to_stream($stream, $out);
                fclose($stream);
                fclose($out);

                try {
                    $upload = new UploadedFile($tmpPdf, $base, 'application/pdf', null, true);
                    $this->dokumen->simpanPdf(
                        $matches[0],
                        $jenis,
                        (int) $periode,
                        $upload,
                        null,
                        $ta,
                        $base,
                        enforcePeriodeLock: false,
                    );
                    $imported++;
                } catch (ValidationException $e) {
                    $msg = collect($e->errors())->flatten()->first() ?: 'validasi gagal';
                    $invalid[] = $base.' ('.$msg.')';
                } finally {
                    @unlink($tmpPdf);
                }
            }
        } finally {
            $zip->close();
        }

        return compact('imported', 'missing', 'invalid', 'ambiguous');
    }

    /**
     * @return array{nama_key: string, ta: int, semester: int}|null
     */
    public function parseSkmt(string $filename): ?array
    {
        if (! preg_match('/^Rekap_Penilaian_SKMT_(.+)_TA(\d{4})_Sem([12])\.pdf$/i', $filename, $m)) {
            return null;
        }

        $namaKey = $this->dokumen->normalizeNamaKey($m[1]);
        if ($namaKey === '') {
            return null;
        }

        return [
            'nama_key' => $namaKey,
            'ta' => (int) $m[2],
            'semester' => (int) $m[3],
        ];
    }

    /**
     * @return array{nama_key: string}|null
     */
    public function parseSkbk(string $filename): ?array
    {
        if (! preg_match('/^SKBK_(.+)\.pdf$/i', $filename, $m)) {
            return null;
        }

        $namaKey = $this->dokumen->normalizeNamaKey($m[1]);
        if ($namaKey === '') {
            return null;
        }

        return ['nama_key' => $namaKey];
    }
}
