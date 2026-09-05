<?php

namespace App\Support;

use App\Models\Siswa;

class PernyataanSiswa
{
    public const VERSI = '2026-09-05';

    public const TEKS_POIN_1 = 'Saya sebagai pemilik data menyatakan dengan sesungguhnya bahwa seluruh data yang saya berikan adalah benar, lengkap, dan dapat dipertanggungjawabkan. Apabila di kemudian hari ditemukan ketidaksesuaian dan/atau kekeliruan pada data tersebut, saya bersedia menanggung segala konsekuensi hukum maupun administratif sesuai dengan peraturan Madrasah dan peraturan perundang-undangan yang berlaku.';

    public const TEKS_POIN_2 = 'Saya bersedia mematuhi seluruh peraturan dan tata tertib MTsN 11 Majalengka, menjalankan ibadah serta menjaga akhlak mulia, mengikuti kegiatan pembelajaran dengan penuh tanggung jawab, menjaga nama baik madrasah, dan siap menerima sanksi (termasuk pemberhentian) apabila melanggar ketentuan yang berlaku.';

    public const TEKS_PENUTUP_BIODATA = 'Pernyataan biodata ini dibuat untuk dipergunakan sebagaimana mestinya.';

    public static function namaWaliEfektif(Siswa $siswa): string
    {
        $siswa->loadMissing(['ayah', 'ibu', 'wali']);

        $wali = $siswa->wali;
        $status = $wali?->status;

        if ($status === 'Sama dengan ayah kandung') {
            return (string) ($siswa->ayah?->nama ?: $wali?->nama ?: '');
        }

        if ($status === 'Sama dengan ibu kandung') {
            return (string) ($siswa->ibu?->nama ?: $wali?->nama ?: '');
        }

        return (string) ($wali?->nama ?: '');
    }

    /**
     * @return array{versi: string, poin_1: string, poin_2: string, penutup_biodata: string}
     */
    public static function teksAktif(): array
    {
        return [
            'versi' => self::VERSI,
            'poin_1' => self::TEKS_POIN_1,
            'poin_2' => self::TEKS_POIN_2,
            'penutup_biodata' => self::TEKS_PENUTUP_BIODATA,
        ];
    }
}
