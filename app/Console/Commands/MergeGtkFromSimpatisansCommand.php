<?php

namespace App\Console\Commands;

use App\Support\GtkMerger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MergeGtkFromSimpatisansCommand extends Command
{
    protected $signature = 'madani:merge-gtk-from-simpatisans
                            {--connection=simpatisans : Nama koneksi DB Simpatisans di config/database.php}
                            {--file= : Path JSON array hasil export gurus (alternatif tanpa koneksi DB)}
                            {--overwrite : Timpa field Madani yang sudah terisi}';

    protected $description = 'Merge data guru Simpatisans ke gtks Madani by NIP (opsi C: tidak menghapus data Madani).';

    public function handle(GtkMerger $merger): int
    {
        $rows = $this->loadRows();
        if ($rows === null) {
            return self::FAILURE;
        }

        $overwrite = (bool) $this->option('overwrite');
        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $row = (array) $row;
            $nip = trim((string) ($row['username'] ?? $row['nip'] ?? ''));
            if ($nip === '') {
                $skipped++;

                continue;
            }

            $result = $merger->mergeFromSimpatisansGuru($row, $overwrite);
            match ($result['action']) {
                'created' => $created++,
                'updated' => $updated++,
                default => $unchanged++,
            };
        }

        $this->info("Selesai. created={$created} updated={$updated} unchanged={$unchanged} skipped_no_nip={$skipped}");

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
            $rows = DB::connection($connection)->table('gurus')->orderBy('id')->get();
        } catch (\Throwable $e) {
            $this->error("Gagal baca koneksi [{$connection}]: ".$e->getMessage());
            $this->line('Set DB Simpatisans di .env / config, atau pakai --file=export-gurus.json');

            return null;
        }

        return $rows->map(fn ($row) => (array) $row)->all();
    }
}
