<?php

namespace App\Services;

use App\Jobs\SendNotifikasiFcmJob;
use App\Models\IzinSiswa;
use App\Models\JurnalPembelajaran;
use App\Models\Notifikasi;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\PernyataanSiswa;
use App\Support\R2Url;
use App\Support\SuratIzinSiswa;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class IzinSiswaService
{
    public function simpan(Siswa $siswa, array $data, string $ttdWaliBase64, ?string $lampiranBase64 = null): IzinSiswa
    {
        if (! ($data['pernyataan_disetujui'] ?? false)) {
            throw ValidationException::withMessages([
                'pernyataan_disetujui' => 'Pernyataan orang tua/wali wajib disetujui.',
            ]);
        }

        $tanggal = Carbon::parse($data['tanggal'])->startOfDay();
        $this->pastikanTanggalValid($tanggal);

        $jenisBukti = filled($data['jenis_bukti'] ?? null) ? trim((string) $data['jenis_bukti']) : null;
        if (filled($lampiranBase64) && ! filled($jenisBukti)) {
            throw ValidationException::withMessages([
                'jenis_bukti' => 'Jenis bukti wajib diisi jika lampiran diunggah.',
            ]);
        }
        if (! filled($lampiranBase64)) {
            $jenisBukti = null;
        }

        $rombel = $siswa->rombelAktif();
        $bytes = $this->decodePng($ttdWaliBase64);
        $path = $this->simpanPng($siswa->id, $bytes);
        $lampiran = filled($lampiranBase64) ? $this->simpanLampiran($siswa->id, $lampiranBase64) : null;
        $namaWali = PernyataanSiswa::namaWaliEfektif($siswa);
        if ($namaWali === '') {
            $namaWali = 'Orang tua/wali';
        }

        $izin = DB::transaction(function () use ($siswa, $data, $tanggal, $rombel, $path, $lampiran, $jenisBukti, $namaWali) {
            $existing = IzinSiswa::query()
                ->where('siswa_id', $siswa->id)
                ->whereDate('tanggal', $tanggal)
                ->where('status', IzinSiswa::STATUS_AKTIF)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                $this->hapusFileLama($existing->ttd_wali_path, $path);
                if ($lampiran !== null) {
                    $this->hapusFileLama($existing->lampiran_path, $lampiran['path']);
                } else {
                    $this->hapusFileLama($existing->lampiran_path, null);
                }

                $existing->update([
                    'rombel_id' => $rombel?->id,
                    'jenis' => $data['jenis'],
                    'alasan' => $data['alasan'],
                    'pernyataan_disetujui' => true,
                    'ttd_wali_path' => $path,
                    'lampiran_path' => $lampiran['path'] ?? null,
                    'jenis_bukti' => $jenisBukti,
                    'nama_wali' => $namaWali,
                ]);

                return $existing->fresh(['siswa', 'rombel']);
            }

            return IzinSiswa::query()->create([
                'siswa_id' => $siswa->id,
                'rombel_id' => $rombel?->id,
                'jenis' => $data['jenis'],
                'tanggal' => $tanggal,
                'alasan' => $data['alasan'],
                'pernyataan_disetujui' => true,
                'ttd_wali_path' => $path,
                'lampiran_path' => $lampiran['path'] ?? null,
                'jenis_bukti' => $jenisBukti,
                'nama_wali' => $namaWali,
                'status' => IzinSiswa::STATUS_AKTIF,
            ])->load(['siswa', 'rombel']);
        });

        $this->kirimNotifikasiGuru($izin);

        return $izin;
    }

    /**
     * @param  list<string>  $siswaIds
     * @return array{created: int, updated: int, items: list<array<string, mixed>>}
     */
    public function laporkanAlpa(User $user, int $rombelId, array $siswaIds, ?CarbonInterface $tanggal = null): array
    {
        $siswaIds = array_values(array_unique(array_filter(array_map('strval', $siswaIds))));
        if ($siswaIds === []) {
            throw ValidationException::withMessages([
                'siswa_ids' => 'Pilih minimal satu siswa.',
            ]);
        }

        $hari = ($tanggal ?? now())->copy()->startOfDay();
        $this->pastikanTanggalValid($hari);

        $rombel = Rombel::query()->find($rombelId);
        if ($rombel === null) {
            throw ValidationException::withMessages([
                'rombel_id' => 'Kelas tidak ditemukan.',
            ]);
        }

        $anggotaIds = $rombel->anggotaAktif()->pluck('siswas.id')->map(fn ($id) => (string) $id)->all();
        $invalid = array_values(array_diff($siswaIds, $anggotaIds));
        if ($invalid !== []) {
            throw ValidationException::withMessages([
                'siswa_ids' => 'Ada siswa yang bukan anggota aktif kelas ini.',
            ]);
        }

        $created = 0;
        $updated = 0;
        $items = [];

        DB::transaction(function () use ($user, $rombel, $siswaIds, $hari, &$created, &$updated, &$items): void {
            foreach ($siswaIds as $siswaId) {
                $existing = IzinSiswa::query()
                    ->where('siswa_id', $siswaId)
                    ->whereDate('tanggal', $hari)
                    ->where('status', IzinSiswa::STATUS_AKTIF)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    $this->hapusFileLama($existing->ttd_wali_path, null);
                    $this->hapusFileLama($existing->lampiran_path, null);
                    $existing->update([
                        'rombel_id' => $rombel->id,
                        'jenis' => IzinSiswa::JENIS_ALPA,
                        'alasan' => 'Alpa',
                        'pernyataan_disetujui' => false,
                        'ttd_wali_path' => null,
                        'lampiran_path' => null,
                        'jenis_bukti' => null,
                        'nama_wali' => null,
                        'dilaporkan_oleh' => $user->id,
                    ]);
                    $updated++;
                    $items[] = $existing->fresh(['siswa', 'rombel']);
                } else {
                    $items[] = IzinSiswa::query()->create([
                        'siswa_id' => $siswaId,
                        'rombel_id' => $rombel->id,
                        'jenis' => IzinSiswa::JENIS_ALPA,
                        'tanggal' => $hari,
                        'alasan' => 'Alpa',
                        'pernyataan_disetujui' => false,
                        'dilaporkan_oleh' => $user->id,
                        'status' => IzinSiswa::STATUS_AKTIF,
                    ])->load(['siswa', 'rombel']);
                    $created++;
                }
            }
        });

        foreach ($items as $izin) {
            $this->kirimNotifikasiGuru($izin);
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'items' => collect($items)
                ->map(fn (IzinSiswa $izin) => $this->toGuruItem($izin, $user->gtk_id))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return list<array{id: int, label: string, tingkat: string, nama: string, jumlah_siswa: int}>
     */
    public function daftarRombelAktif(): array
    {
        $tahun = TahunAjaran::aktif();
        if ($tahun === null) {
            return [];
        }

        return Rombel::query()
            ->where('tahun_ajaran_id', $tahun->id)
            ->withCount(['siswas as jumlah_siswa' => fn ($q) => $q->wherePivot('status', 'aktif')])
            ->get()
            ->sortBy([
                fn (Rombel $r) => Rombel::tingkatOrder($r->tingkat),
                ['nama', 'asc'],
            ])
            ->values()
            ->map(fn (Rombel $rombel) => [
                'id' => $rombel->id,
                'label' => $rombel->label(),
                'tingkat' => (string) $rombel->tingkat,
                'nama' => (string) $rombel->nama,
                'jumlah_siswa' => (int) $rombel->jumlah_siswa,
            ])
            ->all();
    }

    /**
     * @return list<array{id: string, nama: string, nisn: ?string, sudah_lapor: bool, jenis_lapor: ?string}>
     */
    public function daftarSiswaRombel(int $rombelId, ?CarbonInterface $tanggal = null): array
    {
        $rombel = Rombel::query()->find($rombelId);
        if ($rombel === null) {
            throw ValidationException::withMessages([
                'rombel_id' => 'Kelas tidak ditemukan.',
            ]);
        }

        $hari = ($tanggal ?? now())->toDateString();
        $laporan = IzinSiswa::query()
            ->where('rombel_id', $rombelId)
            ->whereDate('tanggal', $hari)
            ->where('status', IzinSiswa::STATUS_AKTIF)
            ->get()
            ->keyBy(fn (IzinSiswa $izin) => (string) $izin->siswa_id);

        return $rombel->anggotaAktif()
            ->get(['siswas.id', 'siswas.nama', 'siswas.nisn'])
            ->map(function (Siswa $siswa) use ($laporan) {
                $izin = $laporan->get((string) $siswa->id);

                return [
                    'id' => (string) $siswa->id,
                    'nama' => (string) $siswa->nama,
                    'nisn' => $siswa->nisn,
                    'sudah_lapor' => $izin !== null,
                    'jenis_lapor' => $izin?->jenis,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     tanggal: string,
     *     rows: list<array{rombel_id: int, rombel: string, sakit: int, izin: int, alpa: int, total: int}>,
     *     totals: array{sakit: int, izin: int, alpa: int, total: int}
     * }
     */
    public function rekapSiaPerKelas(?CarbonInterface $tanggal = null): array
    {
        $hari = ($tanggal ?? now())->toDateString();
        $tahun = TahunAjaran::aktif();
        $rombels = $tahun === null
            ? collect()
            : Rombel::query()
                ->where('tahun_ajaran_id', $tahun->id)
                ->get()
                ->sortBy([
                    fn (Rombel $r) => Rombel::tingkatOrder($r->tingkat),
                    ['nama', 'asc'],
                ])
                ->values();

        $counts = IzinSiswa::query()
            ->selectRaw('rombel_id, jenis, COUNT(*) as jumlah')
            ->whereDate('tanggal', $hari)
            ->where('status', IzinSiswa::STATUS_AKTIF)
            ->whereNotNull('rombel_id')
            ->groupBy('rombel_id', 'jenis')
            ->get()
            ->groupBy('rombel_id');

        $rows = [];
        $totalSakit = 0;
        $totalIzin = 0;
        $totalAlpa = 0;

        foreach ($rombels as $rombel) {
            $byJenis = $counts->get($rombel->id, collect());
            $sakit = (int) ($byJenis->firstWhere('jenis', IzinSiswa::JENIS_SAKIT)?->jumlah ?? 0);
            $izin = (int) ($byJenis->firstWhere('jenis', IzinSiswa::JENIS_IZIN)?->jumlah ?? 0);
            $alpa = (int) ($byJenis->firstWhere('jenis', IzinSiswa::JENIS_ALPA)?->jumlah ?? 0);
            $total = $sakit + $izin + $alpa;
            $totalSakit += $sakit;
            $totalIzin += $izin;
            $totalAlpa += $alpa;

            $rows[] = [
                'rombel_id' => $rombel->id,
                'rombel' => $rombel->label(),
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
                'total' => $total,
            ];
        }

        return [
            'tanggal' => $hari,
            'rows' => $rows,
            'totals' => [
                'sakit' => $totalSakit,
                'izin' => $totalIzin,
                'alpa' => $totalAlpa,
                'total' => $totalSakit + $totalIzin + $totalAlpa,
            ],
        ];
    }

    public function batalkanOlehWali(IzinSiswa $izin, User $user, ?string $alasanBatal = null): IzinSiswa
    {
        if (! $izin->isAktif()) {
            throw ValidationException::withMessages([
                'status' => 'Laporan sudah dibatalkan.',
            ]);
        }

        $izin->loadMissing('rombel');
        $gtkId = $user->gtk_id;
        if ($gtkId === null || $izin->rombel === null || (int) $izin->rombel->gtk_id !== (int) $gtkId) {
            throw ValidationException::withMessages([
                'izin' => 'Hanya wali kelas yang dapat membatalkan laporan ini.',
            ]);
        }

        $izin->update([
            'status' => IzinSiswa::STATUS_DIBATALKAN,
            'dibatalkan_oleh' => $user->id,
            'dibatalkan_at' => now(),
            'alasan_batal' => filled($alasanBatal) ? trim($alasanBatal) : null,
        ]);

        $izin = $izin->fresh(['siswa', 'rombel']);
        $this->kirimNotifikasiSiswaDibatalkan($izin);

        return $izin;
    }

    /**
     * @return Collection<int, IzinSiswa>
     */
    public function daftarHariIni(?CarbonInterface $tanggal = null): Collection
    {
        $hari = ($tanggal ?? now())->toDateString();

        return IzinSiswa::query()
            ->with(['siswa:id,nama,nisn', 'rombel:id,tingkat,nama,gtk_id'])
            ->whereDate('tanggal', $hari)
            ->where('status', IzinSiswa::STATUS_AKTIF)
            ->get()
            ->sortBy([
                ['jenis', 'asc'],
                fn (IzinSiswa $izin) => mb_strtolower((string) $izin->siswa?->nama),
            ])
            ->values();
    }

    /**
     * @return array{total: int, izin: int, sakit: int, alpa: int, tanggal: string, items: list<array<string, mixed>>}
     */
    public function rekapHariIni(User $user, ?CarbonInterface $tanggal = null): array
    {
        $hari = ($tanggal ?? now())->toDateString();
        $items = $this->daftarHariIni(Carbon::parse($hari));
        $gtkId = $user->gtk_id;

        $mapped = $items->map(fn (IzinSiswa $izin) => $this->toGuruItem($izin, $gtkId))->values()->all();

        return [
            'tanggal' => $hari,
            'total' => count($mapped),
            'izin' => $items->where('jenis', IzinSiswa::JENIS_IZIN)->count(),
            'sakit' => $items->where('jenis', IzinSiswa::JENIS_SAKIT)->count(),
            'alpa' => $items->where('jenis', IzinSiswa::JENIS_ALPA)->count(),
            'items' => $mapped,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toSiswaItem(IzinSiswa $izin): array
    {
        $izin->loadMissing('rombel');

        return [
            'id' => $izin->id,
            'jenis' => $izin->jenis,
            'jenis_label' => $izin->labelJenis(),
            'tanggal' => $izin->tanggal?->toDateString(),
            'alasan' => $izin->alasan,
            'status' => $izin->status,
            'rombel' => $izin->rombel?->label(),
            'ttd_wali_url' => filled($izin->ttd_wali_path) ? R2Url::temporary($izin->ttd_wali_path) : null,
            'lampiran_url' => filled($izin->lampiran_path) ? R2Url::temporary($izin->lampiran_path) : null,
            'jenis_bukti' => $izin->jenis_bukti,
            'nama_wali' => $izin->nama_wali,
            'punya_lampiran' => filled($izin->lampiran_path),
            'dibatalkan_at' => $izin->dibatalkan_at?->toIso8601String(),
            'alasan_batal' => $izin->alasan_batal,
            'created_at' => $izin->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toGuruItem(IzinSiswa $izin, mixed $gtkId): array
    {
        $izin->loadMissing(['siswa', 'rombel']);
        $bisaBatalkan = $gtkId !== null
            && $izin->rombel !== null
            && (int) $izin->rombel->gtk_id === (int) $gtkId;

        return [
            'id' => $izin->id,
            'siswa_id' => $izin->siswa_id,
            'nama' => $izin->siswa?->nama,
            'nisn' => $izin->siswa?->nisn,
            'rombel' => $izin->rombel?->label(),
            'jenis' => $izin->jenis,
            'jenis_label' => $izin->labelJenis(),
            'tanggal' => $izin->tanggal?->toDateString(),
            'alasan' => $izin->alasan,
            'status' => $izin->status,
            'bisa_batalkan' => $bisaBatalkan,
            'punya_lampiran' => filled($izin->lampiran_path),
            'punya_surat' => $izin->punyaSuratOrtu(),
            'jenis_bukti' => $izin->jenis_bukti,
            'created_at' => $izin->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toSurat(IzinSiswa $izin): array
    {
        return SuratIzinSiswa::payload($izin);
    }

    private function pastikanTanggalValid(CarbonInterface $tanggal): void
    {
        $min = now()->startOfDay()->subDay();
        $max = now()->startOfDay()->addDays(7);

        if ($tanggal->lt($min) || $tanggal->gt($max)) {
            throw ValidationException::withMessages([
                'tanggal' => 'Tanggal ketidakhadiran harus antara kemarin dan 7 hari ke depan.',
            ]);
        }
    }

    private function decodePng(string $raw): string
    {
        $raw = trim($raw);
        if (str_starts_with($raw, 'data:image')) {
            $parts = explode(',', $raw, 2);
            $raw = $parts[1] ?? '';
        }

        $bytes = base64_decode($raw, true);
        if ($bytes === false || $bytes === '') {
            throw ValidationException::withMessages([
                'ttd_wali' => 'Tanda tangan orang tua/wali tidak valid.',
            ]);
        }

        if (@imagecreatefromstring($bytes) === false) {
            throw ValidationException::withMessages([
                'ttd_wali' => 'Tanda tangan harus berupa gambar PNG/JPEG.',
            ]);
        }

        if (strlen($bytes) > 1024 * 1024) {
            throw ValidationException::withMessages([
                'ttd_wali' => 'Ukuran tanda tangan maksimal 1 MB.',
            ]);
        }

        return $bytes;
    }

    private function simpanPng(string $siswaId, string $bytes): string
    {
        $path = 'siswa/'.$siswaId.'/izin/'.now()->format('YmdHis').'-ttd-wali.png';
        Storage::disk('r2')->put($path, $bytes, 'private');

        return $path;
    }

    /**
     * @return array{path: string, mime: string}
     */
    private function simpanLampiran(string $siswaId, string $raw): array
    {
        $decoded = $this->decodeLampiran($raw);
        $ext = $decoded['ext'];
        $path = 'siswa/'.$siswaId.'/izin/'.now()->format('YmdHis').'-lampiran.'.$ext;
        Storage::disk('r2')->put($path, $decoded['bytes'], 'private');

        return [
            'path' => $path,
            'mime' => $decoded['mime'],
        ];
    }

    /**
     * @return array{bytes: string, mime: string, ext: string}
     */
    private function decodeLampiran(string $raw): array
    {
        $raw = trim($raw);
        $mimeHint = null;
        if (str_starts_with($raw, 'data:')) {
            if (preg_match('#^data:([^;]+);base64,#i', $raw, $matches) === 1) {
                $mimeHint = strtolower(trim($matches[1]));
            }
            $parts = explode(',', $raw, 2);
            $raw = $parts[1] ?? '';
        }

        $bytes = base64_decode($raw, true);
        if ($bytes === false || $bytes === '') {
            throw ValidationException::withMessages([
                'lampiran' => 'Lampiran bukti tidak valid.',
            ]);
        }

        if (strlen($bytes) > 2 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'lampiran' => 'Ukuran lampiran maksimal 2 MB.',
            ]);
        }

        $mime = $mimeHint;
        if ($mime === null || ! in_array($mime, ['image/png', 'image/jpeg', 'image/jpg', 'application/pdf'], true)) {
            if (str_starts_with($bytes, '%PDF')) {
                $mime = 'application/pdf';
            } elseif (@imagecreatefromstring($bytes) !== false) {
                $mime = 'image/jpeg';
            } else {
                throw ValidationException::withMessages([
                    'lampiran' => 'Lampiran harus berupa PDF, PNG, atau JPEG.',
                ]);
            }
        }

        if (in_array($mime, ['image/png', 'image/jpeg', 'image/jpg'], true) && @imagecreatefromstring($bytes) === false) {
            throw ValidationException::withMessages([
                'lampiran' => 'Lampiran gambar tidak valid.',
            ]);
        }

        $ext = match ($mime) {
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            default => 'jpg',
        };

        return [
            'bytes' => $bytes,
            'mime' => $mime === 'image/jpg' ? 'image/jpeg' : $mime,
            'ext' => $ext,
        ];
    }

    private function hapusFileLama(?string $lama, ?string $baru): void
    {
        if (! $lama || $lama === $baru) {
            return;
        }

        Storage::disk('r2')->delete($lama);
    }

    private function kirimNotifikasiGuru(IzinSiswa $izin): void
    {
        $izin->loadMissing(['siswa', 'rombel']);
        $gtkIds = $this->gtkIdsPenerimaNotifikasi($izin);
        if ($gtkIds === []) {
            return;
        }

        $nama = $izin->siswa?->nama ?? 'Siswa';
        $kelas = $izin->rombel?->label() ?? '—';
        $jenis = $izin->labelJenis();
        $tanggal = $izin->tanggal?->translatedFormat('d M Y') ?? $izin->tanggal?->toDateString();

        $notifikasi = Notifikasi::query()->create([
            'judul' => "Siswa {$jenis} hari ini",
            'isi' => "{$nama} ({$kelas}) melapor {$jenis} pada {$tanggal}. Alasan: {$izin->alasan}",
            'jenis' => Notifikasi::JENIS_NOTIFIKASI,
            'audience' => Notifikasi::AUDIENCE_GTK,
            'audience_ids' => $gtkIds,
            'is_active' => true,
            'published_at' => now(),
            'priority' => Notifikasi::PRIORITY_NORMAL,
            'sound_key' => Notifikasi::SOUND_DEFAULT,
        ]);

        SendNotifikasiFcmJob::dispatch($notifikasi->id);
    }

    /**
     * Wali kelas + guru mapel yang mengajar rombel itu pada hari tanggal izin
     * (diambil dari jurnal pembelajaran historis untuk hari yang sama).
     *
     * @return list<int>
     */
    private function gtkIdsPenerimaNotifikasi(IzinSiswa $izin): array
    {
        $izin->loadMissing('rombel');
        $ids = [];

        if ($izin->rombel?->gtk_id !== null) {
            $ids[] = (int) $izin->rombel->gtk_id;
        }

        $kelasId = $izin->rombel?->source_simpatisans_kelas_id;
        $tanggal = $izin->tanggal;
        if ($kelasId !== null && (int) $kelasId > 0 && $tanggal !== null) {
            $hari = $this->hariIndonesia($tanggal);
            $userIds = JurnalPembelajaran::query()
                ->where('kelas_id', (int) $kelasId)
                ->where(function ($q) use ($tanggal, $hari): void {
                    $q->whereDate('tanggal', $tanggal)
                        ->orWhere('hari', $hari);
                })
                ->where('tanggal', '>=', $tanggal->copy()->subDays(90)->toDateString())
                ->distinct()
                ->pluck('user_id')
                ->filter()
                ->all();

            if ($userIds !== []) {
                $mapelGtkIds = User::query()
                    ->whereIn('id', $userIds)
                    ->whereNotNull('gtk_id')
                    ->pluck('gtk_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
                $ids = array_merge($ids, $mapelGtkIds);
            }
        }

        $ids = array_values(array_unique(array_filter($ids, fn (int $id) => $id > 0)));

        return $ids;
    }

    private function hariIndonesia(CarbonInterface $date): string
    {
        return match ($date->dayOfWeek) {
            Carbon::MONDAY => 'Senin',
            Carbon::TUESDAY => 'Selasa',
            Carbon::WEDNESDAY => 'Rabu',
            Carbon::THURSDAY => 'Kamis',
            Carbon::FRIDAY => 'Jumat',
            Carbon::SATURDAY => 'Sabtu',
            default => 'Minggu',
        };
    }

    private function kirimNotifikasiSiswaDibatalkan(IzinSiswa $izin): void
    {
        $izin->loadMissing('siswa');
        if ($izin->siswa === null) {
            return;
        }

        $jenis = $izin->labelJenis();
        $tanggal = $izin->tanggal?->translatedFormat('d M Y') ?? $izin->tanggal?->toDateString();

        $notifikasi = Notifikasi::query()->create([
            'judul' => "Laporan {$jenis} dibatalkan",
            'isi' => "Laporan {$jenis} Anda pada {$tanggal} dibatalkan oleh wali kelas."
                .(filled($izin->alasan_batal) ? ' Alasan: '.$izin->alasan_batal : ''),
            'jenis' => Notifikasi::JENIS_NOTIFIKASI,
            'audience' => Notifikasi::AUDIENCE_SISWA,
            'audience_ids' => [(string) $izin->siswa_id],
            'is_active' => true,
            'published_at' => now(),
            'priority' => Notifikasi::PRIORITY_NORMAL,
            'sound_key' => Notifikasi::SOUND_DEFAULT,
        ]);

        SendNotifikasiFcmJob::dispatch($notifikasi->id);
    }
}
