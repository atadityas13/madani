<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Wilayah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferensiController extends Controller
{
    public function emis(): JsonResponse
    {
        $emis = config('emis');
        unset($emis['asrama_madrasah']);

        return response()->json([
            'success' => true,
            'data' => [
                'emis' => $emis,
                'asrama_madrasah' => config('emis.asrama_madrasah'),
                'wilayah' => Wilayah::tree(),
            ],
        ]);
    }

    public function wilayah(Request $request): JsonResponse
    {
        $provinsi = (string) $request->query('provinsi', '');
        $kota = (string) $request->query('kota', '');
        $kecamatan = (string) $request->query('kecamatan', '');
        $desa = (string) $request->query('desa', '');

        if ($provinsi === '') {
            return response()->json([
                'success' => true,
                'data' => array_keys(Wilayah::tree()),
            ]);
        }

        if ($kota === '') {
            return response()->json([
                'success' => true,
                'data' => Wilayah::kabupaten($provinsi),
            ]);
        }

        if ($kecamatan === '') {
            return response()->json([
                'success' => true,
                'data' => Wilayah::kecamatan($provinsi, $kota),
            ]);
        }

        if ($desa !== '') {
            return response()->json([
                'success' => true,
                'data' => [],
                'kode_pos' => Wilayah::kodePos($provinsi, $kota, $kecamatan, $desa),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => Wilayah::desa($provinsi, $kota, $kecamatan),
        ]);
    }
}
