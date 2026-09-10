<?php

namespace App\Services\Tunjangan;

use App\Models\Gtk;
use App\Models\TahunAjaran;
use App\Models\TunjanganDokumen;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

class TunjanganDokumenService
{
    public const MAX_PDF_KB = 2048;

    /**
     * @return array<int, string>
     */
    public static function namaBulan(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    /**
     * Urutan bulan tahun pelajaran: Sem I Juli–Des, Sem II Januari–Juni.
     *
     * @return list<array{semester: int, label: string, bulan: array<int, string>}>
     */
    public static function grupBulanSkakpt(): array
    {
        $nama = self::namaBulan();

        return [
            [
                'semester' => 1,
                'label' => 'Semester I (Juli–Desember)',
                'bulan' => [
                    7 => $nama[7],
                    8 => $nama[8],
                    9 => $nama[9],
                    10 => $nama[10],
                    11 => $nama[11],
                    12 => $nama[12],
                ],
            ],
            [
                'semester' => 2,
                'label' => 'Semester II (Januari–Juni)',
                'bulan' => [
                    1 => $nama[1],
                    2 => $nama[2],
                    3 => $nama[3],
                    4 => $nama[4],
                    5 => $nama[5],
                    6 => $nama[6],
                ],
            ],
        ];
    }

    public function assertJenisUpload(string $jenis): void
    {
        if (! in_array($jenis, TunjanganDokumen::jenisUpload(), true)) {
            abort(404);
        }
    }

    public function assertJenisSemua(string $jenis): void
    {
        if (! in_array($jenis, TunjanganDokumen::semuaJenis(), true)) {
            abort(404);
        }
    }

    public function labelJenis(string $jenis): string
    {
        return match ($jenis) {
            TunjanganDokumen::JENIS_SKMT => 'SKMT',
            TunjanganDokumen::JENIS_SKBK => 'SKBK',
            TunjanganDokumen::JENIS_SPTJM => 'SPTJM',
            TunjanganDokumen::JENIS_SKAKPT => 'SKAKPT',
            default => strtoupper($jenis),
        };
    }

    public function deskripsiJenis(string $jenis): ?string
    {
        return match ($jenis) {
            TunjanganDokumen::JENIS_SKMT => 'Surat Keterangan Melaksanakan Tugas',
            TunjanganDokumen::JENIS_SKBK => 'Surat Keterangan Beban Kerja',
            TunjanganDokumen::JENIS_SPTJM => 'Surat Pertanggungjawaban Mutlak',
            TunjanganDokumen::JENIS_SKAKPT => null,
            default => null,
        };
    }

    /**
     * @return list<array{kode: string, judul: string, deskripsi: ?string}>
     */
    public function hubJenisList(): array
    {
        $items = [];
        foreach (TunjanganDokumen::semuaJenis() as $kode) {
            $items[] = [
                'kode' => $kode,
                'judul' => $this->labelJenis($kode),
                'deskripsi' => $this->deskripsiJenis($kode),
            ];
        }

        return $items;
    }

    public function bolehUploadSkakpt(TahunAjaran $tahunAjaran, int $bulan, ?CarbonInterface $sekarang = null): bool
    {
        if ($bulan < 1 || $bulan > 12) {
            return false;
        }

        $sekarang ??= now();
        $tahunKalender = $this->tahunKalenderUntukBulanSkakpt($tahunAjaran, $bulan);
        $akhirBulan = $sekarang->copy()->setDate($tahunKalender, $bulan, 1)->endOfMonth();

        return $sekarang->greaterThan($akhirBulan);
    }

    public function bolehUploadSemester(TahunAjaran $tahunAjaran, int $semester, ?CarbonInterface $sekarang = null): bool
    {
        $sekarang ??= now();

        if ($tahunAjaran->adalahAktif()) {
            if ($semester === 1) {
                return true;
            }

            $tahunKedua = $this->tahunKeduaAjaran($tahunAjaran);

            return $sekarang->year > $tahunKedua
                || ($sekarang->year === $tahunKedua && $sekarang->month >= 1);
        }

        return $tahunAjaran->status === TahunAjaran::STATUS_ARSIP
            || ($tahunAjaran->tanggal_selesai && $tahunAjaran->tanggal_selesai->lt($sekarang));
    }

    public function tahunPertamaAjaran(TahunAjaran $tahunAjaran): int
    {
        if (preg_match('/(\d{4})\s*\/\s*(\d{4})/', (string) $tahunAjaran->nama, $m)) {
            return (int) $m[1];
        }

        if ($tahunAjaran->tanggal_mulai) {
            return (int) $tahunAjaran->tanggal_mulai->year;
        }

        return (int) now()->year;
    }

    public function tahunKeduaAjaran(TahunAjaran $tahunAjaran): int
    {
        if (preg_match('/(\d{4})\s*\/\s*(\d{4})/', (string) $tahunAjaran->nama, $m)) {
            return (int) $m[2];
        }

        if ($tahunAjaran->tanggal_selesai) {
            return (int) $tahunAjaran->tanggal_selesai->year;
        }

        return $this->tahunPertamaAjaran($tahunAjaran) + 1;
    }

    public function tahunKalenderUntukBulanSkakpt(TahunAjaran $tahunAjaran, int $bulan): int
    {
        return $bulan >= 7
            ? $this->tahunPertamaAjaran($tahunAjaran)
            : $this->tahunKeduaAjaran($tahunAjaran);
    }

    public function simpanPdf(
        Gtk $gtk,
        string $jenis,
        int $periode,
        UploadedFile $file,
        ?int $tahunAnggaran = null,
        ?TahunAjaran $tahunAjaran = null,
        ?string $namaAsli = null,
        bool $enforcePeriodeLock = true,
    ): TunjanganDokumen {
        $this->assertJenisUpload($jenis);
        $this->assertValidPdf($file);

        if ($jenis === TunjanganDokumen::JENIS_SKAKPT) {
            if ($tahunAjaran === null || $periode < 1 || $periode > 12) {
                throw ValidationException::withMessages(['file' => 'Periode SKAKPT tidak valid.']);
            }
            if ($enforcePeriodeLock && ! $this->bolehUploadSkakpt($tahunAjaran, $periode)) {
                throw ValidationException::withMessages(['file' => 'Upload hanya untuk bulan yang sudah berlalu.']);
            }
            $slotKey = TunjanganDokumen::slotKeySkakpt((int) $tahunAjaran->id, $periode);
            $folder = "tunjangan/{$gtk->id}/skakpt/ta{$tahunAjaran->id}";
            $tahunAnggaran = null;
        } else {
            if ($tahunAjaran === null || ! in_array($periode, [1, 2], true)) {
                throw ValidationException::withMessages(['file' => 'Periode semester tidak valid.']);
            }
            if ($enforcePeriodeLock && ! $this->bolehUploadSemester($tahunAjaran, $periode)) {
                throw ValidationException::withMessages(['file' => 'Upload semester ini masih terkunci.']);
            }
            $slotKey = TunjanganDokumen::slotKeySemester($jenis, (int) $tahunAjaran->id, $periode);
            $folder = "tunjangan/{$gtk->id}/{$jenis}/ta{$tahunAjaran->id}";
            $tahunAnggaran = null;
        }

        $existing = TunjanganDokumen::query()
            ->where('gtk_id', $gtk->id)
            ->where('slot_key', $slotKey)
            ->first();

        $path = $file->storeAs($folder, $periode.'.pdf', 'r2');

        $dokumen = TunjanganDokumen::query()->updateOrCreate(
            [
                'gtk_id' => $gtk->id,
                'slot_key' => $slotKey,
            ],
            [
                'jenis' => $jenis,
                'tahun_anggaran' => $tahunAnggaran,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'periode' => $periode,
                'path' => $path,
                'nama_asli' => $namaAsli ?: $file->getClientOriginalName(),
            ]
        );

        if ($existing && $existing->path && $existing->path !== $path) {
            Storage::disk('r2')->delete($existing->path);
        }

        return $dokumen;
    }

    public function hapus(TunjanganDokumen $dokumen): void
    {
        if (filled($dokumen->path)) {
            Storage::disk('r2')->delete((string) $dokumen->path);
        }

        $dokumen->delete();
    }

    public function assertValidPdf(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages(['file' => 'File tidak valid.']);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        if ($ext !== 'pdf' && $file->getMimeType() !== 'application/pdf') {
            throw ValidationException::withMessages(['file' => 'File harus berformat PDF.']);
        }

        $kb = (int) ceil($file->getSize() / 1024);
        if ($kb > self::MAX_PDF_KB) {
            throw ValidationException::withMessages([
                'file' => 'Ukuran PDF maksimal '.self::MAX_PDF_KB.' KB.',
            ]);
        }
    }

    public function normalizeNamaKey(string $nama): string
    {
        $nama = str_replace('_', ' ', $nama);
        $nama = mb_strtoupper(trim(preg_replace('/\s+/', ' ', $nama) ?? ''));

        // Gelar Haji/Hajah sering ada di Madani (H. / Hj.) tapi tidak di nama file ZIP.
        // Hapus sebagai token depan saja agar "HASAN" tidak ikut terpotong.
        $nama = preg_replace('/^(HAJJAH|HAJI|HJ|H)\.+/', '', $nama) ?? $nama;
        $nama = preg_replace('/^(HAJJAH|HAJI|HJ|H)\s+/', '', $nama) ?? $nama;
        $nama = trim($nama);

        return preg_replace('/[^A-Z0-9]/', '', $nama) ?: '';
    }

    /**
     * @return Collection<int, Gtk>
     */
    public function gtkTersertifikasi()
    {
        return Gtk::query()
            ->where('status', 'aktif')
            ->whereNotNull('nrg')
            ->where('nrg', '!=', '')
            ->orderBy('nama')
            ->get();
    }

    /**
     * Bulan default admin SKAKPT: bulan sebelum bulan berjalan.
     */
    public function bulanSkakptDefault(?CarbonInterface $sekarang = null): int
    {
        $sekarang ??= now();

        return (int) $sekarang->copy()->subMonthNoOverflow()->month;
    }

    /**
     * Daftar admin SKAKPT: filter TA + bulan (default aktif & bulan sebelumnya),
     * hitungan sudah/belum upload, dan filter status upload. Urutan DUK.
     *
     * @return array{
     *     tahun_ajaran: TahunAjaran,
     *     bulan: int,
     *     status_upload: string|null,
     *     gtks: Collection<int, Gtk>,
     *     jumlah_sudah: int,
     *     jumlah_belum: int,
     *     jumlah_total: int
     * }
     */
    public function daftarSkakptAdmin(?int $tahunAjaranId, ?int $bulan, ?string $statusUpload): array
    {
        $tahunAjaran = $tahunAjaranId
            ? TahunAjaran::query()->find($tahunAjaranId)
            : TahunAjaran::aktif();

        abort_unless($tahunAjaran, 404, 'Tahun ajaran belum tersedia.');

        $bulan = $bulan && $bulan >= 1 && $bulan <= 12
            ? $bulan
            : $this->bulanSkakptDefault();

        $statusUpload = in_array($statusUpload, ['sudah', 'belum'], true)
            ? $statusUpload
            : null;

        $gtks = $this->gtkTersertifikasi();
        $gtkIds = $gtks->pluck('id');

        $sudahIds = TunjanganDokumen::query()
            ->where('jenis', TunjanganDokumen::JENIS_SKAKPT)
            ->where('tahun_ajaran_id', $tahunAjaran->id)
            ->where('periode', $bulan)
            ->whereNotNull('path')
            ->where('path', '!=', '')
            ->whereIn('gtk_id', $gtkIds)
            ->pluck('gtk_id')
            ->unique()
            ->flip();

        $gtks = $gtks->map(function (Gtk $gtk) use ($sudahIds) {
            $gtk->setAttribute('skakpt_sudah_upload', $sudahIds->has($gtk->id));

            return $gtk;
        });

        $jumlahSudah = $sudahIds->count();
        $jumlahTotal = $gtks->count();
        $jumlahBelum = $jumlahTotal - $jumlahSudah;

        if ($statusUpload === 'sudah') {
            $gtks = $gtks->filter(fn (Gtk $gtk) => (bool) $gtk->skakpt_sudah_upload)->values();
        } elseif ($statusUpload === 'belum') {
            $gtks = $gtks->filter(fn (Gtk $gtk) => ! $gtk->skakpt_sudah_upload)->values();
        }

        $gtks = $this->urutkanGtkByDuk($gtks);

        return [
            'tahun_ajaran' => $tahunAjaran,
            'bulan' => $bulan,
            'status_upload' => $statusUpload,
            'gtks' => $gtks,
            'jumlah_sudah' => $jumlahSudah,
            'jumlah_belum' => $jumlahBelum,
            'jumlah_total' => $jumlahTotal,
        ];
    }

    /**
     * @param  Collection<int, Gtk>  $gtks
     * @return Collection<int, Gtk>
     */
    public function urutkanGtkByDuk(Collection $gtks): Collection
    {
        return $gtks
            ->sortBy(function (Gtk $gtk) {
                $duk = $gtk->duk;

                return is_numeric($duk) ? (int) $duk : PHP_INT_MAX;
            })
            ->values();
    }

    /**
     * Siapkan entri unduhan massal SKAKPT (hanya yang sudah upload), urut DUK.
     *
     * @return list<array{path: string, gtk: Gtk}>
     */
    public function entriUnduhMassalSkakpt(?int $tahunAjaranId, ?int $bulan, ?string $statusUpload): array
    {
        $ringkasan = $this->daftarSkakptAdmin($tahunAjaranId, $bulan, $statusUpload);
        $tahunAjaran = $ringkasan['tahun_ajaran'];
        $bulanTerpilih = $ringkasan['bulan'];

        $gtks = $ringkasan['gtks']
            ->filter(fn (Gtk $gtk) => (bool) $gtk->skakpt_sudah_upload)
            ->values();

        if ($gtks->isEmpty()) {
            return [];
        }

        $dokumenByGtk = TunjanganDokumen::query()
            ->where('jenis', TunjanganDokumen::JENIS_SKAKPT)
            ->where('tahun_ajaran_id', $tahunAjaran->id)
            ->where('periode', $bulanTerpilih)
            ->whereIn('gtk_id', $gtks->pluck('id'))
            ->whereNotNull('path')
            ->where('path', '!=', '')
            ->get()
            ->keyBy('gtk_id');

        $entries = [];
        foreach ($gtks as $gtk) {
            $dokumen = $dokumenByGtk->get($gtk->id);
            if ($dokumen === null || ! filled($dokumen->path)) {
                continue;
            }

            $entries[] = [
                'path' => (string) $dokumen->path,
                'gtk' => $gtk,
            ];
        }

        return $entries;
    }

    /**
     * Gabungkan PDF SKAKPT menjadi satu berkas (urutan entri = urutan DUK).
     *
     * @param  list<array{path: string, gtk: Gtk}>  $entries
     */
    public function gabungPdfSkakpt(array $entries): string
    {
        $pdf = new Fpdi;
        $ditambah = 0;

        foreach ($entries as $entry) {
            $binary = Storage::disk('r2')->get($entry['path']);
            if ($binary === null || $binary === '') {
                continue;
            }

            $pageCount = $pdf->setSourceFile(StreamReader::createByString($binary));
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
                $ditambah++;
            }
        }

        if ($ditambah === 0) {
            throw ValidationException::withMessages([
                'unduh' => 'Tidak ada halaman PDF yang bisa digabung.',
            ]);
        }

        return $pdf->Output('S');
    }

    public function cariGtkByNamaKey(string $namaKey): array
    {
        if ($namaKey === '') {
            return [];
        }

        return $this->gtkTersertifikasi()
            ->filter(fn (Gtk $gtk) => $this->normalizeNamaKey((string) $gtk->nama) === $namaKey)
            ->values()
            ->all();
    }

    public function cariTahunAjaranByKodeTa(int $tahun): ?TahunAjaran
    {
        $matches = TahunAjaran::query()
            ->where('nama', 'like', '%'.$tahun.'%')
            ->get();

        if ($matches->count() !== 1) {
            return null;
        }

        return $matches->first();
    }
}
