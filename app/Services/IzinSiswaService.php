<?php

namespace App\Services;

use App\Jobs\SendNotifikasiFcmJob;
use App\Models\IzinSiswa;
use App\Models\Notifikasi;
use App\Models\Siswa;
use App\Models\User;
use App\Support\R2Url;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class IzinSiswaService
{
    public function simpan(Siswa $siswa, array $data, string $ttdWaliBase64): IzinSiswa
    {
        if (! ($data['pernyataan_disetujui'] ?? false)) {
            throw ValidationException::withMessages([
                'pernyataan_disetujui' => 'Pernyataan orang tua/wali wajib disetujui.',
            ]);
        }

        $tanggal = Carbon::parse($data['tanggal'])->startOfDay();
        $this->pastikanTanggalValid($tanggal);

        $rombel = $siswa->rombelAktif();
        $bytes = $this->decodePng($ttdWaliBase64);
        $path = $this->simpanPng($siswa->id, $bytes);

        $izin = DB::transaction(function () use ($siswa, $data, $tanggal, $rombel, $path) {
            $existing = IzinSiswa::query()
                ->where('siswa_id', $siswa->id)
                ->whereDate('tanggal', $tanggal)
                ->where('status', IzinSiswa::STATUS_AKTIF)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                $this->hapusTtdLama($existing->ttd_wali_path, $path);
                $existing->update([
                    'rombel_id' => $rombel?->id,
                    'jenis' => $data['jenis'],
                    'alasan' => $data['alasan'],
                    'pernyataan_disetujui' => true,
                    'ttd_wali_path' => $path,
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
                'status' => IzinSiswa::STATUS_AKTIF,
            ])->load(['siswa', 'rombel']);
        });

        $this->kirimNotifikasiGuru($izin);

        return $izin;
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
     * @return array{total: int, izin: int, sakit: int, tanggal: string, items: list<array<string, mixed>>}
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
            'created_at' => $izin->created_at?->toIso8601String(),
        ];
    }

    private function pastikanTanggalValid(CarbonInterface $tanggal): void
    {
        $min = now()->startOfDay()->subDay();
        $max = now()->startOfDay()->addDays(7);

        if ($tanggal->lt($min) || $tanggal->gt($max)) {
            throw ValidationException::withMessages([
                'tanggal' => 'Tanggal izin/sakit harus antara kemarin dan 7 hari ke depan.',
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

    private function hapusTtdLama(?string $lama, string $baru): void
    {
        if (! $lama || $lama === $baru) {
            return;
        }

        Storage::disk('r2')->delete($lama);
    }

    private function kirimNotifikasiGuru(IzinSiswa $izin): void
    {
        $izin->loadMissing(['siswa', 'rombel']);
        $nama = $izin->siswa?->nama ?? 'Siswa';
        $kelas = $izin->rombel?->label() ?? '—';
        $jenis = $izin->labelJenis();
        $tanggal = $izin->tanggal?->translatedFormat('d M Y') ?? $izin->tanggal?->toDateString();

        $notifikasi = Notifikasi::query()->create([
            'judul' => "Siswa {$jenis} hari ini",
            'isi' => "{$nama} ({$kelas}) melapor {$jenis} pada {$tanggal}. Alasan: {$izin->alasan}",
            'jenis' => Notifikasi::JENIS_NOTIFIKASI,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
            'audience_ids' => null,
            'is_active' => true,
            'published_at' => now(),
            'priority' => Notifikasi::PRIORITY_NORMAL,
            'sound_key' => Notifikasi::SOUND_DEFAULT,
        ]);

        SendNotifikasiFcmJob::dispatch($notifikasi->id);
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
