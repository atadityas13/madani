<?php

namespace App\Support;

use App\Models\Gtk;
use App\Models\Madrasah;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ElapkinBridge
{
    /**
     * @return array{success: bool, message?: string, status?: int, cookies?: string, kepala_madrasah?: ?array<string, string>}
     */
    public function openSession(User $user): array
    {
        $ticket = $this->buildSsoTicket($user);
        $apiUrl = rtrim((string) config('services.elapkin.mobile_url'), '/').'/api/auth/sso.php';

        try {
            $response = Http::timeout(20)
                ->asJson()
                ->withHeaders($this->mobileHeaders())
                ->post($apiUrl, [
                    'nip' => $ticket['nip'],
                    'timestamp' => $ticket['timestamp'],
                    'signature' => $ticket['signature'],
                    'profile_hash' => $ticket['profile_hash'],
                    'profile' => $ticket['profile'],
                ]);
        } catch (\Throwable) {
            return [
                'success' => false,
                'message' => 'Tidak dapat menghubungi server e-Lapkin.',
                'status' => 502,
            ];
        }

        $payload = $response->json();
        if (! $response->successful() || ! ($payload['success'] ?? false)) {
            $message = is_string($payload['message'] ?? null) && $payload['message'] !== ''
                ? $payload['message']
                : (is_string($payload['error'] ?? null) ? $payload['error'] : 'SSO e-Lapkin ditolak.');

            $debug = mb_substr(trim((string) $response->body()), 0, 400);
            if ($debug !== '') {
                $message .= " (HTTP {$response->status()}: {$debug})";
            }

            Log::error('elapkin-bridge rejected', [
                'elapkin_http_status' => $response->status(),
                'nip' => $ticket['nip'],
                'elapkin_body_preview' => $debug,
            ]);

            return [
                'success' => false,
                'message' => $message,
                'status' => 401,
            ];
        }

        $cookies = $this->extractCookies($response);
        if ($cookies === '') {
            return [
                'success' => false,
                'message' => 'SSO berhasil tetapi cookie sesi tidak diterima dari e-Lapkin.',
                'status' => 502,
            ];
        }

        return [
            'success' => true,
            'cookies' => $cookies,
            'kepala_madrasah' => $ticket['kepala_madrasah'],
        ];
    }

    /**
     * @return array{success: bool, message?: string, status?: int, tahun?: int, data?: list<mixed>, count?: int}
     */
    public function hariLibur(User $user, int $tahun): array
    {
        $bridge = $this->openSession($user);
        if (! $bridge['success']) {
            return [
                'success' => false,
                'message' => $bridge['message'] ?? 'Gagal membuka sesi e-Lapkin.',
                'status' => $bridge['status'] ?? 401,
            ];
        }

        $headers = array_merge($this->mobileHeaders(), [
            'Cookie' => $this->cookiesToHeader((string) $bridge['cookies']),
        ]);
        $query = ['action' => 'get_by_year', 'tahun' => $tahun];
        $candidates = [
            rtrim((string) config('services.elapkin.mobile_url'), '/').'/api/hari_libur.php',
            preg_replace('#/mobile-app/?$#', '', rtrim((string) config('services.elapkin.mobile_url'), '/')).'/api/hari_libur.php',
        ];
        $candidates = array_values(array_unique(array_filter($candidates)));

        $lastMessage = 'Gagal memuat hari libur dari e-Lapkin.';
        $lastStatus = 502;

        foreach ($candidates as $holidayUrl) {
            try {
                $response = Http::timeout(20)->withHeaders($headers)->get($holidayUrl, $query);
            } catch (\Throwable) {
                continue;
            }

            $payload = $response->json();
            if ($response->successful() && ($payload['success'] ?? false)) {
                return [
                    'success' => true,
                    'tahun' => $tahun,
                    'data' => $payload['data'] ?? [],
                    'count' => count($payload['data'] ?? []),
                ];
            }

            if (is_string($payload['message'] ?? null) && $payload['message'] !== '') {
                $lastMessage = $payload['message'];
            }
            $lastStatus = $response->status() ?: 502;
            if ($response->status() !== 404) {
                break;
            }
        }

        return [
            'success' => false,
            'message' => $lastMessage,
            'status' => $lastStatus,
        ];
    }

    /**
     * @return array{nip: string, timestamp: int, signature: string, profile_hash: string, profile: array<string, mixed>, kepala_madrasah: ?array<string, string>}
     */
    public function buildSsoTicket(User $user): array
    {
        $user->loadMissing('gtk');
        $gtk = $user->gtk;
        $kepala = $this->resolveKepalaMadrasah();
        $unitKerja = Madrasah::query()->value('nama') ?: 'MTsN 11 Majalengka';
        $nip = $gtk?->nip ?: $user->username;

        $profile = [
            'nip' => $nip,
            'nama' => $gtk?->nama_lengkap ?? $user->name,
            'jabatan' => $gtk?->jabatan ?? 'Guru',
            'kode_guru' => $gtk?->kode_internal,
            'guru_id' => $gtk?->id,
            'nuptk' => $gtk?->nuptk,
            'golongan' => $gtk?->golongan,
            'unit_kerja' => $unitKerja,
            'nip_penilai' => $kepala['nip'] ?? null,
            'nama_penilai' => $kepala['nama'] ?? null,
            'jabatan_penilai' => $kepala['jabatan'] ?? 'Kepala Madrasah',
            'penilai_peran' => 'Kepala Madrasah',
            'mapel' => array_values((array) $gtk?->metaGet('mapel', [])),
            'tugas_tambahan' => array_values((array) $gtk?->metaGet('tugas_tambahan', [])),
        ];

        $timestamp = time();
        $profileJson = json_encode($profile, JSON_UNESCAPED_UNICODE) ?: '{}';
        $profileHash = hash('sha256', $profileJson);
        $payload = $nip.'|'.$timestamp.'|'.$profileHash;
        $signature = hash_hmac('sha256', $payload, (string) config('services.elapkin.sso_secret'));

        return [
            'nip' => $nip,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'profile_hash' => $profileHash,
            'profile' => $profile,
            'kepala_madrasah' => $kepala,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function mobileHeaders(): array
    {
        return [
            'User-Agent' => (string) config('services.elapkin.talim_user_agent'),
            'X-Mobile-Token' => $this->generateMobileToken(),
            'X-App-Package' => (string) config('services.elapkin.talim_package'),
            'Accept' => 'application/json',
        ];
    }

    private function generateMobileToken(): string
    {
        $date = now('Asia/Jakarta')->format('Y-m-d');

        return md5((string) config('services.elapkin.mobile_secret').$date);
    }

    private function cookiesToHeader(string $cookies): string
    {
        $parts = [];
        foreach (preg_split('/\r\n|\r|\n/', $cookies) ?: [] as $line) {
            $pair = trim(explode(';', $line)[0]);
            if ($pair !== '' && str_contains($pair, '=')) {
                $parts[] = $pair;
            }
        }

        return implode('; ', array_unique($parts));
    }

    private function extractCookies(Response $response): string
    {
        $parts = [];
        foreach ($response->cookies() as $cookie) {
            $parts[] = $cookie->getName().'='.$cookie->getValue();
        }

        if ($parts === []) {
            $headers = $response->header('Set-Cookie');
            $lines = is_array($headers) ? $headers : ($headers ? [$headers] : []);
            foreach ($lines as $line) {
                $pair = trim(explode(';', (string) $line)[0]);
                if (str_contains($pair, '=')) {
                    $parts[] = $pair;
                }
            }
        }

        return implode("\n", array_unique($parts));
    }

    /**
     * @return array{nip: string, nama: string, jabatan: string, peran: string}|null
     */
    private function resolveKepalaMadrasah(): ?array
    {
        $nip = trim((string) config('services.elapkin.kepala_nip', ''));
        if ($nip !== '') {
            $gtk = Gtk::query()->where('nip', $nip)->first();

            return [
                'nip' => $nip,
                'nama' => $gtk?->nama_lengkap ?: (string) config('services.elapkin.kepala_nama', 'Kepala Madrasah'),
                'jabatan' => $gtk?->jabatan ?: 'Kepala Madrasah',
                'peran' => 'Penilai LKH/RKB',
            ];
        }

        $gtk = Gtk::query()
            ->where('status', 'aktif')
            ->where('jabatan', 'like', '%Kepala Madrasah%')
            ->orderBy('id')
            ->first();

        if (! $gtk || ! filled($gtk->nip)) {
            return null;
        }

        return [
            'nip' => (string) $gtk->nip,
            'nama' => $gtk->nama_lengkap,
            'jabatan' => $gtk->jabatan ?: 'Kepala Madrasah',
            'peran' => 'Penilai LKH/RKB',
        ];
    }
}
