<?php

namespace App\Services\Simpatisans;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class ReferensiMapelService
{
    /**
     * @return array{siap: bool, mapels: list<string>, rumpuns: list<string>, peringatan: ?string}
     */
    public function ambil(): array
    {
        $kosong = [
            'siap' => false,
            'mapels' => [],
            'rumpuns' => [],
            'peringatan' => null,
        ];

        $baseUrl = rtrim((string) config('services.simpatisans.base_url'), '/');
        $secret = (string) config('services.simpatisans.secret');

        if ($baseUrl === '' || $secret === '') {
            $kosong['peringatan'] = 'Daftar mapel belum bisa dimuat. Isi SIMPATISANS_API_URL dan MADANI_INTROSPECT_SECRET.';

            return $kosong;
        }

        try {
            $response = Http::connectTimeout(5)
                ->timeout(15)
                ->acceptJson()
                ->withHeaders([
                    'X-Madani-Introspect-Secret' => $secret,
                ])
                ->get($baseUrl.'/madani/referensi')
                ->throw();
        } catch (ConnectionException) {
            $kosong['peringatan'] = 'Tidak dapat terhubung ke Simpatisans untuk daftar mapel.';

            return $kosong;
        } catch (RequestException) {
            $kosong['peringatan'] = 'Simpatisans menolak permintaan daftar mapel.';

            return $kosong;
        }

        $mapels = $this->daftar($response->json('mapels'));
        $rumpuns = $this->daftar($response->json('rumpuns'));

        return [
            'siap' => true,
            'mapels' => $mapels,
            'rumpuns' => $rumpuns,
            'peringatan' => null,
        ];
    }

    /**
     * @return list<string>
     */
    private function daftar(mixed $nilai): array
    {
        if (! is_array($nilai)) {
            return [];
        }

        $hasil = [];
        foreach ($nilai as $item) {
            $nama = trim((string) $item);
            if ($nama !== '') {
                $hasil[] = $nama;
            }
        }

        return array_values(array_unique($hasil));
    }
}
