<?php

namespace App\Services;

use App\Models\Madrasah;
use App\Models\OrangTua;
use App\Models\Siswa;
use App\Models\SiswaPeriodik;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class PortofolioPdfService
{
    public function download(Siswa $siswa): Response
    {
        return $this->makePdf($siswa)->download($this->filename($siswa));
    }

    public function stream(Siswa $siswa): Response
    {
        return $this->makePdf($siswa)->stream($this->filename($siswa));
    }

    private function makePdf(Siswa $siswa): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('siswa.portofolio-pdf', $this->viewData($siswa))
            ->setPaper('a4', 'portrait');
    }

    private function filename(Siswa $siswa): string
    {
        $nama = preg_replace('/[^A-Za-z0-9 _-]+/', '', $siswa->nama) ?: 'siswa';

        return trim($nama).' - Portofolio.pdf';
    }

    /**
     * @return array<string, mixed>
     */
    public function viewData(Siswa $siswa): array
    {
        $siswa->load([
            'orangTuas', 'periodiks.tahunAjaran', 'rombels.tahunAjaran',
            'beasiswas', 'prestasis', 'ayah', 'ibu', 'wali',
        ]);

        $madrasah = Madrasah::saatIni();
        $periodik = $siswa->periodikAktif();
        $generatedAt = now();

        $verifyUrl = URL::temporarySignedRoute(
            'portofolio.cek',
            $generatedAt->copy()->addDays(90),
            ['siswa' => $siswa->id],
        );

        return [
            'siswa' => $siswa,
            'madrasah' => $madrasah,
            'periodik' => $periodik,
            'masuk' => $siswa->dataMasukAkademik(),
            'logoDataUri' => $this->r2DataUri($madrasah->logo_path),
            'fotoDataUri' => $this->r2DataUri($siswa->foto),
            'qrSvg' => $this->qrSvg($verifyUrl),
            'generatedAt' => $generatedAt,
            'identitasRows' => $this->identitasRows($siswa, $periodik),
            'alamatRows' => $this->alamatRows($periodik),
            'ayahRows' => $this->ortuRows($siswa->ayah, 'ayah'),
            'ibuRows' => $this->ortuRows($siswa->ibu, 'ibu'),
            'waliRows' => $this->ortuRows($siswa->wali, 'wali'),
            'aktivitas' => $this->aktivitasRows($siswa, $madrasah),
            'beasiswas' => $siswa->beasiswas,
            'prestasis' => $siswa->prestasis,
        ];
    }

    /**
     * @return list<array{0: string, 1: ?string}>
     */
    private function identitasRows(Siswa $siswa, ?SiswaPeriodik $periodik): array
    {
        $jk = match ($siswa->jenis_kelamin) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => $siswa->jenis_kelamin,
        };

        $imunisasi = collect($periodik?->imunisasi ?? [])->filter()->implode(', ');
        $disabilitas = $periodik?->disabilitasLabel();
        if (is_array($periodik?->disabilitas)) {
            $disabilitas = collect($periodik->disabilitas)->filter()->implode(', ');
        }

        return [
            ['NISN', $siswa->nisn],
            ['NIS', $siswa->nis],
            ['NIK', $siswa->nik],
            ['Tempat lahir', $siswa->tempat_lahir],
            ['Tanggal lahir', $siswa->tanggal_lahir?->locale('id')->translatedFormat('d F Y')],
            ['Jenis kelamin', $jk],
            ['Jumlah saudara', $siswa->jumlah_saudara !== null ? (string) $siswa->jumlah_saudara : null],
            ['Anak ke', $siswa->anak_ke !== null ? (string) $siswa->anak_ke : null],
            ['Agama', $siswa->agama],
            ['Cita-cita', $siswa->cita_cita],
            ['Hobi', $siswa->hobi],
            ['No HP', $siswa->tidak_punya_hp ? 'Tidak punya' : $siswa->no_hp],
            ['Email', $siswa->tidak_punya_email ? 'Tidak punya' : $siswa->email],
            ['Pernah TK/RA', $periodik?->pernah_tk_ra ? 'Ya' : ($periodik ? 'Tidak' : null)],
            ['Pernah PAUD', $periodik?->pernah_paud ? 'Ya' : ($periodik ? 'Tidak' : null)],
            ['Imunisasi', $imunisasi !== '' ? $imunisasi : null],
            ['Kebutuhan khusus', $periodik?->kebutuhanKhususLabel() ?: $periodik?->kebutuhan_khusus_lainnya],
            ['Disabilitas', $disabilitas ?: $periodik?->disabilitas_lainnya],
            ['No KK', $periodik?->no_kk],
            ['Kepala keluarga', $periodik?->kepala_keluarga],
            ['Pembiaya sekolah', $periodik?->pembiaya],
            ['No KIP', $periodik?->tidak_punya_kip ? 'Tidak punya' : $periodik?->no_kip],
            ['No KKS', $periodik?->tidak_punya_kks ? 'Tidak punya' : $periodik?->no_kks],
            ['No PKH', $periodik?->tidak_punya_pkh ? 'Tidak punya' : $periodik?->no_pkh],
            ['Penghasilan gabungan ortu', $periodik?->penghasilan_gabungan],
        ];
    }

    /**
     * @return list<array{0: string, 1: ?string}>
     */
    private function alamatRows(?SiswaPeriodik $periodik): array
    {
        return [
            ['Status tempat tinggal', $periodik?->tempat_tinggal],
            ['Alamat', $this->formatAlamat($periodik)],
            ['Jarak ke madrasah', $periodik?->jarak],
            ['Waktu tempuh', $periodik?->waktu_tempuh],
            ['Transportasi', $periodik?->transportasi],
        ];
    }

    /**
     * @return list<array{0: string, 1: ?string}>
     */
    private function ortuRows(?OrangTua $ortu, string $peran): array
    {
        if (! $ortu) {
            return [
                ['Nama lengkap', null],
            ];
        }

        $rows = [
            ['Nama lengkap', $ortu->nama],
        ];

        if ($peran === 'wali') {
            $rows[] = ['Status wali', $ortu->status];
            $rows[] = ['Hubungan', $ortu->hubungan];
        } else {
            $rows[] = ['Status hidup', $ortu->status_hidup];
        }

        $rows = array_merge($rows, [
            ['NIK', $ortu->nik],
            ['Tempat lahir', $ortu->tempat_lahir],
            ['Tanggal lahir', $ortu->tanggal_lahir?->locale('id')->translatedFormat('d F Y')],
            ['Pendidikan terakhir', $ortu->pendidikan],
            ['Pekerjaan utama', $ortu->pekerjaan],
            ['Penghasilan', $ortu->penghasilan],
            ['No HP', $ortu->tidak_punya_hp ? 'Tidak punya' : $ortu->no_hp],
            ['Status tempat tinggal', $ortu->status_tempat_tinggal],
            ['Alamat', $this->formatAlamat($ortu)],
        ]);

        return $rows;
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function aktivitasRows(Siswa $siswa, Madrasah $madrasah): array
    {
        $masuk = $siswa->dataMasukAkademik();
        $keterangan = $masuk['status'] ?? null;
        if (($masuk['status'] ?? null) === 'Pindahan' && filled($masuk['nama_sekolah_asal'] ?? null)) {
            $keterangan = 'Pindahan — '.$masuk['nama_sekolah_asal'];
        }

        return $siswa->rombels
            ->sortByDesc(fn ($rombel) => $rombel->tahunAjaran?->tanggal_mulai?->format('Ymd') ?? '0')
            ->values()
            ->map(fn ($rombel) => [
                'tahun_ajaran' => $rombel->tahunAjaran?->label(),
                'tingkat' => $rombel->tingkat !== null ? 'Kelas '.$rombel->tingkat : null,
                'rombel' => $rombel->nama,
                'status' => $rombel->pivot->status,
                'keterangan' => $keterangan,
                'nsm' => $madrasah->nsm,
                'npsn' => $madrasah->npsn,
            ])
            ->all();
    }

    private function formatAlamat(OrangTua|SiswaPeriodik|null $record): ?string
    {
        if (! $record) {
            return null;
        }

        $parts = array_filter([
            $record->alamat,
            filled($record->blok) ? 'Blok '.$record->blok : null,
            filled($record->rt) || filled($record->rw)
                ? 'RT '.($record->rt ?: '-').'/RW '.($record->rw ?: '-')
                : null,
            $record->desa,
            $record->kecamatan ? 'Kec. '.$record->kecamatan : null,
            $record->kota,
            $record->provinsi,
            $record->kode_pos,
        ], fn ($v) => filled($v));

        return $parts === [] ? null : implode(', ', $parts);
    }

    private function r2DataUri(?string $path): ?string
    {
        if (! filled($path) || ! Storage::disk('r2')->exists($path)) {
            return null;
        }

        $bytes = Storage::disk('r2')->get($path);
        if ($bytes === null || $bytes === '') {
            return null;
        }

        $mime = Storage::disk('r2')->mimeType($path) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }

    private function qrSvg(string $content): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(140, 0),
            new SvgImageBackEnd,
        );

        return (new Writer($renderer))->writeString($content);
    }
}
