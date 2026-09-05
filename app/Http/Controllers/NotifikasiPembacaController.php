<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use App\Models\NotifikasiRead;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotifikasiPembacaController extends Controller
{
    public function show(Request $request, Notifikasi $notifikasi): View
    {
        $data = $request->validate([
            'tipe' => ['nullable', 'in:semua,guru,siswa'],
            'rombel_id' => ['nullable', 'integer', 'exists:rombels,id'],
        ]);

        $tipe = $data['tipe'] ?? 'semua';
        $rombelId = $tipe === 'siswa' ? ($data['rombel_id'] ?? null) : null;

        $base = NotifikasiRead::query()
            ->where('notifikasi_id', $notifikasi->id)
            ->whereNotNull('read_at');

        $countSemua = (clone $base)->count();
        $countGuru = (clone $base)->where('reader_type', User::class)->count();
        $countSiswa = (clone $base)->where('reader_type', Siswa::class)->count();

        $reads = (clone $base)
            ->with(['reader' => function ($morphTo): void {
                $morphTo->morphWith([
                    User::class => ['gtk:id,nama,nip'],
                    Siswa::class => ['rombels' => fn ($q) => $q->wherePivot('status', 'aktif')],
                ]);
            }])
            ->when($tipe === 'guru', fn ($q) => $q->where('reader_type', User::class))
            ->when($tipe === 'siswa', function ($q) use ($rombelId): void {
                $q->where('reader_type', Siswa::class);

                if ($rombelId !== null) {
                    $siswaIds = Rombel::query()
                        ->whereKey($rombelId)
                        ->firstOrFail()
                        ->anggotaAktif()
                        ->pluck('siswas.id')
                        ->map(fn ($id) => (string) $id)
                        ->all();

                    $q->whereIn('reader_id', $siswaIds);
                }
            })
            ->orderByDesc('read_at')
            ->paginate(30)
            ->withQueryString();

        $tahunAktif = TahunAjaran::aktif();
        $rombels = Rombel::query()
            ->when($tahunAktif, fn ($q) => $q->where('tahun_ajaran_id', $tahunAktif->id))
            ->orderBy('tingkat')
            ->orderBy('nama')
            ->get(['id', 'nama', 'tingkat']);

        return view('notifikasi.pembaca', compact(
            'notifikasi',
            'reads',
            'tipe',
            'rombelId',
            'rombels',
            'countSemua',
            'countGuru',
            'countSiswa',
        ));
    }
}
