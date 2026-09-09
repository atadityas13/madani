<?php

namespace App\Console\Commands;

use App\Services\JurnalEntryConsolidationService;
use Illuminate\Console\Command;

class ConsolidateJurnalEntriesCommand extends Command
{
    protected $signature = 'jurnal:consolidate-entries
        {--dry-run : Hanya hitung tanpa mengubah DB}
        {--user= : Batasi ke user_id Madani tertentu}';

    protected $description = 'Gabungkan entri jurnal jam berurutan (mis. 1+2) menjadi satu baris jam_list';

    public function handle(JurnalEntryConsolidationService $service): int
    {
        $userId = $this->option('user');
        $hasil = $service->consolidate(
            $userId !== null && $userId !== '' ? (int) $userId : null,
            (bool) $this->option('dry-run'),
        );

        $this->info('Mode: '.($hasil['dry_run'] ? 'DRY-RUN' : 'APPLY'));
        $this->info('Grup digabung: '.$hasil['groups_merged']);
        $this->info('Baris dihapus (setelah digabung): '.$hasil['rows_removed']);

        return self::SUCCESS;
    }
}
