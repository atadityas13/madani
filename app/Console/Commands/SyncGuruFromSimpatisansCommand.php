<?php

namespace App\Console\Commands;

use App\Models\Gtk;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class SyncGuruFromSimpatisansCommand extends Command
{
    protected $signature = 'guru:sync-from-simpatisans
        {simpatisans : Path ke dump SQL Simpatisans}
        {--dry-run : Tampilkan ringkasan tanpa menulis DB}
        {--skip-passwords : Jangan menimpa password Madani dengan hash Simpatisans}';

    protected $description = 'Samakan data GTK & akun guru Madani agar sesuai dump Simpatisans';

    public function handle(): int
    {
        $path = $this->argument('simpatisans');
        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $sql = file_get_contents($path);
        $gurus = $this->extractInsertRows($sql, 'gurus');
        $users = $this->extractInsertRows($sql, 'users');
        $mapels = $this->extractInsertRows($sql, 'mapels');
        $guruMapels = $this->extractInsertRows($sql, 'guru_mapels');
        $tugas = $this->extractInsertRows($sql, 'tugas_tambahans');
        $guruTugas = $this->extractInsertRows($sql, 'guru_tugas_tambahans');

        if ($gurus === []) {
            $this->error('Tidak ada data gurus di dump.');

            return self::FAILURE;
        }

        $mapelById = [];
        foreach ($mapels as $mapel) {
            $mapelById[$mapel['id']] = $mapel['nama_mapel'] ?? $mapel['nama'] ?? ('#'.$mapel['id']);
        }
        $tugasById = [];
        foreach ($tugas as $item) {
            $tugasById[$item['id']] = $item['nama_tugas'] ?? ('#'.$item['id']);
        }
        $userByNip = [];
        foreach ($users as $user) {
            $userByNip[$user['username']] = $user;
        }

        $simNips = array_map(fn (array $g) => (string) $g['username'], $gurus);
        $dry = (bool) $this->option('dry-run');
        $skipPasswords = (bool) $this->option('skip-passwords');

        $this->info('Simpatisans gurus: '.count($gurus));
        $this->info('Mode: '.($dry ? 'DRY-RUN' : 'APPLY'));

        $updated = 0;
        $createdGtk = 0;
        $removed = 0;
        $passwordSynced = 0;

        DB::transaction(function () use (
            $gurus,
            $simNips,
            $userByNip,
            $guruMapels,
            $guruTugas,
            $mapelById,
            $tugasById,
            $dry,
            $skipPasswords,
            &$updated,
            &$createdGtk,
            &$removed,
            &$passwordSynced,
        ): void {
            // Hapus GTK Madani yang NIP-nya tidak ada di Simpatisans (kecuali tanpa NIP).
            $bogus = Gtk::query()
                ->whereNotNull('nip')
                ->where('nip', '!=', '')
                ->whereNotIn('nip', $simNips)
                ->get();

            foreach ($bogus as $gtk) {
                $this->warn("Hapus GTK non-Simpatisans: {$gtk->nip} / {$gtk->nama}");
                if (! $dry) {
                    DB::table('rombels')->where('gtk_id', $gtk->id)->update(['gtk_id' => null]);
                    DB::table('rombels')->where('wali_kelas_id', $gtk->id)->update(['wali_kelas_id' => null]);
                    User::query()->where('gtk_id', $gtk->id)->update(['gtk_id' => null]);
                    $gtk->delete();
                }
                $removed++;
            }

            Role::findOrCreate(Peran::GURU);

            foreach ($gurus as $sg) {
                $nip = (string) $sg['username'];
                $meta = $this->buildMeta($sg, $guruMapels, $guruTugas, $mapelById, $tugasById);
                $payload = [
                    'nama' => $this->clean($sg['nama_guru']) ?? $nip,
                    'gelar_depan' => $this->clean($sg['gelar_depan'] ?? null),
                    'gelar_belakang' => $this->clean($sg['gelar_belakang'] ?? null),
                    'nip' => $nip,
                    'nuptk' => $this->clean($sg['nuptk'] ?? null),
                    'jenis_kelamin' => $this->clean($sg['jenis_kelamin'] ?? null),
                    'tempat_lahir' => $this->clean($sg['tempat_lahir'] ?? null),
                    'tanggal_lahir' => $this->clean($sg['tanggal_lahir'] ?? null),
                    'agama' => $this->clean($sg['agama'] ?? null),
                    'nomor_hp' => $this->clean($sg['nomor_hp'] ?? null),
                    'email' => $this->clean($sg['email'] ?? null),
                    'alamat' => $this->clean($sg['alamat'] ?? null),
                    'jabatan' => $this->clean($sg['jabatan'] ?? null),
                    'golongan' => $this->clean($sg['golongan'] ?? null),
                    'status_pegawai' => $this->clean($sg['status_pegawai'] ?? null),
                    'kode_internal' => $this->clean($sg['kode_guru'] ?? null),
                    'duk' => isset($sg['duk']) && $sg['duk'] !== null ? (string) $sg['duk'] : null,
                    'meta' => $meta,
                    'status' => 'aktif',
                    'jenis' => Gtk::JENIS_GURU,
                ];

                $gtk = Gtk::query()->where('nip', $nip)->first();
                if ($gtk) {
                    if (! $dry) {
                        $gtk->fill($payload)->save();
                    }
                    $updated++;
                } else {
                    if (! $dry) {
                        $gtk = Gtk::query()->create($payload);
                    }
                    $createdGtk++;
                    $this->line("Buat GTK baru: {$nip}");
                }

                $simUser = $userByNip[$nip] ?? null;
                if ($simUser === null) {
                    continue;
                }

                if ($dry) {
                    continue;
                }

                /** @var Gtk $gtk */
                $user = User::query()->where('username', $nip)->first();
                $isActive = ((string) ($simUser['is_active'] ?? '1')) !== '0';
                $hash = (string) $simUser['password'];

                if ($user === null) {
                    $userId = DB::table('users')->insertGetId([
                        'name' => $payload['nama'],
                        'username' => $nip,
                        'email' => filled($payload['email'])
                            ? $payload['email']
                            : $nip.'@gtk.madani.local',
                        'password' => $hash,
                        'must_change_password' => false,
                        'is_aktif' => $isActive,
                        'gtk_id' => $gtk->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $user = User::query()->findOrFail($userId);
                    $passwordSynced++;
                } else {
                    $changes = [
                        'name' => $payload['nama'],
                        'is_aktif' => $isActive,
                        'gtk_id' => $gtk->id,
                        'updated_at' => now(),
                    ];
                    if (! $skipPasswords) {
                        $changes['password'] = $hash;
                        $passwordSynced++;
                    }
                    DB::table('users')->where('id', $user->id)->update($changes);
                    $user->refresh();
                }

                $user->syncRoles([Peran::GURU]);
            }
        });

        $this->info("Selesai. updated={$updated} created_gtk={$createdGtk} removed={$removed} passwords={$passwordSynced}");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $sg
     * @param  list<array<string, mixed>>  $guruMapels
     * @param  list<array<string, mixed>>  $guruTugas
     * @param  array<string, string>  $mapelById
     * @param  array<string, string>  $tugasById
     * @return array<string, mixed>
     */
    private function buildMeta(array $sg, array $guruMapels, array $guruTugas, array $mapelById, array $tugasById): array
    {
        $guruId = $sg['id'];
        $mapels = [];
        foreach ($guruMapels as $gm) {
            if ($gm['guru_id'] !== $guruId) {
                continue;
            }
            $name = $this->clean($mapelById[$gm['mapel_id']] ?? null);
            if ($name && ! in_array($name, $mapels, true)) {
                $mapels[] = $name;
            }
        }

        $tugasList = [];
        $seen = [];
        foreach ($guruTugas as $gt) {
            if ($gt['guru_id'] !== $guruId) {
                continue;
            }
            $nama = $this->clean($tugasById[$gt['tugas_tambahan_id']] ?? null) ?? '';
            $detail = $this->clean($gt['detail'] ?? null);
            $key = $nama.'|'.($detail ?? '');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $tugasList[] = [
                'nama' => $nama,
                'detail' => $detail,
                'is_ekuivalen' => (bool) ((int) ($gt['is_ekuivalen'] ?? 0)),
            ];
        }

        $meta = [
            'status_sertifikasi' => (bool) ((int) ($sg['status_sertifikasi'] ?? 0)),
            'is_bk' => (bool) ((int) ($sg['is_bk'] ?? 0)),
            'id_gtk' => $sg['id_gtk'] ?? null,
            'mapel' => $mapels,
            'tugas_tambahan' => $tugasList,
        ];

        if (! empty($sg['mapel_ijazah_id'])) {
            $meta['mapel_ijazah'] = $this->clean($mapelById[$sg['mapel_ijazah_id']] ?? (string) $sg['mapel_ijazah_id']);
        }
        if (! empty($sg['mapel_sertifikasi_id'])) {
            $meta['mapel_sertifikasi'] = $this->clean($mapelById[$sg['mapel_sertifikasi_id']] ?? (string) $sg['mapel_sertifikasi_id']);
        }

        return $meta;
    }

    private function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim(stripcslashes($value));

        return $value === '' ? null : $value;
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
