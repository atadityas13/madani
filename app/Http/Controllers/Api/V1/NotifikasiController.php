<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Models\NotifikasiRead;
use App\Models\Siswa;
use App\Models\User;
use App\Support\NotifikasiPersonalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class NotifikasiController extends Controller
{
    public function __construct(private NotifikasiPersonalizer $personalizer) {}

    public function index(Request $request): JsonResponse
    {
        $reader = $this->reader($request);
        if ($reader === null) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $jenis = $request->query('jenis');
        $kanal = $request->query('kanal');
        $query = Notifikasi::query()->published()->orderByDesc('published_at')->orderByDesc('id');

        if (is_string($jenis) && $jenis !== '') {
            $query->where('jenis', $jenis);
        } elseif ($kanal === 'lonceng') {
            $query->whereIn('jenis', [Notifikasi::JENIS_NOTIFIKASI, Notifikasi::JENIS_PENGUMUMAN]);
        }

        $items = $query->limit(100)->get()->filter(function (Notifikasi $item) use ($reader) {
            return $reader instanceof User
                ? $item->isVisibleToGuru($reader)
                : $item->isVisibleToSiswa($reader);
        })->values();

        $reads = NotifikasiRead::query()
            ->where('reader_type', $reader::class)
            ->where('reader_id', (string) $reader->getKey())
            ->whereIn('notifikasi_id', $items->pluck('id'))
            ->get()
            ->keyBy('notifikasi_id');

        if ($kanal === 'lonceng') {
            $items = $items->reject(function (Notifikasi $item) use ($reads) {
                $read = $reads->get($item->id);

                return $read !== null && $read->cleared_at !== null;
            })->values();
        }

        $data = $items->map(function (Notifikasi $item) use ($reads, $reader) {
            $at = $item->published_at ?? $item->created_at;
            $read = $reads->get($item->id);

            return [
                'id' => $item->id,
                'judul' => $this->personalizer->render($item->judul, $reader),
                'isi' => $this->personalizer->render($item->isi, $reader),
                'jenis' => $item->jenis,
                'gambar_url' => $item->gambar_url,
                'link' => $item->link,
                'audio_url' => $item->audio_url,
                'sound_key' => $item->sound_key ?: Notifikasi::SOUND_DEFAULT,
                'priority' => $item->priority ?: Notifikasi::PRIORITY_NORMAL,
                'use_periode' => (bool) $item->use_periode,
                'dismissible' => $item->isDismissible(),
                'starts_at' => $item->starts_at?->timezone('Asia/Jakarta')->toIso8601String(),
                'ends_at' => $item->ends_at?->timezone('Asia/Jakarta')->toIso8601String(),
                'published_at' => $at?->copy()->timezone('Asia/Jakarta')->toIso8601String(),
                'is_read' => $read !== null && $read->read_at !== null,
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

    public function markAllRead(Request $request): JsonResponse
    {
        $reader = $this->reader($request);
        if ($reader === null) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $ids = $this->loncengVisibleIds($request, $reader);

        foreach ($ids as $id) {
            NotifikasiRead::query()->updateOrCreate(
                [
                    'notifikasi_id' => $id,
                    'reader_type' => $reader::class,
                    'reader_id' => (string) $reader->getKey(),
                ],
                ['read_at' => now()],
            );
        }

        return response()->json(['success' => true, 'marked' => $ids->count()]);
    }

    public function clear(Request $request): JsonResponse
    {
        $reader = $this->reader($request);
        if ($reader === null) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $ids = $this->loncengVisibleIds($request, $reader);
        $now = now();

        foreach ($ids as $id) {
            NotifikasiRead::query()->updateOrCreate(
                [
                    'notifikasi_id' => $id,
                    'reader_type' => $reader::class,
                    'reader_id' => (string) $reader->getKey(),
                ],
                [
                    'read_at' => $now,
                    'cleared_at' => $now,
                ],
            );
        }

        return response()->json(['success' => true, 'cleared' => $ids->count()]);
    }

    /**
     * @return Collection<int, int>
     */
    private function loncengVisibleIds(Request $request, User|Siswa $reader): Collection
    {
        $kanal = $request->query('kanal', 'lonceng');
        $query = Notifikasi::query()->published();
        if ($kanal === 'lonceng') {
            $query->whereIn('jenis', [Notifikasi::JENIS_NOTIFIKASI, Notifikasi::JENIS_PENGUMUMAN]);
        }

        return $query->limit(200)->get()->filter(function (Notifikasi $item) use ($reader) {
            return $reader instanceof User
                ? $item->isVisibleToGuru($reader)
                : $item->isVisibleToSiswa($reader);
        })->pluck('id');
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
