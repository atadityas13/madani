<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiswaController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $nisn = $request->query('nisn');

        if (! $nisn) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter nisn wajib diisi.',
            ], 422);
        }

        $siswa = Siswa::query()
            ->with(['rombels' => fn ($q) => $q->wherePivot('status', 'aktif')])
            ->where('nisn', $nisn)
            ->first();

        if (! $siswa) {
            return response()->json([
                'success' => false,
                'message' => 'Siswa tidak ditemukan.',
            ], 404);
        }

        $rombel = $siswa->rombels->first();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $siswa->id,
                'nisn' => $siswa->nisn,
                'nik' => $siswa->nik,
                'nis' => $siswa->nis,
                'nama' => $siswa->nama,
                'jenis_kelamin' => $siswa->jenis_kelamin,
                'status_keaktifan' => $siswa->status_keaktifan,
                'rombel' => $rombel ? [
                    'id' => $rombel->id,
                    'nama' => $rombel->nama,
                    'tingkat' => $rombel->tingkat,
                ] : null,
            ],
        ]);
    }
}
