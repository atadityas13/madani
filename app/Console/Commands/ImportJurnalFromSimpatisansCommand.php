<?php

namespace App\Console\Commands;

use App\Services\Manajemen\JurnalSimpatisansImportService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ImportJurnalFromSimpatisansCommand extends Command
{
    protected $signature = 'jurnal:import-from-simpatisans
        {simpatisans : Path ke dump SQL Simpatisans}
        {--dry-run : Tampilkan ringkasan tanpa menulis DB}
        {--table= : Nama tabel jurnal di dump (default: auto-detect)}';

    protected $description = 'Impor histori jurnal pembelajaran dari dump SQL Simpatisans ke Madani';

    public function handle(JurnalSimpatisansImportService $import): int
    {
        try {
            $hasil = $import->imporDariPath(
                $this->argument('simpatisans'),
                (bool) $this->option('dry-run'),
                $this->option('table') ?: null,
            );
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Tabel sumber: {$hasil['table']}");
        $this->info('Baris sumber: '.$hasil['source_rows']);
        $this->info('Mode: '.($hasil['dry_run'] ? 'DRY-RUN' : 'APPLY'));
        $this->info('Imported: '.$hasil['imported']);
        $this->info('Updated: '.$hasil['updated']);
        $this->info('Skipped: '.$hasil['skipped']);

        if ($hasil['orphans'] !== []) {
            $this->warn('Contoh orphan (max 20):');
            foreach (array_slice($hasil['orphans'], 0, 20) as $line) {
                $this->line(' - '.$line);
            }
        }

        return self::SUCCESS;
    }
}
