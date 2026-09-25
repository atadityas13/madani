<?php

namespace App\Services;

use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\SiswaMutasi;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MutasiService
{
    /**
     * @param  array{
     *     tanggal?: string|null,
     *     alasan: string,
     *     jenis_sekolah: string,
     *     nomor_dokumen_emis?: string|null,
     *     nama_sekolah: string,
     *     nama: string,
     *     nisn: string,
     *     nik: string,
     *     tempat_lahir: string,
     *     tanggal_lahir: string,
     *     jenis_kelamin: string,
     *     angkatan: string,
     *     wali_dari: string,
     *     nama_ortu: string,
     *     pekerjaan: string,
     *     no_hp?: string|null,
     *     alamat?: string|null,
     *     desa?: string|null,
     *     kecamatan?: string|null,
     *     kota?: string|null,
     *     provinsi?: string|null,
     *     kode_pos?: string|null,
     *     rt?: string|null,
     *     rw?: string|null,
     * }  $data
     */
    public function storeMasuk(array $data, User $user): SiswaMutasi
    {
        $nisn = $data['nisn'];
        $bentrok = Siswa::query()
            ->where('nisn', $nisn)
            ->where('status_keaktifan', '!=', 'nonaktif')
            ->exists();

        if ($bentrok) {
            throw ValidationException::withMessages([
                'nisn' => 'NISN sudah dipakai siswa aktif.',
            ]);
        }

        $tahun = TahunAjaran::aktif();

        return DB::transaction(function () use ($data, $user, $tahun) {
            $siswa = Siswa::query()->create([
                'nama' => $data['nama'],
                'nisn' => $data['nisn'],
                'punya_nisn' => true,
                'nik' => $data['nik'],
                'punya_nik' => true,
                'tempat_lahir' => $data['tempat_lahir'],
                'tanggal_lahir' => $data['tanggal_lahir'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'angkatan' => $data['angkatan'],
                'no_hp' => $data['no_hp'] ?? null,
                'tidak_punya_hp' => blank($data['no_hp'] ?? null),
                'status_keaktifan' => 'aktif_tanpa_rombel',
            ]);

            foreach (['ayah', 'ibu', 'wali'] as $peran) {
                $siswa->orangTuas()->create(['peran' => $peran]);
            }

            $this->isiOrangTuaDariWali($siswa, $data);

            if ($tahun) {
                $siswa->periodiks()->create([
                    'tahun_ajaran_id' => $tahun->id,
                    'tanggal_masuk' => $data['tanggal'] ?? now()->toDateString(),
                    'alasan_masuk' => 'Pindahan',
                    'nama_sekolah_asal' => $data['nama_sekolah'],
                    'npsn_asal' => $data['jenis_sekolah'] === SiswaMutasi::SEKOLAH_MADRASAH
                        ? ($data['nomor_dokumen_emis'] ?? null)
                        : null,
                    'alamat' => $data['alamat'] ?? null,
                    'rt' => $data['rt'] ?? null,
                    'rw' => $data['rw'] ?? null,
                    'desa' => $data['desa'] ?? null,
                    'kecamatan' => $data['kecamatan'] ?? null,
                    'kota' => $data['kota'] ?? null,
                    'provinsi' => $data['provinsi'] ?? null,
                    'kode_pos' => $data['kode_pos'] ?? null,
                ]);
            }

            return SiswaMutasi::query()->create([
                'jenis' => SiswaMutasi::JENIS_MASUK,
                'siswa_id' => $siswa->id,
                'tanggal' => $data['tanggal'] ?? now()->toDateString(),
                'alasan' => $data['alasan'],
                'jenis_sekolah' => $data['jenis_sekolah'],
                'nomor_dokumen_emis' => $data['jenis_sekolah'] === SiswaMutasi::SEKOLAH_MADRASAH
                    ? ($data['nomor_dokumen_emis'] ?? null)
                    : null,
                'nama_sekolah' => $data['nama_sekolah'],
                'rombel_id' => null,
                'tahun_ajaran_id' => $tahun?->id,
                'dicatat_oleh' => $user->id,
            ]);
        });
    }

    /**
     * @param  array{
     *     siswa_id: string,
     *     tanggal?: string|null,
     *     alasan: string,
     *     jenis_sekolah: string,
     *     nomor_dokumen_emis?: string|null,
     *     nama_sekolah: string,
     * }  $data
     */
    public function storeKeluar(array $data, User $user): SiswaMutasi
    {
        return $this->storeNonaktif($data, $user, SiswaMutasi::JENIS_KELUAR);
    }

    /**
     * @param  array{
     *     siswa_id: string,
     *     tanggal?: string|null,
     *     alasan: string,
     * }  $data
     */
    public function storeDo(array $data, User $user): SiswaMutasi
    {
        return $this->storeNonaktif($data, $user, SiswaMutasi::JENIS_DO);
    }

    /**
     * @param  array{
     *     siswa_id: string,
     *     tanggal?: string|null,
     *     alasan: string,
     *     jenis_sekolah?: string|null,
     *     nomor_dokumen_emis?: string|null,
     *     nama_sekolah?: string|null,
     * }  $data
     */
    private function storeNonaktif(array $data, User $user, string $jenis): SiswaMutasi
    {
        $siswa = Siswa::query()->findOrFail($data['siswa_id']);

        if ($siswa->status_keaktifan === 'nonaktif') {
            throw ValidationException::withMessages([
                'siswa_id' => 'Siswa sudah nonaktif.',
            ]);
        }

        $sudahNonaktif = SiswaMutasi::query()
            ->where('siswa_id', $siswa->id)
            ->whereIn('jenis', [SiswaMutasi::JENIS_KELUAR, SiswaMutasi::JENIS_DO])
            ->exists();

        if ($sudahNonaktif) {
            throw ValidationException::withMessages([
                'siswa_id' => 'Siswa sudah memiliki catatan mutasi keluar atau dropout.',
            ]);
        }

        $tahun = TahunAjaran::aktif();
        $rombelAktif = $siswa->rombels()
            ->wherePivot('status', 'aktif')
            ->latest('rombel_siswas.id')
            ->first();

        return DB::transaction(function () use ($data, $user, $siswa, $tahun, $rombelAktif, $jenis) {
            if ($rombelAktif) {
                DB::table('rombel_siswas')
                    ->where('siswa_id', $siswa->id)
                    ->where('status', 'aktif')
                    ->update(['status' => 'nonaktif']);
            }

            $siswa->update([
                'status_keaktifan' => 'nonaktif',
                'tanggal_nonaktif' => $data['tanggal'] ?? now()->toDateString(),
                'alasan_nonaktif' => $data['alasan'],
            ]);

            $jenisSekolah = $jenis === SiswaMutasi::JENIS_KELUAR
                ? ($data['jenis_sekolah'] ?? null)
                : null;

            return SiswaMutasi::query()->create([
                'jenis' => $jenis,
                'siswa_id' => $siswa->id,
                'tanggal' => $data['tanggal'] ?? now()->toDateString(),
                'alasan' => $data['alasan'],
                'jenis_sekolah' => $jenisSekolah,
                'nomor_dokumen_emis' => $jenisSekolah === SiswaMutasi::SEKOLAH_MADRASAH
                    ? ($data['nomor_dokumen_emis'] ?? null)
                    : null,
                'nama_sekolah' => $jenis === SiswaMutasi::JENIS_KELUAR
                    ? ($data['nama_sekolah'] ?? null)
                    : null,
                'rombel_id' => $rombelAktif?->id,
                'tahun_ajaran_id' => $tahun?->id,
                'dicatat_oleh' => $user->id,
            ]);
        });
    }

    public function batalkanMasuk(SiswaMutasi $mutasi): void
    {
        if (! $mutasi->isMasuk()) {
            throw ValidationException::withMessages([
                'mutasi' => 'Hanya mutasi masuk yang dapat dibatalkan lewat aksi ini.',
            ]);
        }

        $siswa = $mutasi->siswa;

        if ($siswa === null) {
            $mutasi->delete();

            return;
        }

        if (filled($siswa->nis)) {
            throw ValidationException::withMessages([
                'mutasi' => 'Siswa sudah memiliki NIS. Batalkan mutasi masuk tidak diizinkan. Gunakan Mutasi keluar atau DO.',
            ]);
        }

        DB::transaction(function () use ($mutasi, $siswa) {
            $mutasi->delete();
            $siswa->forceDelete();
        });
    }

    public function batalkanNonaktif(SiswaMutasi $mutasi): void
    {
        if (! $mutasi->isNonaktif()) {
            throw ValidationException::withMessages([
                'mutasi' => 'Hanya mutasi keluar atau dropout yang dapat dibatalkan lewat aksi ini.',
            ]);
        }

        $siswa = $mutasi->siswa;

        if ($siswa === null) {
            $mutasi->delete();

            return;
        }

        DB::transaction(function () use ($mutasi, $siswa) {
            $rombel = $mutasi->rombel_id
                ? Rombel::query()->find($mutasi->rombel_id)
                : null;

            $bisaRestore = $rombel !== null
                && ($siswa->angkatan === null || $siswa->angkatan === $rombel->tingkat);

            if ($bisaRestore) {
                DB::table('rombel_siswas')
                    ->where('siswa_id', $siswa->id)
                    ->where('status', 'aktif')
                    ->whereIn(
                        'rombel_id',
                        Rombel::query()->where('tahun_ajaran_id', $rombel->tahun_ajaran_id)->select('id')
                    )
                    ->update(['status' => 'nonaktif']);

                $rombel->siswas()->syncWithoutDetaching([
                    $siswa->id => ['status' => 'aktif'],
                ]);

                $siswa->update([
                    'status_keaktifan' => 'aktif',
                    'tanggal_nonaktif' => null,
                    'alasan_nonaktif' => null,
                ]);
            } else {
                $siswa->update([
                    'status_keaktifan' => 'aktif_tanpa_rombel',
                    'tanggal_nonaktif' => null,
                    'alasan_nonaktif' => null,
                ]);
            }

            $mutasi->delete();
        });
    }

    /**
     * @param  array{
     *     wali_dari: string,
     *     nama_ortu: string,
     *     pekerjaan: string,
     *     no_hp?: string|null,
     *     alamat?: string|null,
     *     desa?: string|null,
     *     kecamatan?: string|null,
     *     kota?: string|null,
     *     provinsi?: string|null,
     *     kode_pos?: string|null,
     *     rt?: string|null,
     *     rw?: string|null,
     * }  $data
     */
    private function isiOrangTuaDariWali(Siswa $siswa, array $data): void
    {
        $kontak = [
            'nama' => $data['nama_ortu'],
            'pekerjaan' => $data['pekerjaan'],
            'no_hp' => $data['no_hp'] ?? null,
            'tidak_punya_hp' => blank($data['no_hp'] ?? null),
            'alamat' => $data['alamat'] ?? null,
            'rt' => $data['rt'] ?? null,
            'rw' => $data['rw'] ?? null,
            'desa' => $data['desa'] ?? null,
            'kecamatan' => $data['kecamatan'] ?? null,
            'kota' => $data['kota'] ?? null,
            'provinsi' => $data['provinsi'] ?? null,
            'kode_pos' => $data['kode_pos'] ?? null,
        ];

        $waliDari = $data['wali_dari'];

        if ($waliDari === 'ayah') {
            $siswa->orangTuas()->where('peran', 'ayah')->update(array_merge($kontak, [
                'status_hidup' => 'hidup',
                'status' => 'hidup',
            ]));
            $siswa->orangTuas()->where('peran', 'wali')->update(array_merge($kontak, [
                'status' => 'Sama dengan ayah kandung',
                'hubungan' => 'Ayah kandung',
                'status_hidup' => 'hidup',
            ]));

            return;
        }

        if ($waliDari === 'ibu') {
            $siswa->orangTuas()->where('peran', 'ibu')->update(array_merge($kontak, [
                'status_hidup' => 'hidup',
                'status' => 'hidup',
            ]));
            $siswa->orangTuas()->where('peran', 'wali')->update(array_merge($kontak, [
                'status' => 'Sama dengan ibu kandung',
                'hubungan' => 'Ibu kandung',
                'status_hidup' => 'hidup',
            ]));

            return;
        }

        $siswa->orangTuas()->where('peran', 'wali')->update(array_merge($kontak, [
            'status' => 'Lainnya',
            'status_hidup' => 'hidup',
        ]));
    }
}
