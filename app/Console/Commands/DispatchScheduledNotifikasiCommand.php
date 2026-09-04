<?php

namespace App\Console\Commands;

use App\Jobs\SendNotifikasiFcmJob;
use App\Models\Notifikasi;
use Illuminate\Console\Command;

class DispatchScheduledNotifikasiCommand extends Command
{
    protected $signature = 'notifikasi:dispatch-scheduled';

    protected $description = 'Dispatch FCM for due scheduled notifications';

    public function handle(): int
    {
        $ids = Notifikasi::query()->dueForDispatch()->pluck('id');

        foreach ($ids as $id) {
            SendNotifikasiFcmJob::dispatch((int) $id);
            $this->info("Queued notifikasi #{$id}");
        }

        $this->info('Queued '.$ids->count().' notification(s).');

        return self::SUCCESS;
    }
}
