<?php

namespace App\Services\Vendor;

use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\VendorJob;
use App\Support\Peran;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VendorJobService
{
    /**
     * @param  array{nama: string, user_id: int, status?: string, rombel_ids?: list<int>, siswa_ids?: list<string>}  $data
     */
    public function buat(User $pembuat, array $data): VendorJob
    {
        $vendor = User::query()->findOrFail($data['user_id']);
        if (! $vendor->hasRole(Peran::VENDOR)) {
            throw new InvalidArgumentException('Pengguna yang dipilih bukan vendor.');
        }

        return DB::transaction(function () use ($pembuat, $data, $vendor) {
            $job = VendorJob::query()->create([
                'nama' => $data['nama'],
                'user_id' => $vendor->id,
                'created_by' => $pembuat->id,
                'status' => $data['status'] ?? VendorJob::STATUS_AKTIF,
            ]);

            $this->sinkronAnggota($job, $data['rombel_ids'] ?? [], $data['siswa_ids'] ?? []);

            return $job->fresh(['vendor', 'siswas']);
        });
    }

    /**
     * @param  array{nama?: string, user_id?: int, status?: string, rombel_ids?: list<int>, siswa_ids?: list<string>}  $data
     */
    public function perbarui(VendorJob $job, array $data): VendorJob
    {
        return DB::transaction(function () use ($job, $data) {
            if (isset($data['user_id'])) {
                $vendor = User::query()->findOrFail($data['user_id']);
                if (! $vendor->hasRole(Peran::VENDOR)) {
                    throw new InvalidArgumentException('Pengguna yang dipilih bukan vendor.');
                }
            }

            $job->update(array_filter([
                'nama' => $data['nama'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'status' => $data['status'] ?? null,
            ], fn ($v) => $v !== null));

            if (array_key_exists('rombel_ids', $data) || array_key_exists('siswa_ids', $data)) {
                $this->sinkronAnggota($job, $data['rombel_ids'] ?? [], $data['siswa_ids'] ?? []);
            }

            return $job->fresh(['vendor', 'siswas']);
        });
    }

    /**
     * @param  list<int|string>  $rombelIds
     * @param  list<string>  $siswaIds
     */
    public function sinkronAnggota(VendorJob $job, array $rombelIds, array $siswaIds): void
    {
        $tahun = TahunAjaran::aktif();
        $ids = collect($siswaIds)->filter()->map(fn ($id) => (string) $id);

        if ($rombelIds !== []) {
            $dariRombel = Siswa::query()
                ->whereHas('rombels', function ($query) use ($rombelIds, $tahun) {
                    $query->whereIn('rombels.id', $rombelIds)
                        ->where('rombel_siswas.status', 'aktif')
                        ->when($tahun, fn ($q) => $q->where('tahun_ajaran_id', $tahun->id));
                })
                ->pluck('id');
            $ids = $ids->merge($dariRombel);
        }

        $job->siswas()->sync($ids->unique()->values()->all());
    }

    /**
     * @return array{total: int, sudah_foto: int, belum_foto: int}
     */
    public function statistikUntukVendor(User $vendor): array
    {
        $siswaIds = VendorJob::query()
            ->where('user_id', $vendor->id)
            ->where('status', VendorJob::STATUS_AKTIF)
            ->with('siswas:id,foto')
            ->get()
            ->flatMap(fn (VendorJob $job) => $job->siswas)
            ->unique('id');

        $sudah = $siswaIds->filter(fn (Siswa $s) => filled($s->foto))->count();

        return [
            'total' => $siswaIds->count(),
            'sudah_foto' => $sudah,
            'belum_foto' => $siswaIds->count() - $sudah,
        ];
    }

    /**
     * @return Collection<int, User>
     */
    public function daftarVendor(): Collection
    {
        return User::query()
            ->role(Peran::VENDOR)
            ->where('is_aktif', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Rombel>
     */
    public function rombelAktif(): Collection
    {
        $tahun = TahunAjaran::aktif();

        return Rombel::query()
            ->when($tahun, fn ($q) => $q->where('tahun_ajaran_id', $tahun->id))
            ->orderBy('tingkat')
            ->orderBy('nama')
            ->get();
    }
}
