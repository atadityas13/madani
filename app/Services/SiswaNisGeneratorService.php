<?php

namespace App\Services;

use App\Models\Madrasah;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SiswaNisGeneratorService
{
    /**
     * @return array{generated: int, pesan: string}
     */
    public function generateUntukAngkatan(string $angkatan): array
    {
        $angkatan = strtoupper(trim($angkatan));
        if (! in_array($angkatan, ['VII', 'VIII', 'IX'], true)) {
            throw ValidationException::withMessages([
                'angkatan' => 'Angkatan tidak valid.',
            ]);
        }

        $nsm = $this->nsmDigits();
        if ($nsm === '') {
            throw ValidationException::withMessages([
                'angkatan' => 'NSM madrasah belum diisi. Lengkapi di Kelembagaan → Identitas.',
            ]);
        }

        $tahunKode = $this->kodeTahunDariTaAktif();
        $prefix = $nsm.$tahunKode;

        $siswaTanpaNis = Siswa::query()
            ->where('angkatan', $angkatan)
            ->where(function ($query) {
                $query->whereNull('nis')->orWhere('nis', '');
            })
            ->orderBy('nama')
            ->get();

        if ($siswaTanpaNis->isEmpty()) {
            return [
                'generated' => 0,
                'pesan' => 'Tidak ada siswa angkatan '.$angkatan.' yang belum memiliki NIS.',
            ];
        }

        $next = $this->urutanBerikutnya($prefix);

        DB::transaction(function () use ($siswaTanpaNis, $prefix, &$next): void {
            foreach ($siswaTanpaNis as $siswa) {
                if ($next > 9999) {
                    throw ValidationException::withMessages([
                        'angkatan' => 'Kuota urutan NIS (4 digit) sudah penuh untuk tahun ini.',
                    ]);
                }

                $nis = $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
                $siswa->update(['nis' => $nis]);
                $next++;
            }
        });

        $jumlah = $siswaTanpaNis->count();

        return [
            'generated' => $jumlah,
            'pesan' => "Berhasil generate NIS untuk {$jumlah} siswa angkatan {$angkatan}.",
        ];
    }

    public function jumlahTanpaNis(): int
    {
        return Siswa::query()
            ->where(function ($query) {
                $query->whereNull('nis')->orWhere('nis', '');
            })
            ->count();
    }

    /**
     * @return array<string, int>
     */
    public function jumlahTanpaNisPerAngkatan(): array
    {
        $rows = Siswa::query()
            ->selectRaw('angkatan, COUNT(*) as total')
            ->where(function ($query) {
                $query->whereNull('nis')->orWhere('nis', '');
            })
            ->whereIn('angkatan', array_keys(config('emis.tingkat_rombel')))
            ->groupBy('angkatan')
            ->pluck('total', 'angkatan');

        $hasil = [];
        foreach (array_keys(config('emis.tingkat_rombel')) as $angkatan) {
            $hasil[$angkatan] = (int) ($rows[$angkatan] ?? 0);
        }

        return $hasil;
    }

    public function nsmDigits(): string
    {
        return preg_replace('/\D+/', '', (string) Madrasah::saatIni()->nsm) ?? '';
    }

    public function kodeTahunDariTaAktif(): string
    {
        $tahun = TahunAjaran::aktif();
        if ($tahun === null || ! filled($tahun->nama)) {
            throw ValidationException::withMessages([
                'angkatan' => 'Tahun ajaran aktif belum diatur.',
            ]);
        }

        if (preg_match('/(\d{4})/', (string) $tahun->nama, $matches) !== 1) {
            throw ValidationException::withMessages([
                'angkatan' => 'Format tahun ajaran aktif tidak valid.',
            ]);
        }

        return substr($matches[1], -2);
    }

    private function urutanBerikutnya(string $prefix): int
    {
        $panjang = strlen($prefix) + 4;

        $candidates = Siswa::query()
            ->where('nis', 'like', $prefix.'%')
            ->whereRaw('LENGTH(nis) = ?', [$panjang])
            ->pluck('nis');

        $max = 0;
        foreach ($candidates as $nis) {
            $suffix = substr((string) $nis, -4);
            if (ctype_digit($suffix)) {
                $max = max($max, (int) $suffix);
            }
        }

        return $max + 1;
    }
}
