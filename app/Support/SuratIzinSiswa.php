<?php

namespace App\Support;

use App\Models\IzinSiswa;
use Carbon\Carbon;

class SuratIzinSiswa
{
    /**
     * @return array{
     *     kota: string,
     *     tanggal_surat: string,
     *     hal: string,
     *     lampiran_label: string,
     *     kepada_yth: string,
     *     wali_kelas: string,
     *     madrasah: string,
     *     di_tempat: string,
     *     salam_pembuka: string,
     *     pengantar: string,
     *     nama_siswa: string,
     *     kelas_siswa: string,
     *     paragraf: string,
     *     penutup: string,
     *     salam_penutup: string,
     *     hormat_kami: string,
     *     peran_penandatangan: string,
     *     nama_wali: string,
     *     ttd_wali_url: ?string,
     *     lampiran_url: ?string,
     *     jenis_bukti: ?string,
     *     punya_lampiran: bool
     * }
     */
    public static function payload(IzinSiswa $izin): array
    {
        $izin->loadMissing(['siswa', 'rombel']);
        $kota = (string) (config('madrasah.kota') ?: 'Majalengka');
        $madrasah = (string) (config('madrasah.nama') ?: 'MTsN 11 Majalengka');
        $tanggalSurat = Carbon::parse($izin->created_at ?? now())->locale('id');
        $tanggalIzin = Carbon::parse($izin->tanggal)->locale('id');
        $punyaLampiran = filled($izin->lampiran_path);
        $jenis = $izin->labelJenis();
        $alasan = trim((string) $izin->alasan);
        $jenisBukti = filled($izin->jenis_bukti) ? trim((string) $izin->jenis_bukti) : null;

        $paragraf = 'Dengan surat ini memberitahukan bahwa anak kami tersebut tidak dapat mengikuti kegiatan pembelajaran di madrasah pada hari ini '
            .$tanggalIzin->translatedFormat('l, j F Y')
            .' karena '.$jenis;

        if ($alasan !== '') {
            $paragraf .= ' ('.$alasan.')';
        }

        $paragraf .= '.';

        if ($punyaLampiran && filled($jenisBukti)) {
            $paragraf .= ' Bersama dengan surat ini, kami turut melampirkan bukti ketidakhadiran berupa '.$jenisBukti.'.';
        }

        $kelas = $izin->rombel?->label() ?? '—';
        $namaWali = filled($izin->nama_wali)
            ? (string) $izin->nama_wali
            : 'Orang tua/wali';

        return [
            'kota' => $kota,
            'tanggal_surat' => $tanggalSurat->translatedFormat('j F Y'),
            'hal' => 'Surat Izin Tidak Mengikuti Pembelajaran',
            'lampiran_label' => $punyaLampiran ? '1 Lembar' : '-',
            'kepada_yth' => 'Kepada Yth.',
            'wali_kelas' => 'Bapak/Ibu Wali Kelas '.$kelas,
            'madrasah' => $madrasah,
            'di_tempat' => 'di Tempat',
            'salam_pembuka' => "Assalamu'alaikum Wr. Wb.",
            'pengantar' => 'Saya yang bertanda tangan di bawah ini orang tua/wali dari:',
            'nama_siswa' => (string) ($izin->siswa?->nama ?? '—'),
            'kelas_siswa' => $kelas,
            'paragraf' => $paragraf,
            'penutup' => 'Demikian surat izin ini kami sampaikan. Atas perhatian, pengertian, dan izin yang Bapak/Ibu Guru berikan, kami ucapkan terima kasih.',
            'salam_penutup' => "Wassalamu'alaikum Wr. Wb.",
            'hormat_kami' => 'Hormat kami,',
            'peran_penandatangan' => 'Orang tua/wali',
            'nama_wali' => $namaWali,
            'ttd_wali_url' => filled($izin->ttd_wali_path) ? R2Url::temporary($izin->ttd_wali_path) : null,
            'lampiran_url' => $punyaLampiran ? R2Url::temporary($izin->lampiran_path) : null,
            'jenis_bukti' => $jenisBukti,
            'punya_lampiran' => $punyaLampiran,
        ];
    }
}
