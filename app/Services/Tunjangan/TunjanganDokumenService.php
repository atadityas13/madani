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

class TunjanganDokumenService
{
    public const MAX_PDF_KB = 2048;

    /**
     * @return list<string>
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

    public function bolehUploadSkakpt(int $tahunAnggaran, int $bulan, ?CarbonInterface $sekarang = null): bool
    {
        $sekarang ??= now();
        $tahunSekarang = (int) $sekarang->year;
        $bulanSekarang = (int) $sekarang->month;

        if ($tahunAnggaran > $tahunSekarang) {
            return false;
        }

        if ($tahunAnggaran < $tahunSekarang) {
            return $bulan >= 1 && $bulan <= 12;
        }

        return $bulan >= 1 && $bulan < $bulanSekarang;
    }

    public function bolehUploadSemester(TahunAjaran $tahunAjaran, int $semester, ?CarbonInterface $sekarang = null): bool
    {
        $sekarang ??= now();

        if ($tahunAjaran->adalahAktif()) {
            if ($semester === 1) {
                return true;
            }

            // Semester II: buka sejak Januari tahun kedua TA (atau jika tanggal_selesai sudah lewat sebagian).
            $tahunKedua = $this->tahunKeduaAjaran($tahunAjaran);

            return $sekarang->year > $tahunKedua
                || ($sekarang->year === $tahunKedua && $sekarang->month >= 1);
        }

        // TA lampau: keduanya boleh; TA belum aktif: terkunci.
        return $tahunAjaran->status === TahunAjaran::STATUS_ARSIP
            || ($tahunAjaran->tanggal_selesai && $tahunAjaran->tanggal_selesai->lt($sekarang));
    }

    public function tahunKeduaAjaran(TahunAjaran $tahunAjaran): int
    {
        if (preg_match('/(\d{4})\s*\/\s*(\d{4})/', (string) $tahunAjaran->nama, $m)) {
            return (int) $m[2];
        }

        if ($tahunAjaran->tanggal_selesai) {
            return (int) $tahunAjaran->tanggal_selesai->year;
        }

        return (int) now()->year;
    }

    /**
     * @return list<int>
     */
    public function tahunAnggaranOptions(): array
    {
        $current = (int) now()->year;
        $fromDb = TunjanganDokumen::query()
            ->where('jenis', TunjanganDokumen::JENIS_SKAKPT)
            ->whereNotNull('tahun_anggaran')
            ->distinct()
            ->orderByDesc('tahun_anggaran')
            ->pluck('tahun_anggaran')
            ->map(fn ($y) => (int) $y)
            ->all();

        $years = array_values(array_unique(array_merge([$current], $fromDb)));
        rsort($years);

        return $years;
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
            if ($tahunAnggaran === null || $periode < 1 || $periode > 12) {
                throw ValidationException::withMessages(['file' => 'Periode SKAKPT tidak valid.']);
            }
            if ($enforcePeriodeLock && ! $this->bolehUploadSkakpt($tahunAnggaran, $periode)) {
                throw ValidationException::withMessages(['file' => 'Upload hanya untuk bulan yang sudah berlalu.']);
            }
            $slotKey = TunjanganDokumen::slotKeySkakpt($tahunAnggaran, $periode);
            $folder = "tunjangan/{$gtk->id}/skakpt/{$tahunAnggaran}";
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
                'tahun_ajaran_id' => $tahunAjaran?->id,
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
