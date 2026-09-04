<?php

namespace App\Jobs;

use App\Models\Notifikasi;
use App\Support\FcmSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendNotifikasiFcmJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [5, 30, 120];

    public function __construct(public int $notifikasiId) {}

    public function handle(FcmSender $fcm): void
    {
        $notifikasi = Notifikasi::query()->find($this->notifikasiId);
        if ($notifikasi === null || ! $notifikasi->is_active) {
            return;
        }

        if ($notifikasi->published_at !== null && $notifikasi->published_at->isFuture()) {
            return;
        }

        $tokens = $notifikasi->resolveFcmTokens()->filter()->unique()->values()->all();
        $result = $fcm->sendToTokens($tokens, [
            'title' => $notifikasi->judul,
            'body' => mb_substr(strip_tags($notifikasi->isi), 0, 180),
            'data' => [
                'notifikasi_id' => (string) $notifikasi->id,
                'jenis' => (string) $notifikasi->jenis,
                'title' => $notifikasi->judul,
                'body' => mb_substr(strip_tags($notifikasi->isi), 0, 180),
            ],
        ]);

        Log::info('fcm.notifikasi_dispatched', [
            'notifikasi_id' => $notifikasi->id,
            'token_count' => count($tokens),
            'sent' => $result['sent'],
            'failed' => $result['failed'],
        ]);
    }
}
