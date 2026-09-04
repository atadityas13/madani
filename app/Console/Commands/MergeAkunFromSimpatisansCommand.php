<?php

namespace App\Console\Commands;

use App\Support\GtkAkunImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MergeAkunFromSimpatisansCommand extends Command
{
    protected $signature = 'madani:merge-akun-from-simpatisans
                            {--connection=simpatisans : Nama koneksi DB Simpatisans}
                            {--file= : Path JSON array export users (alternatif tanpa koneksi DB)}
                            {--overwrite : Timpa akun Madani yang sudah ada (password/status)}';

    protected $description = 'Import akun guru Simpatisans ke users Madani by NIP (salin hash password).';

    public function handle(GtkAkunImporter $importer): int
    {
        $rows = $this->loadRows();
        if ($rows === null) {
            return self::FAILURE;
        }

        $overwrite = (bool) $this->option('overwrite');
        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $skipped = [];

        foreach ($rows as $row) {
            $row = (array) $row;
            $result = $importer->importFromSimpatisansUser($row, $overwrite);

            match ($result['action']) {
                'created' => $created++,
                'updated' => $updated++,
                'unchanged' => $unchanged++,
                default => $skipped[$result['reason'] ?? 'other'] = ($skipped[$result['reason'] ?? 'other'] ?? 0) + 1,
            };
        }

        $this->info("Selesai. created={$created} updated={$updated} unchanged={$unchanged}");
        if ($skipped !== []) {
            $parts = [];
            foreach ($skipped as $reason => $count) {
                $parts[] = "{$reason}={$count}";
            }
            $this->warn('Skipped: '.implode(' ', $parts));
            if (($skipped['gtk_missing'] ?? 0) > 0) {
                $this->line('Tip: jalankan dulu php artisan madani:merge-gtk-from-simpatisans');
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function loadRows(): ?array
    {
        $file = $this->option('file');
        if (is_string($file) && $file !== '') {
            if (! File::exists($file)) {
                $this->error("File tidak ditemukan: {$file}");

                return null;
            }

            $decoded = json_decode(File::get($file), true);
            if (! is_array($decoded)) {
                $this->error('File JSON tidak valid (harus array of objects).');

                return null;
            }

            return array_values($decoded);
        }

        $connection = (string) $this->option('connection');
        try {
            $rows = DB::connection($connection)
                ->table('users')
                ->where('role', 'guru')
                ->orderBy('id')
                ->get();
        } catch (\Throwable $e) {
            $this->error("Gagal baca koneksi [{$connection}]: ".$e->getMessage());
            $this->line('Set SIMPATISANS_DB_* di .env, atau pakai --file=export-users.json');

            return null;
        }

        return $rows->map(fn ($row) => (array) $row)->all();
    }
}
