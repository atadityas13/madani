<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Gtk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SimpatisansGtkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $expected = (string) config('services.madani_introspect.secret', '');
        $given = (string) $request->header('X-Madani-Introspect-Secret', '');

        if ($expected === '' || ! hash_equals($expected, $given)) {
            return response()->json([
                'message' => 'Tidak diotorisasi.',
            ], 401);
        }

        $gtks = Gtk::query()
            ->where('jenis', Gtk::JENIS_GURU)
            ->orderBy('nama')
            ->get();

        $data = $gtks->map(function (Gtk $gtk): array {
            return [
                'nip' => $gtk->nip,
                'nama' => $gtk->nama,
                'gelar_depan' => $gtk->gelar_depan,
                'gelar_belakang' => $gtk->gelar_belakang,
                'nuptk' => $gtk->nuptk,
                'jenis_kelamin' => $gtk->jenis_kelamin,
                'tempat_lahir' => $gtk->tempat_lahir,
                'tanggal_lahir' => $gtk->tanggal_lahir?->toDateString(),
                'agama' => $gtk->agama,
                'nomor_hp' => $gtk->nomor_hp,
                'email' => $gtk->email,
                'alamat' => $gtk->alamat,
                'jabatan' => $gtk->jabatan,
                'golongan' => $gtk->golongan,
                'status_pegawai' => $gtk->status_pegawai,
                'kode_internal' => $gtk->kode_internal,
                'duk' => $gtk->duk,
                'mapel_ijazah' => $gtk->mapel_ijazah,
                'rumpun_ijazah' => $gtk->rumpun_ijazah,
                'mapel_sertifikasi' => $gtk->mapel_sertifikasi,
                'status_sertifikasi' => (bool) $gtk->status_sertifikasi,
                'is_bk' => (bool) $gtk->is_bk,
                'status' => $gtk->status,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'meta' => [
                'total' => $data->count(),
            ],
            'data' => $data,
        ]);
    }
}
