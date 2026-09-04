<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Models\Siswa;
use App\Models\User;
use App\Support\NotifikasiPersonalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PengumumanController extends Controller
{
    /**
     * Legacy alias — authenticated feed jenis=pengumuman untuk kompatibilitas APK lama.
     * Prefer /api/v1/notifikasi?jenis=pengumuman.
     */
    public function __construct(private NotifikasiPersonalizer $personalizer) {}

    public function index(Request $request): JsonResponse
    {
        $reader = $request->user();
        if (! $reader instanceof User && ! $reader instanceof Siswa) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $items = Notifikasi::query()
            ->published()
            ->where('jenis', Notifikasi::JENIS_PENGUMUMAN)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->filter(function (Notifikasi $item) use ($reader) {
                return $reader instanceof User
                    ? $item->isVisibleToGuru($reader)
                    : $item->isVisibleToSiswa($reader);
            })
            ->values()
            ->map(function (Notifikasi $p) use ($reader) {
                $at = $p->published_at ?? $p->created_at;

                return [
                    'id' => $p->id,
                    'judul' => $this->personalizer->render($p->judul, $reader),
                    'isi' => $this->personalizer->render($p->isi, $reader),
                    'published_at' => $at
                        ? $at->copy()->timezone('Asia/Jakarta')->toIso8601String()
                        : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }
}
