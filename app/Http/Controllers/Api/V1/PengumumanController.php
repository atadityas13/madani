<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class PengumumanController extends Controller
{
    /**
     * Legacy public feed for published APK — maps to notifikasi jenis=pengumuman audience=semua_guru.
     */
    public function index(): JsonResponse
    {
        try {
            $items = Notifikasi::query()
                ->published()
                ->where('jenis', Notifikasi::JENIS_PENGUMUMAN)
                ->where('audience', Notifikasi::AUDIENCE_SEMUA_GURU)
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(50)
                ->get()
                ->map(function (Notifikasi $p) {
                    $at = $p->published_at ?? $p->created_at;

                    return [
                        'id' => $p->id,
                        'judul' => $p->judul,
                        'isi' => $p->isi,
                        'published_at' => $at
                            ? $at->copy()->timezone('Asia/Jakarta')->toIso8601String()
                            : null,
                    ];
                });
        } catch (QueryException) {
            $items = collect();
        }

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }
}
