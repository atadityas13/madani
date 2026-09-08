<?php

namespace App\Services\Manajemen;

use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiswaExcelImportService
{
    public const HEADERS = [
        'No',
        'Nama lengkap',
        'NIS',
        'NISN',
        'NIK',
        'Tempat lahir',
        'Tanggal lahir',
        'Jenis kelamin',
        'Angkatan',
        'Nama rombel',
        'Nama Ayah kandung',
        'Nama Ibu kandung',
    ];

    public function unduhTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('siswa');

        $sheet->fromArray([self::HEADERS], null, 'A1');
        $sheet->fromArray([
            [1, 'Contoh Siswa', '123', '1234567890', '3210230911120003', 'Majalengka', '2012-11-09', 'Laki-laki', 'VII', '1', 'Ayah Contoh', 'Ibu Contoh'],
        ], null, 'A2');

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, 'template-impor-siswa.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return array{imported: int, pesan: string}
     */
    public function impor(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'File Excel kosong.',
            ]);
        }

        $this->assertHeaders(array_map(fn ($cell) => trim((string) $cell), $rows[0] ?? []));

        $parsed = [];
        $errors = [];
        $nisnInFile = [];
        $nikInFile = [];

        foreach (array_slice($rows, 1) as $offset => $raw) {
            $excelRow = $offset + 2;
            if ($this->barisKosong($raw)) {
                continue;
            }

            try {
                $row = $this->parseRow($raw, $excelRow);
            } catch (ValidationException $e) {
                foreach ($e->errors() as $messages) {
                    foreach ($messages as $message) {
                        $errors[] = "Baris {$excelRow}: {$message}";
                    }
                }

                continue;
            }

            if (isset($nisnInFile[$row['nisn']])) {
                $errors[] = "Baris {$excelRow}: NISN {$row['nisn']} duplikat di file (juga di baris {$nisnInFile[$row['nisn']]}).";

                continue;
            }

            if (isset($nikInFile[$row['nik']])) {
                $errors[] = "Baris {$excelRow}: NIK {$row['nik']} duplikat di file (juga di baris {$nikInFile[$row['nik']]}).";

                continue;
            }

            $nisnInFile[$row['nisn']] = $excelRow;
            $nikInFile[$row['nik']] = $excelRow;
            $parsed[] = $row;
        }

        if ($parsed === [] && $errors === []) {
            throw ValidationException::withMessages([
                'file' => 'Tidak ada baris data untuk diimpor.',
            ]);
        }

        $tahun = TahunAjaran::aktif();
        $rombels = [];

        if ($tahun) {
            $rombels = Rombel::query()
                ->where('tahun_ajaran_id', $tahun->id)
                ->get()
                ->groupBy(fn (Rombel $rombel) => strtoupper((string) $rombel->tingkat).'|'.trim((string) $rombel->nama));
        }

        foreach ($parsed as $row) {
            if (Siswa::query()->where('nisn', $row['nisn'])->exists()) {
                $errors[] = "Baris {$row['excel_row']}: NISN {$row['nisn']} sudah ada di database.";
            }

            if (Siswa::query()->where('nik', $row['nik'])->exists()) {
                $errors[] = "Baris {$row['excel_row']}: NIK {$row['nik']} sudah ada di database.";
            }

            if ($row['rombel_nama'] !== null) {
                if (! $tahun) {
                    $errors[] = "Baris {$row['excel_row']}: Tidak ada tahun ajaran aktif untuk penempatan rombel.";

                    continue;
                }

                $key = $row['angkatan'].'|'.$row['rombel_nama'];
                if (! isset($rombels[$key])) {
                    $errors[] = "Baris {$row['excel_row']}: Rombel {$row['angkatan']}-{$row['rombel_nama']} tidak ditemukan pada tahun ajaran aktif.";
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'file' => array_slice($errors, 0, 20),
            ]);
        }

        $imported = 0;

        DB::transaction(function () use ($parsed, $rombels, &$imported): void {
            foreach ($parsed as $row) {
                $punyaRombel = $row['rombel_nama'] !== null;
                $rombel = $punyaRombel
                    ? $rombels[$row['angkatan'].'|'.$row['rombel_nama']]->first()
                    : null;

                $siswa = Siswa::query()->create([
                    'nama' => $row['nama'],
                    'nis' => $row['nis'],
                    'nisn' => $row['nisn'],
                    'punya_nisn' => true,
                    'nik' => $row['nik'],
                    'punya_nik' => true,
                    'tempat_lahir' => $row['tempat_lahir'],
                    'tanggal_lahir' => $row['tanggal_lahir'],
                    'jenis_kelamin' => $row['jenis_kelamin'],
                    'angkatan' => $row['angkatan'],
                    'agama' => 'Islam',
                    'status_keaktifan' => $rombel ? 'aktif' : 'aktif_tanpa_rombel',
                    'tidak_punya_hp' => true,
                    'tidak_punya_email' => true,
                ]);

                $siswa->ensurePasswordAwal();
                $siswa->save();

                $siswa->orangTuas()->create(['peran' => 'ayah', 'nama' => $row['ayah_nama']]);
                $siswa->orangTuas()->create(['peran' => 'ibu', 'nama' => $row['ibu_nama']]);
                $siswa->orangTuas()->create(['peran' => 'wali']);

                if ($rombel) {
                    $rombel->siswas()->syncWithoutDetaching([
                        $siswa->id => ['status' => 'aktif'],
                    ]);
                }

                $imported++;
            }
        });

        return [
            'imported' => $imported,
            'pesan' => "Impor siswa berhasil: {$imported} baris.",
        ];
    }

    /**
     * @param  list<string>  $headers
     */
    private function assertHeaders(array $headers): void
    {
        $expected = self::HEADERS;
        $actual = array_slice($headers, 0, count($expected));

        if ($actual !== $expected) {
            throw ValidationException::withMessages([
                'file' => 'Header Excel tidak sesuai template. Unduh template terbaru lalu isi ulang.',
            ]);
        }
    }

    /**
     * @param  list<mixed>  $raw
     */
    private function barisKosong(array $raw): bool
    {
        foreach (array_slice($raw, 1, 11) as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<mixed>  $raw
     * @return array{
     *     excel_row: int,
     *     nama: string,
     *     nis: ?string,
     *     nisn: string,
     *     nik: string,
     *     tempat_lahir: string,
     *     tanggal_lahir: string,
     *     jenis_kelamin: string,
     *     angkatan: string,
     *     rombel_nama: ?string,
     *     ayah_nama: ?string,
     *     ibu_nama: string
     * }
     */
    private function parseRow(array $raw, int $excelRow): array
    {
        $get = fn (int $index): string => trim((string) ($raw[$index] ?? ''));

        $nama = $get(1);
        $nis = $this->hanyaDigit($get(2));
        $nisn = $this->hanyaDigit($get(3));
        $nik = $this->normalisasiNik($raw[4] ?? '');
        $tempatLahir = $get(5);
        $tanggalLahir = $this->normalisasiTanggal($raw[6] ?? null);
        $jenisKelamin = $this->normalisasiJenisKelamin($get(7));
        $angkatan = strtoupper($get(8));
        $rombelNama = $get(9);
        $ayahNama = $get(10);
        $ibuNama = $get(11);

        $messages = [];

        if ($nama === '') {
            $messages['nama'] = 'Nama lengkap wajib diisi.';
        }
        if ($nisn === '' || strlen($nisn) !== 10) {
            $messages['nisn'] = 'NISN wajib 10 digit angka.';
        }
        if ($nik === '' || strlen($nik) !== 16) {
            $messages['nik'] = 'NIK wajib 16 digit angka.';
        }
        if ($tempatLahir === '') {
            $messages['tempat_lahir'] = 'Tempat lahir wajib diisi.';
        }
        if ($tanggalLahir === null) {
            $messages['tanggal_lahir'] = 'Tanggal lahir wajib format yyyy-mm-dd.';
        }
        if ($jenisKelamin === null) {
            $messages['jenis_kelamin'] = 'Jenis kelamin wajib Laki-laki atau Perempuan.';
        }
        if (! in_array($angkatan, ['VII', 'VIII', 'IX'], true)) {
            $messages['angkatan'] = 'Angkatan wajib VII, VIII, atau IX.';
        }
        if ($ibuNama === '') {
            $messages['ibu_nama'] = 'Nama Ibu kandung wajib diisi.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }

        return [
            'excel_row' => $excelRow,
            'nama' => $nama,
            'nis' => $nis !== '' ? $nis : null,
            'nisn' => $nisn,
            'nik' => $nik,
            'tempat_lahir' => $tempatLahir,
            'tanggal_lahir' => $tanggalLahir,
            'jenis_kelamin' => $jenisKelamin,
            'angkatan' => $angkatan,
            'rombel_nama' => $rombelNama !== '' ? $rombelNama : null,
            'ayah_nama' => $ayahNama !== '' ? $ayahNama : null,
            'ibu_nama' => $ibuNama,
        ];
    }

    public function normalisasiNik(mixed $value): string
    {
        $raw = trim((string) $value);
        $raw = ltrim($raw, "'`\" \t");

        return $this->hanyaDigit($raw);
    }

    private function hanyaDigit(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function normalisasiJenisKelamin(string $value): ?string
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'laki-laki', 'laki laki', 'l' => 'L',
            'perempuan', 'p' => 'P',
            default => null,
        };
    }

    private function normalisasiTanggal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $text = trim((string) $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $text) === 1) {
            return $text;
        }

        try {
            return Carbon::parse($text)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
