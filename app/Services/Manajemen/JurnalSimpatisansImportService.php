<?php

namespace App\Services\Manajemen;

use App\Models\JurnalPembelajaran;
use App\Models\User;
use App\Services\JurnalEntryConsolidationService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class JurnalSimpatisansImportService
{
    /**
     * @return array{
     *     table: string,
     *     source_rows: int,
     *     imported: int,
     *     updated: int,
     *     skipped: int,
     *     orphans: list<string>,
     *     skip_reasons: array<string, int>,
     *     dry_run: bool
     * }
     */
    public function imporDariPath(string $path, bool $dryRun = false, ?string $table = null): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException("File tidak ditemukan: {$path}");
        }

        $sql = file_get_contents($path);
        if ($sql === false || $sql === '') {
            throw new InvalidArgumentException('Dump kosong atau tidak bisa dibaca.');
        }

        return $this->imporDariSql($sql, $dryRun, $table);
    }

    /**
     * @return array{
     *     table: string,
     *     source_rows: int,
     *     imported: int,
     *     updated: int,
     *     skipped: int,
     *     orphans: list<string>,
     *     skip_reasons: array<string, int>,
     *     dry_run: bool
     * }
     */
    public function imporDariUpload(UploadedFile $file, bool $dryRun = false): array
    {
        $sql = file_get_contents($file->getRealPath() ?: '');
        if ($sql === false || $sql === '') {
            throw new InvalidArgumentException('File SQL kosong atau tidak bisa dibaca.');
        }

        return $this->imporDariSql($sql, $dryRun);
    }

    /**
     * @return array{
     *     table: string,
     *     source_rows: int,
     *     imported: int,
     *     updated: int,
     *     skipped: int,
     *     orphans: list<string>,
     *     skip_reasons: array<string, int>,
     *     consolidated_groups?: int,
     *     consolidated_removed?: int,
     *     dry_run: bool
     * }
     */
    public function imporDariSql(string $sql, bool $dryRun = false, ?string $table = null): array
    {
        $table ??= $this->detectJurnalTable($sql);
        if ($table === null) {
            throw new InvalidArgumentException('Tabel jurnal tidak ditemukan di dump. Pastikan ada INSERT jurnal_pembelajaran.');
        }

        $fallbackColumns = $this->columnsFromCreateTable($sql, $table);
        $rows = $this->extractInsertRows($sql, $table, $fallbackColumns);
        if ($rows === []) {
            throw new InvalidArgumentException("Tidak ada INSERT untuk tabel {$table}.");
        }

        $userById = $this->indexById($this->extractInsertRows($sql, 'users'));
        $guruById = $this->indexById($this->extractInsertRows($sql, 'gurus'));
        $kelasNamaById = $this->namaLookup($this->extractInsertRows($sql, 'kelas'), ['nama_kelas', 'nama', 'name']);
        $mapelNamaById = $this->namaLookup($this->extractInsertRows($sql, 'mapels'), ['nama_mapel', 'nama', 'name', 'mapel']);

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        /** @var array<string, int> $skipReasons */
        $skipReasons = [];

        $noteSkip = function (string $reason) use (&$skipped, &$skipReasons): void {
            $skipped++;
            $skipReasons[$reason] = ($skipReasons[$reason] ?? 0) + 1;
        };

        $runner = function () use (
            $rows,
            $userById,
            $guruById,
            $kelasNamaById,
            $mapelNamaById,
            $dryRun,
            $noteSkip,
            &$imported,
            &$updated,
        ): void {
            foreach ($rows as $row) {
                if (! is_array($row) || array_is_list($row)) {
                    $noteSkip('baris tanpa nama kolom (butuh INSERT --complete-insert atau CREATE TABLE di dump)');

                    continue;
                }

                $sourceId = $this->intVal($row['id'] ?? null);
                $nip = $this->resolveNip($row, $userById, $guruById);
                if ($sourceId === null) {
                    $noteSkip('tanpa source id');

                    continue;
                }
                if ($nip === null) {
                    $noteSkip('tanpa NIP (user_id/guru_id tidak terpetakan — sertakan tabel users/gurus di dump)');

                    continue;
                }

                $madaniUser = $this->findMadaniUser($nip);
                if ($madaniUser === null) {
                    $noteSkip("NIP {$nip} belum ada user/GTK Madani");

                    continue;
                }

                $payload = $this->mapRow($row, $madaniUser->id, $sourceId, $kelasNamaById, $mapelNamaById);
                if ($payload === null) {
                    $noteSkip("source {$sourceId} field wajib tidak lengkap (kelas/mapel/tanggal)");

                    continue;
                }

                if ($dryRun) {
                    $exists = JurnalPembelajaran::query()
                        ->where('source_simpatisans_id', $sourceId)
                        ->exists();
                    if ($exists) {
                        $updated++;
                    } else {
                        $imported++;
                    }

                    continue;
                }

                $existing = JurnalPembelajaran::query()
                    ->where('source_simpatisans_id', $sourceId)
                    ->first();

                if ($existing !== null) {
                    $existing->update($payload);
                    $updated++;
                } else {
                    JurnalPembelajaran::query()->create($payload);
                    $imported++;
                }
            }
        };

        if ($dryRun) {
            $runner();
        } else {
            DB::transaction($runner);
        }

        $consolidation = ['groups_merged' => 0, 'rows_removed' => 0, 'dry_run' => $dryRun];
        if (! $dryRun && ($imported + $updated) > 0) {
            $consolidation = app(JurnalEntryConsolidationService::class)->consolidate();
        }

        arsort($skipReasons);

        $orphans = [];
        foreach ($skipReasons as $reason => $count) {
            $orphans[] = "{$reason} ×{$count}";
        }

        return [
            'table' => $table,
            'source_rows' => count($rows),
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'orphans' => $orphans,
            'skip_reasons' => $skipReasons,
            'consolidated_groups' => $consolidation['groups_merged'],
            'consolidated_removed' => $consolidation['rows_removed'],
            'dry_run' => $dryRun,
        ];
    }

    private function detectJurnalTable(string $sql): ?string
    {
        foreach (['jurnal_pembelajaran', 'jurnal_pembelajarans', 'jurnals'] as $table) {
            if (preg_match('/INSERT INTO (?:`[^`]+`\.)?`'.preg_quote($table, '/').'`/i', $sql)) {
                return $table;
            }
        }

        return null;
    }

    /**
     * @return list<string>|null
     */
    private function columnsFromCreateTable(string $sql, string $table): ?array
    {
        $pattern = '/CREATE TABLE (?:IF NOT EXISTS )?(?:`[^`]+`\.)?`'.preg_quote($table, '/').'`\s*\((.*)\)\s*(?:ENGINE|DEFAULT|COLLATE|AUTO_INCREMENT|;)/is';
        if (! preg_match($pattern, $sql, $match)) {
            return null;
        }

        $body = $match[1];
        $columns = [];
        foreach (preg_split('/\r\n|\r|\n/', $body) ?: [] as $line) {
            $line = trim($line, " \t,");
            if ($line === '' || ! str_starts_with($line, '`')) {
                continue;
            }
            if (! preg_match('/^`([^`]+)`/', $line, $col)) {
                continue;
            }
            $name = $col[1];
            if (preg_match('/^(PRIMARY|UNIQUE|KEY|INDEX|CONSTRAINT|FULLTEXT|SPATIAL|FOREIGN)/i', $name)) {
                continue;
            }
            $columns[] = $name;
        }

        return $columns === [] ? null : $columns;
    }

    private function findMadaniUser(string $nip): ?User
    {
        $nip = trim($nip);

        return User::query()
            ->where('username', $nip)
            ->orWhereHas('gtk', fn ($query) => $query->where('nip', $nip))
            ->first();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, array<string, mixed>>
     */
    private function indexById(array $rows): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            if (! is_array($row) || array_is_list($row) || ! isset($row['id'])) {
                continue;
            }
            $indexed[(string) $row['id']] = $row;
        }

        return $indexed;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $nameKeys
     * @return array<string, string>
     */
    private function namaLookup(array $rows, array $nameKeys): array
    {
        $lookup = [];
        foreach ($rows as $row) {
            if (! is_array($row) || array_is_list($row) || ! isset($row['id'])) {
                continue;
            }
            foreach ($nameKeys as $key) {
                $nama = $this->clean(isset($row[$key]) ? (string) $row[$key] : null);
                if ($nama !== null) {
                    $lookup[(string) $row['id']] = $nama;
                    break;
                }
            }
        }

        return $lookup;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, array<string, mixed>>  $userById
     * @param  array<string, array<string, mixed>>  $guruById
     */
    private function resolveNip(array $row, array $userById, array $guruById): ?string
    {
        foreach (['nip', 'username'] as $key) {
            if (! empty($row[$key])) {
                return trim((string) $row[$key]);
            }
        }

        if (! empty($row['user_id']) && isset($userById[(string) $row['user_id']])) {
            $user = $userById[(string) $row['user_id']];
            foreach (['username', 'nip'] as $key) {
                if (! empty($user[$key])) {
                    return trim((string) $user[$key]);
                }
            }
        }

        if (! empty($row['guru_id']) && isset($guruById[(string) $row['guru_id']])) {
            $guru = $guruById[(string) $row['guru_id']];
            foreach (['username', 'nip'] as $key) {
                if (! empty($guru[$key])) {
                    return trim((string) $guru[$key]);
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $kelasNamaById
     * @param  array<string, string>  $mapelNamaById
     * @return array<string, mixed>|null
     */
    private function mapRow(
        array $row,
        int $userId,
        int $sourceId,
        array $kelasNamaById,
        array $mapelNamaById,
    ): ?array {
        $kelasId = $this->intVal($row['kelas_id'] ?? null);
        $mapelId = $this->intVal($row['mapel_id'] ?? null);
        $tanggal = $this->normalizeTanggal($row['tanggal'] ?? null);
        // Materi kosong di Simpatisans tetap diimpor agar guru tidak kehilangan entri.
        $materi = $this->clean($row['materi_pokok'] ?? $row['materi'] ?? null) ?? '-';

        if ($kelasId === null || $mapelId === null || $tanggal === null) {
            return null;
        }

        $jamList = $this->decodeIntList($row['jam_list'] ?? null);
        $jadwalIds = $this->decodeIntList($row['jadwal_ids'] ?? null);
        $jamKe = $this->intVal($row['jam_ke'] ?? null) ?? ($jamList[0] ?? 0);
        $jadwalId = $this->intVal($row['jadwal_id'] ?? null) ?? ($jadwalIds[0] ?? null);
        $ketercapaian = strtolower((string) ($row['ketercapaian'] ?? 'tercapai'));
        if (! in_array($ketercapaian, ['tercapai', 'belum'], true)) {
            $ketercapaian = 'tercapai';
        }

        $hari = $this->clean($row['hari'] ?? null);
        if ($hari === null) {
            try {
                $hari = $this->hariIndonesia(Carbon::createFromFormat('Y-m-d', $tanggal, 'Asia/Jakarta'));
            } catch (\Throwable) {
                $hari = null;
            }
        }

        $namaKelas = $this->clean($row['nama_kelas'] ?? null)
            ?? ($kelasNamaById[(string) $kelasId] ?? null);
        $namaMapel = $this->clean($row['nama_mapel'] ?? $row['mapel'] ?? null)
            ?? ($mapelNamaById[(string) $mapelId] ?? null);

        return [
            'user_id' => $userId,
            'kelas_id' => $kelasId,
            'nama_kelas' => $namaKelas,
            'mapel_id' => $mapelId,
            'nama_mapel' => $namaMapel,
            'tanggal' => $tanggal,
            'hari' => $hari,
            'jam_ke' => $jamKe,
            'jam_list' => $jamList,
            'jadwal_id' => $jadwalId,
            'jadwal_ids' => $jadwalIds,
            'materi_pokok' => $materi,
            'ketercapaian' => $ketercapaian,
            'penugasan_siswa' => $this->clean($row['penugasan_siswa'] ?? null),
            'catatan_guru' => $this->clean($row['catatan_guru'] ?? null),
            'semester_id' => $this->intVal($row['semester_id'] ?? null),
            'semester_tipe' => $this->clean($row['semester_tipe'] ?? null),
            'semester_nama_tahun' => $this->clean($row['semester_nama_tahun'] ?? $row['nama_tahun'] ?? null),
            'source_simpatisans_id' => $sourceId,
        ];
    }

    private function normalizeTanggal(mixed $value): ?string
    {
        $raw = $this->clean(isset($value) ? (string) $value : null);
        if ($raw === null) {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $raw, $match) === 1) {
            return $match[0];
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y/m/d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $raw, 'Asia/Jakarta')->format('Y-m-d');
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * @return list<int>
     */
    private function decodeIntList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_array($value)) {
            return array_values(array_map('intval', $value));
        }

        $raw = trim((string) $value);
        $json = json_decode(stripslashes($raw), true);
        if (is_array($json)) {
            return array_values(array_map('intval', $json));
        }

        return [];
    }

    private function intVal(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim(stripcslashes($value));

        return $value === '' ? null : $value;
    }

    private function hariIndonesia(Carbon $date): string
    {
        return match ($date->dayOfWeek) {
            Carbon::MONDAY => 'Senin',
            Carbon::TUESDAY => 'Selasa',
            Carbon::WEDNESDAY => 'Rabu',
            Carbon::THURSDAY => 'Kamis',
            Carbon::FRIDAY => 'Jumat',
            Carbon::SATURDAY => 'Sabtu',
            default => 'Minggu',
        };
    }

    /**
     * @param  list<string>|null  $fallbackColumns
     * @return list<array<string, mixed>>
     */
    private function extractInsertRows(string $sql, string $table, ?array $fallbackColumns = null): array
    {
        $rows = [];
        $pattern = '/INSERT INTO (?:`[^`]+`\.)?`'.preg_quote($table, '/').'`\s*(?:\(([^)]*)\))?\s*VALUES\s*(.+?);/is';
        if (! preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER)) {
            return $rows;
        }

        foreach ($matches as $match) {
            $columns = null;
            if (! empty($match[1])) {
                $columns = array_map(fn ($c) => trim($c, " `\n\r\t"), explode(',', $match[1]));
            } elseif ($fallbackColumns !== null) {
                $columns = $fallbackColumns;
            }

            foreach ($this->splitSqlTuples($match[2]) as $tuple) {
                $vals = $this->splitSqlValues($tuple);
                if ($columns !== null && count($columns) === count($vals)) {
                    $rows[] = array_combine($columns, $vals);

                    continue;
                }

                $rows[] = $vals;
            }
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function splitSqlTuples(string $blob): array
    {
        $tuples = [];
        $len = strlen($blob);
        $depth = 0;
        $inStr = false;
        $esc = false;
        $start = null;
        for ($i = 0; $i < $len; $i++) {
            $ch = $blob[$i];
            if ($inStr) {
                if ($esc) {
                    $esc = false;
                } elseif ($ch === '\\') {
                    $esc = true;
                } elseif ($ch === "'") {
                    if ($i + 1 < $len && $blob[$i + 1] === "'") {
                        $i++;
                    } else {
                        $inStr = false;
                    }
                }

                continue;
            }
            if ($ch === "'") {
                $inStr = true;

                continue;
            }
            if ($ch === '(') {
                if ($depth === 0) {
                    $start = $i + 1;
                }
                $depth++;

                continue;
            }
            if ($ch === ')') {
                $depth--;
                if ($depth === 0 && $start !== null) {
                    $tuples[] = substr($blob, $start, $i - $start);
                    $start = null;
                }
            }
        }

        return $tuples;
    }

    /**
     * @return list<string|null>
     */
    private function splitSqlValues(string $tuple): array
    {
        $vals = [];
        $len = strlen($tuple);
        $inStr = false;
        $esc = false;
        $buf = '';
        for ($i = 0; $i < $len; $i++) {
            $ch = $tuple[$i];
            if ($inStr) {
                if ($esc) {
                    $buf .= $ch;
                    $esc = false;
                } elseif ($ch === '\\') {
                    $esc = true;
                } elseif ($ch === "'") {
                    if ($i + 1 < $len && $tuple[$i + 1] === "'") {
                        $buf .= "'";
                        $i++;
                    } else {
                        $inStr = false;
                    }
                } else {
                    $buf .= $ch;
                }

                continue;
            }
            if ($ch === "'") {
                $inStr = true;

                continue;
            }
            if ($ch === ',') {
                $vals[] = $this->normalizeSqlValue(trim($buf));
                $buf = '';

                continue;
            }
            $buf .= $ch;
        }
        $vals[] = $this->normalizeSqlValue(trim($buf));

        return $vals;
    }

    private function normalizeSqlValue(string $v): ?string
    {
        return strcasecmp($v, 'NULL') === 0 ? null : $v;
    }
}
