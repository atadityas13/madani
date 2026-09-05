<?php

namespace App\Console\Commands;

use App\Models\JurnalPembelajaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportJurnalFromSimpatisansCommand extends Command
{
    protected $signature = 'jurnal:import-from-simpatisans
        {simpatisans : Path ke dump SQL Simpatisans}
        {--dry-run : Tampilkan ringkasan tanpa menulis DB}
        {--table= : Nama tabel jurnal di dump (default: auto-detect)}';

    protected $description = 'Impor histori jurnal pembelajaran dari dump SQL Simpatisans ke Madani';

    public function handle(): int
    {
        $path = $this->argument('simpatisans');
        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $sql = file_get_contents($path);
        if ($sql === false || $sql === '') {
            $this->error('Dump kosong atau tidak bisa dibaca.');

            return self::FAILURE;
        }

        $table = $this->option('table') ?: $this->detectJurnalTable($sql);
        if ($table === null) {
            $this->error('Tabel jurnal tidak ditemukan di dump. Coba --table=nama_tabel');

            return self::FAILURE;
        }

        $rows = $this->extractInsertRows($sql, $table);
        if ($rows === []) {
            $this->error("Tidak ada INSERT untuk tabel {$table}.");

            return self::FAILURE;
        }

        $this->info("Tabel sumber: {$table}");
        $this->info('Baris sumber: '.count($rows));
        $this->info('Mode: '.($this->option('dry-run') ? 'DRY-RUN' : 'APPLY'));

        $users = $this->extractInsertRows($sql, 'users');
        $gurus = $this->extractInsertRows($sql, 'gurus');
        $userById = [];
        foreach ($users as $user) {
            if (isset($user['id'])) {
                $userById[(string) $user['id']] = $user;
            }
        }
        $guruById = [];
        foreach ($gurus as $guru) {
            if (isset($guru['id'])) {
                $guruById[(string) $guru['id']] = $guru;
            }
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $orphans = [];

        DB::transaction(function () use (
            $rows,
            $userById,
            $guruById,
            &$imported,
            &$updated,
            &$skipped,
            &$orphans,
        ) {
            foreach ($rows as $row) {
                if (! is_array($row) || array_is_list($row)) {
                    $skipped++;
                    $orphans[] = 'baris tanpa nama kolom (butuh INSERT dengan daftar kolom)';

                    continue;
                }

                $sourceId = $this->intVal($row['id'] ?? null);
                $nip = $this->resolveNip($row, $userById, $guruById);
                if ($nip === null || $sourceId === null) {
                    $skipped++;
                    $orphans[] = 'id='.($row['id'] ?? '?').' tanpa NIP/source id';

                    continue;
                }

                $madaniUser = User::query()->where('username', $nip)->first();
                if ($madaniUser === null) {
                    $skipped++;
                    $orphans[] = "NIP {$nip} belum ada user Madani (source {$sourceId})";

                    continue;
                }

                $payload = $this->mapRow($row, $madaniUser->id, $sourceId);
                if ($payload === null) {
                    $skipped++;
                    $orphans[] = "source {$sourceId} field wajib tidak lengkap";

                    continue;
                }

                if ($this->option('dry-run')) {
                    $imported++;

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
        });

        $this->info("Imported: {$imported}");
        $this->info("Updated: {$updated}");
        $this->info("Skipped: {$skipped}");
        if ($orphans !== []) {
            $this->warn('Contoh orphan (max 20):');
            foreach (array_slice(array_unique($orphans), 0, 20) as $line) {
                $this->line(' - '.$line);
            }
        }

        return self::SUCCESS;
    }

    private function detectJurnalTable(string $sql): ?string
    {
        foreach (['jurnal_pembelajaran', 'jurnal_pembelajarans', 'jurnals'] as $table) {
            if (preg_match('/INSERT INTO `'.preg_quote($table, '/').'`/i', $sql)) {
                return $table;
            }
        }

        return null;
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
                return (string) $row[$key];
            }
        }

        if (! empty($row['user_id']) && isset($userById[(string) $row['user_id']]['username'])) {
            return (string) $userById[(string) $row['user_id']]['username'];
        }

        if (! empty($row['guru_id']) && isset($guruById[(string) $row['guru_id']]['username'])) {
            return (string) $guruById[(string) $row['guru_id']]['username'];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    private function mapRow(array $row, int $userId, int $sourceId): ?array
    {
        $kelasId = $this->intVal($row['kelas_id'] ?? null);
        $mapelId = $this->intVal($row['mapel_id'] ?? null);
        $tanggal = $this->clean($row['tanggal'] ?? null);
        $materi = $this->clean($row['materi_pokok'] ?? $row['materi'] ?? null);

        if ($kelasId === null || $mapelId === null || $tanggal === null || $materi === null) {
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

        return [
            'user_id' => $userId,
            'kelas_id' => $kelasId,
            'nama_kelas' => $this->clean($row['nama_kelas'] ?? null),
            'mapel_id' => $mapelId,
            'nama_mapel' => $this->clean($row['nama_mapel'] ?? $row['mapel'] ?? null),
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
     * @return list<array<string, mixed>>
     */
    private function extractInsertRows(string $sql, string $table): array
    {
        $rows = [];
        $pattern = '/INSERT INTO `'.preg_quote($table, '/').'`\s*(?:\(([^)]*)\))?\s*VALUES\s*(.+?);/is';
        if (! preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER)) {
            return $rows;
        }

        foreach ($matches as $match) {
            $columns = null;
            if (! empty($match[1])) {
                $columns = array_map(fn ($c) => trim($c, " `\n\r\t"), explode(',', $match[1]));
            }
            foreach ($this->splitSqlTuples($match[2]) as $tuple) {
                $vals = $this->splitSqlValues($tuple);
                $rows[] = ($columns && count($columns) === count($vals))
                    ? array_combine($columns, $vals)
                    : $vals;
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
                    $buf .= $ch;
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
