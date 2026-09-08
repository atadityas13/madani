<?php

namespace App\Services\Simpatisans;

use App\Models\Gtk;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RombelSyncService
{
    public const MESSAGE_TANPA_WALI = 'Terdapat rombel yang belum memiliki wali kelas, pastikan semua rombel sudah ditunjuk wali kelasnya.';

    /**
     * @return array{created: int, updated: int, total: int}
     */
    public function sync(TahunAjaran $tahunAjaran): array
    {
        $payload = $this->fetchPayload();
        $items = $payload['data'] ?? null;

        if (! is_array($items) || $items === []) {
            throw new RuntimeException('Data rombel dari SimpatiSans kosong.');
        }

        $tanpaWali = (int) ($payload['meta']['tanpa_wali'] ?? 0);
        if ($tanpaWali > 0 || $this->adaItemTanpaWali($items)) {
            throw new RuntimeException(self::MESSAGE_TANPA_WALI);
        }

        $gtkByNip = $this->resolveGtkMap($items);

        return DB::transaction(function () use ($items, $tahunAjaran, $gtkByNip) {
            $created = 0;
            $updated = 0;

            foreach ($items as $item) {
                $kelasId = (int) ($item['kelas_id'] ?? 0);
                $tingkat = trim((string) ($item['tingkat'] ?? ''));
                $nama = trim((string) ($item['nama'] ?? ''));
                $nip = trim((string) ($item['wali']['nip'] ?? ''));

                if ($kelasId < 1 || $tingkat === '' || $nama === '' || $nip === '') {
                    throw new RuntimeException('Payload rombel SimpatiSans tidak lengkap.');
                }

                $gtkId = $gtkByNip[$nip] ?? null;
                if ($gtkId === null) {
                    throw new RuntimeException(
                        'GTK dengan NIP '.$nip.' belum ada di Madani. Sinkronkan data guru terlebih dahulu.'
                    );
                }

                $rombel = Rombel::query()
                    ->where('source_simpatisans_kelas_id', $kelasId)
                    ->first();

                if (! $rombel) {
                    $rombel = Rombel::query()
                        ->where('tahun_ajaran_id', $tahunAjaran->id)
                        ->where('tingkat', $tingkat)
                        ->where('nama', $nama)
                        ->first();
                }

                $attributes = [
                    'tahun_ajaran_id' => $tahunAjaran->id,
                    'tingkat' => $tingkat,
                    'nama' => $nama,
                    'gtk_id' => $gtkId,
                    'source_simpatisans_kelas_id' => $kelasId,
                ];

                if ($rombel) {
                    $rombel->update($attributes);
                    $updated++;
                } else {
                    Rombel::query()->create($attributes);
                    $created++;
                }
            }

            return [
                'created' => $created,
                'updated' => $updated,
                'total' => count($items),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchPayload(): array
    {
        $baseUrl = rtrim((string) config('services.simpatisans.base_url', ''), '/');
        $secret = (string) config('services.simpatisans.secret', '');

        if ($baseUrl === '' || $secret === '') {
            throw new RuntimeException('Konfigurasi SimpatiSans belum lengkap (SIMPATISANS_API_URL / MADANI_INTROSPECT_SECRET).');
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->connectTimeout(5)
                ->timeout(30)
                ->withHeaders(['X-Madani-Introspect-Secret' => $secret])
                ->acceptJson()
                ->get('/madani/rombels')
                ->throw();
        } catch (ConnectionException $e) {
            throw new RuntimeException('Tidak dapat terhubung ke SimpatiSans: '.$e->getMessage(), 0, $e);
        } catch (RequestException $e) {
            $message = $e->response?->json('message') ?: $e->getMessage();
            throw new RuntimeException('Gagal mengambil rombel dari SimpatiSans: '.$message, 0, $e);
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException('Respons SimpatiSans tidak valid.');
        }

        return $json;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function adaItemTanpaWali(array $items): bool
    {
        foreach ($items as $item) {
            if (! is_array($item['wali'] ?? null)) {
                return true;
            }

            if (trim((string) ($item['wali']['nip'] ?? '')) === '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, int>
     */
    private function resolveGtkMap(array $items): array
    {
        $nips = collect($items)
            ->map(fn (array $item) => trim((string) ($item['wali']['nip'] ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return Gtk::query()
            ->whereIn('nip', $nips)
            ->pluck('id', 'nip')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
