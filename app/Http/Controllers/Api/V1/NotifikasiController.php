<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Models\NotifikasiRead;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reader = $this->reader($request);
        if ($reader === null) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $jenis = $request->query('jenis');
        $query = Notifikasi::query()->published()->orderByDesc('published_at')->orderByDesc('id');
        if (is_string($jenis) && $jenis !== '') {
            $query->where('jenis', $jenis);
        }

        $items = $query->limit(100)->get()->filter(function (Notifikasi $item) use ($reader) {
            return $reader instanceof User
                ? $item->isVisibleToGuru($reader)
                : $item->isVisibleToSiswa($reader);
        })->values();

        $readIds = NotifikasiRead::query()
            ->where('reader_type', $reader::class)
            ->where('reader_id', (string) $reader->getKey())
            ->whereIn('notifikasi_id', $items->pluck('id'))
            ->pluck('notifikasi_id')
            ->all();

        $data = $items->map(function (Notifikasi $item) use ($readIds) {
            $at = $item->published_at ?? $item->created_at;

            return [
                'id' => $item->id,
                'judul' => $item->judul,
                'isi' => $item->isi,
                'jenis' => $item->jenis,
                'starts_at' => $item->starts_at?->timezone('Asia/Jakarta')->toIso8601String(),
                'ends_at' => $item->ends_at?->timezone('Asia/Jakarta')->toIso8601String(),
                'published_at' => $at?->copy()->timezone('Asia/Jakarta')->toIso8601String(),
                'is_read' => in_array($item->id, $readIds, true),
            ];
        });

        $unreadCount = $data->where('is_read', false)->count();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
            'data' => $data,
        ]);
    }

    public function markRead(Request $request, Notifikasi $notifikasi): JsonResponse
    {
        $reader = $this->reader($request);
        if ($reader === null) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $visible = $reader instanceof User
            ? $notifikasi->isVisibleToGuru($reader)
            : $notifikasi->isVisibleToSiswa($reader);

        if (! $notifikasi->is_active || ! $visible) {
            return response()->json(['success' => false, 'message' => 'Notifikasi tidak ditemukan.'], 404);
        }

        NotifikasiRead::query()->updateOrCreate(
            [
                'notifikasi_id' => $notifikasi->id,
                'reader_type' => $reader::class,
                'reader_id' => (string) $reader->getKey(),
            ],
            ['read_at' => now()],
        );

        return response()->json(['success' => true]);
    }

    private function reader(Request $request): User|Siswa|null
    {
        $user = $request->user();
        if ($user instanceof User || $user instanceof Siswa) {
            return $user;
        }

        return null;
    }
}
