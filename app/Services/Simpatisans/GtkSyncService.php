<?php

namespace App\Services\Simpatisans;

use App\Models\Gtk;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GtkSyncService
{
    public const SESSION_KEY = 'gtk_simpatisans_snapshot';

    /**
     * Kolom yang hanya diisi bila di Madani masih kosong.
     *
     * @var array<string, string>
     */
    private const KOLOM_JIKA_KOSONG = [
        'nama' => 'Nama',
        'gelar_depan' => 'Gelar depan',
        'gelar_belakang' => 'Gelar belakang',
        'nip' => 'NIP',
        'nuptk' => 'NUPTK',
        'jenis_kelamin' => 'Jenis kelamin',
        'tempat_lahir' => 'Tempat lahir',
        'tanggal_lahir' => 'Tanggal lahir',
        'agama' => 'Agama',
        'nomor_hp' => 'Nomor HP',
        'email' => 'Email',
        'alamat' => 'Alamat',
        'jabatan' => 'Jabatan',
        'golongan' => 'Golongan',
        'status_pegawai' => 'Status pegawai',
        'kode_internal' => 'Kode internal',
        'mapel_ijazah' => 'Mapel ijazah',
        'mapel_sertifikasi' => 'Mapel sertifikasi',
    ];

    /**
     * @var array<string, int>
     */
    private const PANJANG_KOLOM = [
        'nama' => 255,
        'gelar_depan' => 50,
        'gelar_belakang' => 50,
        'nip' => 30,
        'nuptk' => 20,
        'jenis_kelamin' => 1,
        'tempat_lahir' => 100,
        'agama' => 30,
        'nomor_hp' => 30,
        'email' => 120,
        'jabatan' => 100,
        'golongan' => 50,
        'status_pegawai' => 30,
        'kode_internal' => 40,
        'duk' => 40,
        'mapel_ijazah' => 255,
        'mapel_sertifikasi' => 255,
    ];

    /**
     * @return array{
     *     tambah: list<array<string, mixed>>,
     *     perbarui: list<array{gtk: Gtk, perubahan: list<array{kolom: string, label: string, dari: ?string, ke: string}>}>,
     *     hapus: list<array{gtk: Gtk, bisa_dihapus: bool}>,
     *     bentrok: list<array{nama_lokal: string, nip_lokal: ?string, nama_remote: string, nip_remote: string, nuptk: string}>,
     *     peringatan: list<string>,
     *     sama: int,
     *     total_remote: int
     * }
     */
    public function fetchAndRemember(): array
    {
        $paket = $this->fetchRows();
        session([self::SESSION_KEY => $paket['rows']]);

        return $this->bandingkan($paket['rows'], $paket['peringatan']);
    }

    /**
     * @return array{dibuat: int, diperbarui: int}
     */
    public function terapkanDariSesi(): array
    {
        $rows = session(self::SESSION_KEY);

        if (! is_array($rows)) {
            throw new RuntimeException('Bandingkan data Simpatisans terlebih dahulu.');
        }

        return $this->terapkan($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $remoteRows
     * @param  list<string>  $peringatan
     * @return array{
     *     tambah: list<array<string, mixed>>,
     *     perbarui: list<array{gtk: Gtk, perubahan: list<array{kolom: string, label: string, dari: ?string, ke: string}>}>,
     *     hapus: list<array{gtk: Gtk, bisa_dihapus: bool}>,
     *     bentrok: list<array{nama_lokal: string, nip_lokal: ?string, nama_remote: string, nip_remote: string, nuptk: string}>,
     *     peringatan: list<string>,
     *     sama: int,
     *     total_remote: int
     * }
     */
    public function bandingkan(array $remoteRows, array $peringatan = []): array
    {
        $local = Gtk::query()->withCount('rombels')->orderBy('nama')->get();

        /** @var array<string, array<string, mixed>> $remoteByNip */
        $remoteByNip = [];
        foreach ($remoteRows as $row) {
            $kunci = (string) ($row['nip_kunci'] ?? '');
            if ($kunci === '') {
                $peringatan[] = 'Guru '.(string) ($row['nama'] ?? '').' di Simpatisans tidak punya NIP yang bisa dicocokkan, jadi dilewati.';

                continue;
            }

            // Awalan huruf menjaga kunci tetap string. NIP 18 digit kalau jadi kunci array
            // diubah PHP menjadi integer, lalu perbandingan ketat gagal.
            $indeks = 'n:'.$kunci;
            if (isset($remoteByNip[$indeks])) {
                $peringatan[] = 'NIP '.(string) $row['nip'].' muncul lebih dari sekali di Simpatisans. Baris berikutnya diabaikan.';

                continue;
            }

            $remoteByNip[$indeks] = $row;
        }

        /** @var array<int, true> $sudahCocok */
        $sudahCocok = [];
        /** @var array<int, true> $bentrokIds */
        $bentrokIds = [];
        $tambah = [];
        $perbarui = [];
        $bentrok = [];
        $sama = 0;

        foreach ($remoteByNip as $row) {
            $kunci = (string) ($row['nip_kunci'] ?? '');
            $cocokNip = $local->filter(
                fn (Gtk $gtk): bool => $this->kunci((string) $gtk->nip) === $kunci
            )->values();

            if ($cocokNip->isNotEmpty()) {
                foreach ($cocokNip as $gtk) {
                    $this->catatCocok($gtk, $row, $perbarui, $sama);
                    $sudahCocok[$gtk->id] = true;
                }

                continue;
            }

            $nuptk = (string) ($row['nuptk_kunci'] ?? '');
            $kandidatNuptk = $nuptk === ''
                ? collect()
                : $local->filter(function (Gtk $gtk) use ($nuptk, $sudahCocok): bool {
                    return ! isset($sudahCocok[$gtk->id]) && $this->kunci((string) $gtk->nuptk) === $nuptk;
                })->values();

            if ($kandidatNuptk->count() === 1 && $this->kunci((string) $kandidatNuptk->first()->nip) === '') {
                $gtk = $kandidatNuptk->first();
                $this->catatCocok($gtk, $row, $perbarui, $sama);
                $sudahCocok[$gtk->id] = true;

                continue;
            }

            if ($kandidatNuptk->isNotEmpty()) {
                foreach ($kandidatNuptk as $gtk) {
                    $bentrokIds[$gtk->id] = true;
                    $bentrok[] = [
                        'nama_lokal' => $gtk->namaLengkap,
                        'nip_lokal' => $gtk->nip,
                        'nama_remote' => (string) $row['nama'],
                        'nip_remote' => (string) $row['nip'],
                        'nuptk' => (string) ($row['nuptk'] ?? ''),
                    ];
                }

                continue;
            }

            $tambah[] = $row;
        }

        if ($remoteByNip === []) {
            $peringatan[] = 'Simpatisans tidak mengembalikan guru. Jangan hapus data Madani sebelum memastikan ekspornya benar.';
        }

        $hapus = [];
        foreach ($local as $gtk) {
            if (isset($sudahCocok[$gtk->id]) || isset($bentrokIds[$gtk->id])) {
                continue;
            }

            if ($gtk->jenis !== Gtk::JENIS_GURU) {
                continue;
            }

            $hapus[] = [
                'gtk' => $gtk,
                'bisa_dihapus' => (int) $gtk->rombels_count === 0,
            ];
        }

        return [
            'tambah' => $tambah,
            'perbarui' => $perbarui,
            'hapus' => $hapus,
            'bentrok' => $bentrok,
            'peringatan' => $peringatan,
            'sama' => $sama,
            'total_remote' => count($remoteByNip),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $remoteRows
     * @return array{dibuat: int, diperbarui: int}
     */
    public function terapkan(array $remoteRows): array
    {
        $hasil = $this->bandingkan($remoteRows);

        return DB::transaction(function () use ($hasil): array {
            $dibuat = 0;
            $diperbarui = 0;

            foreach ($hasil['tambah'] as $row) {
                Gtk::query()->create($this->payloadBaru($row));
                $dibuat++;
            }

            foreach ($hasil['perbarui'] as $item) {
                $payload = [];
                foreach ($item['perubahan'] as $ubah) {
                    $payload[$ubah['kolom']] = $ubah['ke'];
                }

                if ($payload !== []) {
                    $item['gtk']->update($payload);
                    $diperbarui++;
                }
            }

            return [
                'dibuat' => $dibuat,
                'diperbarui' => $diperbarui,
            ];
        });
    }

    /**
     * @return array{rows: list<array<string, mixed>>, peringatan: list<string>}
     */
    private function fetchRows(): array
    {
        $baseUrl = rtrim((string) config('services.simpatisans.base_url'), '/');
        $secret = (string) config('services.simpatisans.secret');

        if ($baseUrl === '' || $secret === '') {
            throw new RuntimeException('Konfigurasi SimpatiSans belum lengkap (SIMPATISANS_API_URL / MADANI_INTROSPECT_SECRET).');
        }

        try {
            $response = Http::connectTimeout(5)
                ->timeout(30)
                ->acceptJson()
                ->withHeaders([
                    'X-Madani-Introspect-Secret' => $secret,
                ])
                ->get($baseUrl.'/madani/gurus')
                ->throw();
        } catch (ConnectionException $e) {
            throw new RuntimeException('Tidak dapat terhubung ke SimpatiSans. Periksa jaringan dan URL API.', 0, $e);
        } catch (RequestException $e) {
            throw new RuntimeException('SimpatiSans menolak permintaan data guru. Periksa MADANI_INTROSPECT_SECRET.', 0, $e);
        }

        $data = $response->json('data');
        if (! is_array($data)) {
            throw new RuntimeException('Respons data guru SimpatiSans tidak valid.');
        }

        $rows = [];
        $peringatan = [];
        foreach ($data as $row) {
            if (! is_array($row)) {
                $peringatan[] = 'Ada baris guru Simpatisans yang bukan data, jadi dilewati.';

                continue;
            }

            $normalized = $this->normalisasiRemote($row);
            if ($normalized === null) {
                $peringatan[] = 'Ada guru Simpatisans tanpa nama dan NIP, jadi dilewati.';

                continue;
            }

            $rows[] = $normalized;
        }

        return [
            'rows' => $rows,
            'peringatan' => $peringatan,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    private function normalisasiRemote(array $row): ?array
    {
        $nip = $this->teks($row['nip'] ?? null, self::PANJANG_KOLOM['nip']);
        $nama = $this->teks($row['nama'] ?? null, self::PANJANG_KOLOM['nama']);
        if ($nip === null && $nama === null) {
            return null;
        }

        $nipKunci = $this->kunci($nip);
        if ($nip !== null && $nipKunci !== '' && $nip !== $nipKunci && strlen($nipKunci) <= self::PANJANG_KOLOM['nip']) {
            $nip = $nipKunci;
        }

        $duk = $row['duk'] ?? null;
        $dukTeks = null;
        if ($duk !== null && $duk !== '' && is_numeric($duk)) {
            $dukTeks = (string) (int) $duk;
            if (strlen($dukTeks) > self::PANJANG_KOLOM['duk']) {
                $dukTeks = null;
            }
        }

        return [
            'nip' => $nip,
            'nip_kunci' => $nipKunci,
            'nama' => $nama ?? ('Guru '.($nip ?? '')),
            'gelar_depan' => $this->teks($row['gelar_depan'] ?? null, self::PANJANG_KOLOM['gelar_depan']),
            'gelar_belakang' => $this->teks($row['gelar_belakang'] ?? null, self::PANJANG_KOLOM['gelar_belakang']),
            'nuptk' => $this->teks($row['nuptk'] ?? null, self::PANJANG_KOLOM['nuptk']),
            'nuptk_kunci' => $this->kunci(isset($row['nuptk']) ? (string) $row['nuptk'] : null),
            'jenis_kelamin' => $this->jenisKelamin(isset($row['jenis_kelamin']) ? (string) $row['jenis_kelamin'] : null),
            'tempat_lahir' => $this->teks($row['tempat_lahir'] ?? null, self::PANJANG_KOLOM['tempat_lahir']),
            'tanggal_lahir' => $this->tanggal($row['tanggal_lahir'] ?? null),
            'agama' => $this->teks($row['agama'] ?? null, self::PANJANG_KOLOM['agama']),
            'nomor_hp' => $this->teks($row['nomor_hp'] ?? null, self::PANJANG_KOLOM['nomor_hp']),
            'email' => $this->teks($row['email'] ?? null, self::PANJANG_KOLOM['email']),
            'alamat' => $this->teks($row['alamat'] ?? null, 5000),
            'jabatan' => $this->teks($row['jabatan'] ?? null, self::PANJANG_KOLOM['jabatan']),
            'golongan' => $this->teks($row['golongan'] ?? null, self::PANJANG_KOLOM['golongan']),
            'status_pegawai' => $this->teks($row['status_pegawai'] ?? null, self::PANJANG_KOLOM['status_pegawai']),
            'kode_internal' => $this->teks($row['kode_guru'] ?? null, self::PANJANG_KOLOM['kode_internal']),
            'duk' => $dukTeks,
            'mapel_ijazah' => $this->teks($row['mapel_ijazah'] ?? null, self::PANJANG_KOLOM['mapel_ijazah']),
            'mapel_sertifikasi' => $this->teks($row['mapel_sertifikasi'] ?? null, self::PANJANG_KOLOM['mapel_sertifikasi']),
            'status_sertifikasi' => (bool) ($row['status_sertifikasi'] ?? false),
            'is_bk' => (bool) ($row['is_bk'] ?? false),
        ];
    }

    /**
     * @param  list<array{gtk: Gtk, perubahan: list<array{kolom: string, label: string, dari: ?string, ke: string}>}>  $perbarui
     * @param  array<string, mixed>  $row
     */
    private function catatCocok(Gtk $gtk, array $row, array &$perbarui, int &$sama): void
    {
        $perubahan = $this->perubahan($gtk, $row);
        if ($perubahan === []) {
            $sama++;

            return;
        }

        $perbarui[] = [
            'gtk' => $gtk,
            'perubahan' => $perubahan,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<array{kolom: string, label: string, dari: ?string, ke: string}>
     */
    private function perubahan(Gtk $gtk, array $row): array
    {
        $hasil = [];

        $dukRemote = $row['duk'];
        $dukLokal = trim((string) $gtk->duk);
        if (is_string($dukRemote) && $dukRemote !== '' && $dukRemote !== $dukLokal) {
            $hasil[] = [
                'kolom' => 'duk',
                'label' => 'DUK',
                'dari' => $dukLokal === '' ? null : $dukLokal,
                'ke' => $dukRemote,
            ];
        }

        foreach (self::KOLOM_JIKA_KOSONG as $kolom => $label) {
            $baru = $row[$kolom] ?? null;
            if ($this->kosong($baru)) {
                continue;
            }

            $lama = $gtk->getAttribute($kolom);
            if ($kolom === 'tanggal_lahir' && $lama !== null) {
                continue;
            }

            if (! $this->kosong($lama)) {
                continue;
            }

            $hasil[] = [
                'kolom' => $kolom,
                'label' => $label,
                'dari' => null,
                'ke' => (string) $baru,
            ];
        }

        return $hasil;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function payloadBaru(array $row): array
    {
        $payload = [
            'nama' => (string) $row['nama'],
            'jenis' => Gtk::JENIS_GURU,
            'status' => 'aktif',
            'status_sertifikasi' => (bool) $row['status_sertifikasi'],
            'is_bk' => (bool) $row['is_bk'],
        ];

        if (is_string($row['duk']) && $row['duk'] !== '') {
            $payload['duk'] = $row['duk'];
        }

        foreach (array_keys(self::KOLOM_JIKA_KOSONG) as $kolom) {
            if ($kolom === 'nama' || $this->kosong($row[$kolom] ?? null)) {
                continue;
            }

            $payload[$kolom] = $row[$kolom];
        }

        return $payload;
    }

    private function kunci(?string $nilai): string
    {
        $nilai = trim((string) $nilai);
        if ($nilai === '') {
            return '';
        }

        $angka = preg_replace('/\D+/', '', $nilai) ?? '';
        if (strlen($angka) >= 8) {
            return $angka;
        }

        $mentah = strtoupper((string) (preg_replace('/\s+/', '', $nilai) ?? ''));

        return strlen($mentah) >= 8 ? $mentah : '';
    }

    private function kosong(mixed $nilai): bool
    {
        if ($nilai === null) {
            return true;
        }

        $teks = trim((string) $nilai);

        return $teks === '' || $teks === '-' || $teks === '—';
    }

    private function teks(mixed $nilai, int $maks): ?string
    {
        if ($this->kosong($nilai)) {
            return null;
        }

        $teks = trim((string) $nilai);
        if (mb_strlen($teks) > $maks) {
            return null;
        }

        return $teks;
    }

    private function jenisKelamin(?string $nilai): ?string
    {
        $nilai = strtoupper(trim((string) $nilai));

        return match (true) {
            in_array($nilai, ['L', 'LAKI-LAKI', 'LAKI LAKI'], true) => 'L',
            in_array($nilai, ['P', 'PEREMPUAN'], true) => 'P',
            default => null,
        };
    }

    private function tanggal(mixed $nilai): ?string
    {
        if ($this->kosong($nilai)) {
            return null;
        }

        $teks = trim((string) $nilai);
        $waktu = strtotime($teks);
        if ($waktu === false) {
            return null;
        }

        return date('Y-m-d', $waktu);
    }
}
