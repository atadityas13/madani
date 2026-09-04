<?php

namespace App\Jobs;

use App\Models\DeviceToken;
use App\Models\Notifikasi;
use App\Models\User;
use App\Support\FcmSender;
use App\Support\NotifikasiPersonalizer;
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

    public function handle(FcmSender $fcm, NotifikasiPersonalizer $personalizer): void
    {
        $notifikasi = Notifikasi::query()->find($this->notifikasiId);
        if ($notifikasi === null || ! $notifikasi->is_active) {
            return;
        }

        if ($notifikasi->published_at !== null && $notifikasi->published_at->isFuture()) {
            return;
        }

        if ($notifikasi->scheduled_at !== null && $notifikasi->scheduled_at->isFuture()) {
            return;
        }

        $sent = 0;
        $failed = 0;

        foreach ($notifikasi->resolveRecipients() as $recipient) {
            $tokens = DeviceToken::query()
                ->where('tokenable_type', $recipient::class)
                ->where('tokenable_id', (string) $recipient->getKey())
                ->pluck('fcm_token')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($tokens === []) {
                continue;
            }

            $title = $personalizer->render($notifikasi->judul, $recipient);
            $body = mb_substr(strip_tags($personalizer->render($notifikasi->isi, $recipient)), 0, 180);

            $result = $fcm->sendToTokens($tokens, [
                'title' => $title,
                'body' => $body,
                'priority' => $notifikasi->priority ?: Notifikasi::PRIORITY_NORMAL,
                'android_channel_id' => $notifikasi->androidChannelId(),
                'data' => array_filter([
                    'notifikasi_id' => (string) $notifikasi->id,
                    'jenis' => (string) $notifikasi->jenis,
                    'title' => $title,
                    'body' => $body,
                    'message' => $body,
                    'image' => (string) ($notifikasi->gambar_url ?? ''),
                    'link' => (string) ($notifikasi->link ?? ''),
                    'audio' => (string) ($notifikasi->audio_url ?? ''),
                    'sound' => (string) ($notifikasi->sound_key ?? Notifikasi::SOUND_DEFAULT),
                    'sound_url' => (string) ($notifikasi->audio_url ?? ''),
                    'priority' => (string) ($notifikasi->priority ?? Notifikasi::PRIORITY_NORMAL),
                    'use_periode' => $notifikasi->use_periode ? '1' : '0',
                    'reader_type' => $recipient instanceof User ? 'guru' : 'siswa',
                    'reader_id' => (string) $recipient->getKey(),
                ], fn ($v) => $v !== ''),
            ]);

            $sent += $result['sent'];
            $failed += $result['failed'];
        }

        $notifikasi->forceFill(['sent_at' => now()])->save();

        Log::info('fcm.notifikasi_dispatched', [
            'notifikasi_id' => $notifikasi->id,
            'sent' => $sent,
            'failed' => $failed,
            'recipient_hint' => $notifikasi->audience,
        ]);
    }
}
