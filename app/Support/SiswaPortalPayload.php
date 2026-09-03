<?php

namespace App\Support;

use App\Models\OrangTua;
use App\Models\RekamDidik;
use App\Models\Siswa;
use App\Models\SiswaPeriodik;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\Storage;

class SiswaPortalPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Siswa $siswa): array
    {
        $siswa->load([
            'orangTuas', 'periodiks.tahunAjaran', 'rombels.tahunAjaran',
            'beasiswas', 'prestasis', 'rekamDidik', 'dokumens', 'ayah', 'ibu', 'wali',
        ]);

        $periodik = $siswa->periodikAktif();
        $rombel = $siswa->rombelAktif();
        $tahun = TahunAjaran::aktif();

        return [
            'id' => $siswa->id,
            'nisn' => $siswa->nisn,
            'nis' => $siswa->nis,
            'nik' => $siswa->nik,
            'nama' => $siswa->nama,
            'tempat_lahir' => $siswa->tempat_lahir,
            'tanggal_lahir' => $siswa->tanggal_lahir?->toDateString(),
            'jenis_kelamin' => $siswa->jenis_kelamin,
            'agama' => $siswa->agama,
            'kewarganegaraan' => $siswa->kewarganegaraan,
            'anak_ke' => $siswa->anak_ke,
            'jumlah_saudara' => $siswa->jumlah_saudara,
            'cita_cita' => $siswa->cita_cita,
            'hobi' => $siswa->hobi,
            'email' => $siswa->email,
            'no_hp' => $siswa->no_hp,
            'tidak_punya_hp' => (bool) $siswa->tidak_punya_hp,
            'status_keaktifan' => $siswa->status_keaktifan,
            'must_change_password' => (bool) $siswa->must_change_password,
            'foto_url' => $siswa->foto ? Storage::disk('public')->url($siswa->foto) : null,
            'tahun_ajaran' => $tahun?->label(),
            'rombel' => $rombel ? [
                'id' => $rombel->id,
                'nama' => $rombel->nama,
                'tingkat' => $rombel->tingkat,
                'program' => $rombel->program,
            ] : null,
            'kelengkapan' => $siswa->kelengkapan(),
            'periodik' => self::periodik($periodik),
            'orang_tua' => [
                'ayah' => self::ortu($siswa->ayah),
                'ibu' => self::ortu($siswa->ibu),
                'wali' => self::ortu($siswa->wali),
            ],
            'rekam_didik' => self::rekam($siswa->rekamDidik),
            'rombels' => $siswa->rombels->map(fn ($item) => [
                'id' => $item->id,
                'nama' => $item->nama,
                'tingkat' => $item->tingkat,
                'program' => $item->program,
                'status' => $item->pivot->status,
                'tahun_ajaran' => $item->tahunAjaran?->label(),
            ])->values(),
            'prestasis' => $siswa->prestasis->map(fn ($item) => [
                'id' => $item->id,
                'nama' => $item->nama,
                'jenis' => $item->jenis,
                'tingkat' => $item->tingkat,
                'tahun' => $item->tahun,
                'penyelenggara' => $item->penyelenggara,
                'sertifikat_url' => $item->sertifikat_path ? Storage::disk('public')->url($item->sertifikat_path) : null,
            ])->values(),
            'beasiswas' => $siswa->beasiswas->map(fn ($item) => [
                'id' => $item->id,
                'tahun' => $item->tahun,
                'kategori' => $item->kategori,
                'nama' => $item->nama,
                'nominal' => $item->nominal,
                'nomor_rekening' => $item->nomor_rekening,
                'bukti_url' => $item->bukti_path ? Storage::disk('public')->url($item->bukti_path) : null,
            ])->values(),
            'dokumen' => $siswa->dokumens->mapWithKeys(fn ($item) => [
                $item->jenis => [
                    'nama_asli' => $item->nama_asli,
                    'url' => Storage::disk('public')->url($item->path),
                ],
            ]),
            'data_masuk' => $siswa->dataMasukAkademik(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function periodik(?SiswaPeriodik $periodik): ?array
    {
        if (! $periodik) {
            return null;
        }

        $data = $periodik->toArray();
        $data['tanggal_masuk'] = $periodik->tanggal_masuk?->toDateString();

        return $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function ortu(?OrangTua $ortu): ?array
    {
        if (! $ortu) {
            return null;
        }

        $data = $ortu->toArray();
        $data['tanggal_lahir'] = $ortu->tanggal_lahir?->toDateString();

        return $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function rekam(?RekamDidik $rekam): ?array
    {
        if (! $rekam) {
            return null;
        }

        $data = $rekam->toArray();
        $data['tanggal_lahir_kk'] = $rekam->tanggal_lahir_kk?->toDateString();
        $data['tanggal_lahir_ijazah'] = $rekam->tanggal_lahir_ijazah?->toDateString();
        $data['tanggal_terbit_ijazah'] = $rekam->tanggal_terbit_ijazah?->toDateString();

        return $data;
    }
}
