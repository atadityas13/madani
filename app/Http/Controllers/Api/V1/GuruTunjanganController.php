<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Tunjangan\TunjanganDokumenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuruTunjanganController extends Controller
{
    public function __construct(
        private TunjanganDokumenService $dokumen,
    ) {}

    public function pengingatSkakpt(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $gtk = $user->gtk;

        if ($gtk === null) {
            return response()->json([
                'success' => true,
                'data' => [
                    'tampil' => false,
                    'judul' => null,
                    'isi' => null,
                    'bulan' => null,
                    'nama_bulan' => null,
                    'tahun_ajaran_id' => null,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->dokumen->pengingatSkakptUntukGtk($gtk),
        ]);
    }
}
