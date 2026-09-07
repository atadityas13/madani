<?php

namespace App\Jobs;

use App\Models\DeviceToken;
use App\Models\Notifikasi;
use App\Models\User;
use App\Support\FcmSender;
use App\Support\NotifikasiPersonalizer;
use App\Support\R2Url;
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

        // Jangan tandai sent_at jika FCM belum dikonfigurasi — agar scheduler/resend masih bisa mengirim nanti.
        if (! $fcm->isConfigured()) {
            Log::warning('fcm.notifikasi_skipped_unconfigured', [
                'notifikasi_id' => $notifikasi->id,
            ]);

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
                    'image' => (string) (R2Url::readable($notifikasi->gambar_url, 60 * 24 * 7) ?? ''),
                    'link' => (string) ($notifikasi->link ?? ''),
                    'audio' => (string) (R2Url::readable($notifikasi->audio_url, 60 * 24 * 7) ?? ''),
                    'sound' => (string) ($notifikasi->sound_key ?? Notifikasi::SOUND_DEFAULT),
                    'sound_url' => (string) (R2Url::readable($notifikasi->audio_url, 60 * 24 * 7) ?? ''),
                    'priority' => (string) ($notifikasi->priority ?? Notifikasi::PRIORITY_NORMAL),
                    'use_periode' => '0',
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
