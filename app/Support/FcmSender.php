<?php

namespace App\Support;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FcmSender
{
    /**
     * @param  list<string>  $tokens
     * @param  array{title: string, body: string, data?: array<string, string>, priority?: string, android_channel_id?: string}  $payload
     * @return array{sent: int, failed: int}
     */
    public function sendToTokens(array $tokens, array $payload): array
    {
        $tokens = array_values(array_unique(array_filter($tokens)));
        if ($tokens === []) {
            return ['sent' => 0, 'failed' => 0];
        }

        if (! $this->isConfigured()) {
            Log::info('fcm.skipped_unconfigured', ['token_count' => count($tokens)]);

            return ['sent' => 0, 'failed' => 0];
        }

        $sent = 0;
        $failed = 0;

        foreach ($tokens as $token) {
            try {
                $ok = $this->sendOne($token, $payload);
                if ($ok) {
                    $sent++;
                } else {
                    $failed++;
                }
            } catch (Throwable $e) {
                $failed++;
                Log::warning('fcm.send_failed', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    public function isConfigured(): bool
    {
        $credentials = $this->credentials();

        return is_array($credentials)
            && filled($credentials['client_email'] ?? null)
            && filled($credentials['private_key'] ?? null)
            && filled(config('services.firebase.project_id'));
    }

    /**
     * @param  array{title: string, body: string, data?: array<string, string>, priority?: string, android_channel_id?: string}  $payload
     */
    private function sendOne(string $token, array $payload): bool
    {
        $projectId = (string) config('services.firebase.project_id');
        $accessToken = $this->accessToken();
        if ($accessToken === null) {
            return false;
        }

        $notification = [
            'title' => $payload['title'],
            'body' => $payload['body'],
        ];
        $image = $payload['data']['image'] ?? null;
        if (is_string($image) && $image !== '') {
            $notification['image'] = $image;
        }

        $androidPriority = ($payload['priority'] ?? 'normal') === 'high' ? 'high' : 'normal';
        $channelId = $payload['android_channel_id'] ?? 'madani_push_default';
        $isAlarm = str_contains($channelId, 'alarm');

        $data = array_map('strval', $payload['data'] ?? []);
        $data['title'] = (string) $payload['title'];
        $data['body'] = (string) $payload['body'];
        $data['message'] = (string) $payload['body'];

        $message = [
            'message' => [
                'token' => $token,
                'data' => $data,
                'android' => [
                    'priority' => $isAlarm ? 'high' : $androidPriority,
                ],
            ],
        ];

        // Alarm: data-only agar onMessageReceived + MediaPlayer USAGE_ALARM jalan di background.
        // Default: sertakan notification tray sistem (tanpa sound agar channel app yang mengatur).
        if (! $isAlarm) {
            $message['message']['notification'] = $notification;
            $message['message']['android']['notification'] = [
                'channel_id' => $channelId,
                'default_vibrate_timings' => true,
            ];
        }

        $response = Http::timeout(20)
            ->withToken($accessToken)
            ->acceptJson()
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $message);

        if ($response->successful()) {
            return true;
        }

        $error = (string) data_get($response->json(), 'error.status', '');
        if (in_array($error, ['NOT_FOUND', 'UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
            DeviceToken::query()->where('fcm_token', $token)->delete();
        }

        Log::warning('fcm.http_error', [
            'status' => $response->status(),
            'body' => mb_substr($response->body(), 0, 300),
        ]);

        return false;
    }

    private function accessToken(): ?string
    {
        return Cache::remember('firebase_fcm_access_token', 50 * 60, function (): ?string {
            $credentials = $this->credentials();
            if ($credentials === null) {
                return null;
            }

            $now = time();
            $jwtHeader = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']) ?: '{}');
            $jwtClaim = $this->base64UrlEncode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]) ?: '{}');

            $unsigned = $jwtHeader.'.'.$jwtClaim;
            $privateKey = openssl_pkey_get_private((string) $credentials['private_key']);
            if ($privateKey === false) {
                Log::error('fcm.invalid_private_key');

                return null;
            }

            $signature = '';
            if (! openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
                Log::error('fcm.sign_failed');

                return null;
            }

            $assertion = $unsigned.'.'.$this->base64UrlEncode($signature);
            $response = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);

            if (! $response->successful()) {
                Log::error('fcm.oauth_failed', ['status' => $response->status()]);

                return null;
            }

            $token = $response->json('access_token');

            return is_string($token) && $token !== '' ? $token : null;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function credentials(): ?array
    {
        $json = config('services.firebase.credentials_json');
        if (is_string($json) && $json !== '') {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $path = config('services.firebase.credentials');
        if (! is_string($path) || $path === '' || ! is_file($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
