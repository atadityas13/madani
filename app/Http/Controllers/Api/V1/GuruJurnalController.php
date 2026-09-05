<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Gtk;
use App\Models\JurnalPembelajaran;
use App\Models\User;
use App\Services\CetakPresetService;
use App\Services\JamPelajaranService;
use App\Support\JurnalWriteGate;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class GuruJurnalController extends Controller
{
    public function __construct(
        private JamPelajaranService $jamPelajaranService,
        private CetakPresetService $cetakPresetService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $entries = JurnalPembelajaran::query()
            ->where('user_id', $user->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        $byKelas = $entries->groupBy('kelas_id');
        $data = $byKelas->map(function ($rows, $kelasId) {
            $mapel = $rows
                ->unique('mapel_id')
                ->map(fn (JurnalPembelajaran $row) => [
                    'id' => (int) $row->mapel_id,
                    'nama' => $row->nama_mapel,
                ])
                ->values()
                ->all();

            /** @var JurnalPembelajaran $first */
            $first = $rows->first();

            return [
                'kelas_id' => (int) $kelasId,
                'nama_kelas' => $first->nama_kelas,
                'tingkat' => null,
                'jumlah_entri' => $rows->count(),
                'mapel' => $mapel,
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'semester' => $this->semesterFromEntries($entries),
            'data' => $data,
        ]);
    }

    public function entries(Request $request, int $kelasId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $entries = JurnalPembelajaran::query()
            ->where('user_id', $user->id)
            ->where('kelas_id', $kelasId)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        $mapel = $entries
            ->unique('mapel_id')
            ->map(fn (JurnalPembelajaran $row) => [
                'id' => (int) $row->mapel_id,
                'nama' => $row->nama_mapel,
            ])
            ->values()
            ->all();

        $namaKelas = $entries->first()?->nama_kelas;

        return response()->json([
            'success' => true,
            'semester' => $this->semesterFromEntries($entries),
            'kelas' => [
                'id' => $kelasId,
                'nama_kelas' => $namaKelas,
            ],
            'mapel' => $mapel,
            'data' => $entries->map(fn (JurnalPembelajaran $row) => $row->toApiArray())->values()->all(),
        ]);
    }

    public function entriesByTanggal(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'tanggal' => ['required', 'date_format:Y-m-d'],
        ]);

        $entries = JurnalPembelajaran::query()
            ->where('user_id', $user->id)
            ->whereDate('tanggal', $data['tanggal'])
            ->orderBy('jam_ke')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'tanggal' => $data['tanggal'],
            'data' => $entries->map(fn (JurnalPembelajaran $row) => $row->toApiArray())->values()->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($denied = JurnalWriteGate::denyIfDisabled()) {
            return $denied;
        }

        /** @var User $user */
        $user = $request->user();
        $payload = $this->validatedUpsert($request);

        $entry = JurnalPembelajaran::query()->create([
            ...$payload,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jurnal berhasil disimpan.',
            'data' => $entry->toApiArray(),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if ($denied = JurnalWriteGate::denyIfDisabled()) {
            return $denied;
        }

        /** @var User $user */
        $user = $request->user();
        $entry = JurnalPembelajaran::query()
            ->where('user_id', $user->id)
            ->whereKey($id)
            ->first();

        if ($entry === null) {
            return response()->json([
                'success' => false,
                'message' => 'Jurnal tidak ditemukan.',
            ], 404);
        }

        $payload = $this->validatedUpsert($request);
        $entry->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'Jurnal berhasil diperbarui.',
            'data' => $entry->fresh()->toApiArray(),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if ($denied = JurnalWriteGate::denyIfDisabled()) {
            return $denied;
        }

        /** @var User $user */
        $user = $request->user();
        $entry = JurnalPembelajaran::query()
            ->where('user_id', $user->id)
            ->whereKey($id)
            ->first();

        if ($entry === null) {
            return response()->json([
                'success' => false,
                'message' => 'Jurnal tidak ditemukan.',
            ], 404);
        }

        $entry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jurnal berhasil dihapus.',
        ]);
    }

    public function cetak(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('gtk');

        if ($user->gtk === null) {
            return response('Profil guru tidak ditemukan.', 404);
        }

        $kelasId = $request->query('kelas_id');
        $query = JurnalPembelajaran::query()
            ->where('user_id', $user->id)
            ->orderBy('kelas_id')
            ->orderBy('tanggal')
            ->orderBy('jam_ke')
            ->orderBy('id');

        if ($kelasId !== null && $kelasId !== '') {
            $query->where('kelas_id', (int) $kelasId);
        }

        $entries = $query->get();
        if ($entries->isEmpty()) {
            $message = ($kelasId !== null && $kelasId !== '')
                ? 'Belum ada entri jurnal untuk kelas ini.'
                : 'Belum ada entri jurnal untuk dicetak. Isi jurnal minimal satu kelas terlebih dahulu.';

            return response($message, 422);
        }

        $guru = (object) [
            'nama_lengkap' => $user->gtk->nama_lengkap,
            'username' => $user->gtk->nip ?: $user->username,
        ];

        $activeSemester = $this->resolveSemesterForCetak($entries);

        $sections = $entries
            ->groupBy('kelas_id')
            ->map(function (Collection $kelasEntries) {
                /** @var JurnalPembelajaran $first */
                $first = $kelasEntries->first();

                return [
                    'kelas' => (object) [
                        'nama_kelas' => $first->nama_kelas,
                    ],
                    'rows' => $this->buildCetakRows($kelasEntries),
                ];
            })
            ->sortBy(fn (array $section) => $section['kelas']->nama_kelas ?? '')
            ->values();

        return $this->renderJurnalCetak($guru, $activeSemester, $sections);
    }

    /**
     * @param  Collection<int, JurnalPembelajaran>  $entries
     */
    private function resolveSemesterForCetak(Collection $entries): object
    {
        $withMeta = $entries->first(
            fn (JurnalPembelajaran $row) => filled($row->semester_tipe) || filled($row->semester_nama_tahun)
        );

        return (object) [
            'tipe' => $withMeta?->semester_tipe ?: '—',
            'nama_tahun' => $withMeta?->semester_nama_tahun ?: '—',
        ];
    }

    /**
     * @param  Collection<int, array{kelas: object, rows: list<array<string, mixed>>}>  $sections
     */
    private function renderJurnalCetak(object $guru, object $semester, Collection $sections): Response
    {
        $kepala = $this->resolveKepalaMadrasah();

        return response()
            ->view(
                'guru.cetak.jurnal-pembelajaran',
                array_merge(
                    [
                        'activeSemester' => $semester,
                        'guru' => $guru,
                        'sections' => $sections,
                        'kepalaMadrasah' => $kepala,
                        'tempatCetak' => 'Majalengka',
                        'tanggalCetak' => $this->cetakPresetService->tanggalCarbon(),
                    ],
                    $this->cetakPresetService->viewData(),
                )
            )
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * @return object{nama_lengkap: string, username: string}|null
     */
    private function resolveKepalaMadrasah(): ?object
    {
        $gtk = Gtk::query()
            ->where('status', 'aktif')
            ->where(function ($query) {
                $query->where('jabatan', 'like', '%Kepala Madrasah%')
                    ->orWhere('jabatan', 'like', '%Plt. Kepala%')
                    ->orWhere('jabatan', 'like', '%Plt Kepala%');
            })
            ->orderByRaw("CASE WHEN jabatan LIKE '%Plt%' THEN 1 ELSE 0 END")
            ->orderBy('id')
            ->first();

        if ($gtk === null || ! filled($gtk->nip)) {
            return null;
        }

        return (object) [
            'nama_lengkap' => $gtk->nama_lengkap,
            'username' => (string) $gtk->nip,
        ];
    }

    /**
     * @param  Collection<int, JurnalPembelajaran>  $entries
     * @return list<array<string, mixed>>
     */
    private function buildCetakRows(Collection $entries): array
    {
        return $this->groupJournalEntries($entries)
            ->map(function (Collection $group) {
                $primary = $group->sortBy([
                    ['jam_ke', 'asc'],
                    ['id', 'asc'],
                ])->first();

                $jamList = $group
                    ->flatMap(fn (JurnalPembelajaran $entry) => $this->jamListOf($entry))
                    ->map(fn ($j) => (int) $j)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();

                $hari = (string) ($primary->hari ?? '');

                return [
                    'hari' => $hari,
                    'tanggal' => $primary->tanggal,
                    'waktu' => $this->jamPelajaranService->waktuRangeFor($hari, $jamList),
                    'mapel' => $primary->nama_mapel,
                    'materi_pokok' => (string) $primary->materi_pokok,
                    'ketercapaian' => (string) $primary->ketercapaian,
                    'penugasan_siswa' => $group->pluck('penugasan_siswa')->first(fn ($v) => filled($v)),
                    'catatan_guru' => $group->pluck('catatan_guru')->first(fn ($v) => filled($v)),
                    '_sort_tanggal' => optional($primary->tanggal)->format('Y-m-d') ?? '',
                    '_sort_jam' => $jamList[0] ?? 0,
                ];
            })
            ->sortBy([
                ['_sort_tanggal', 'asc'],
                ['_sort_jam', 'asc'],
            ])
            ->map(function (array $row) {
                unset($row['_sort_tanggal'], $row['_sort_jam']);

                return $row;
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, JurnalPembelajaran>  $entries
     * @return Collection<int, Collection<int, JurnalPembelajaran>>
     */
    private function groupJournalEntries(Collection $entries): Collection
    {
        $sorted = $entries
            ->sortBy(fn (JurnalPembelajaran $e) => sprintf(
                '%s-%010d-%03d-%010d',
                optional($e->tanggal)->format('Y-m-d') ?? '',
                (int) $e->mapel_id,
                (int) ($this->jamListOf($e)[0] ?? $e->jam_ke),
                (int) $e->id
            ))
            ->values();

        $groups = collect();
        $current = collect();

        foreach ($sorted as $entry) {
            if ($current->isEmpty()) {
                $current->push($entry);

                continue;
            }

            /** @var JurnalPembelajaran $last */
            $last = $current->last();
            $tanggalKey = optional($entry->tanggal)->format('Y-m-d');
            $lastTanggalKey = optional($last->tanggal)->format('Y-m-d');
            $entryJams = $this->jamListOf($entry);
            $lastJams = $this->jamListOf($last);
            $jamKe = (int) ($entryJams[0] ?? 0);
            $lastJamKe = (int) ($lastJams[array_key_last($lastJams)] ?? 0);
            $canMerge = $lastTanggalKey === $tanggalKey
                && (int) $last->mapel_id === (int) $entry->mapel_id
                && (string) $last->materi_pokok === (string) $entry->materi_pokok
                && (string) $last->ketercapaian === (string) $entry->ketercapaian
                && $jamKe === ($lastJamKe + 1);

            if ($canMerge) {
                $current->push($entry);

                continue;
            }

            $groups->push($current);
            $current = collect([$entry]);
        }

        if ($current->isNotEmpty()) {
            $groups->push($current);
        }

        return $groups;
    }

    /**
     * @return list<int>
     */
    private function jamListOf(JurnalPembelajaran $entry): array
    {
        $list = array_values(array_map('intval', $entry->jam_list ?? []));
        $list = array_values(array_filter($list, fn (int $j) => $j > 0));
        if ($list !== []) {
            sort($list);

            return $list;
        }

        $jamKe = (int) $entry->jam_ke;

        return $jamKe > 0 ? [$jamKe] : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedUpsert(Request $request): array
    {
        $data = $request->validate([
            'kelas_id' => ['required', 'integer', 'min:1'],
            'mapel_id' => ['required', 'integer', 'min:1'],
            'nama_kelas' => ['nullable', 'string', 'max:120'],
            'nama_mapel' => ['nullable', 'string', 'max:120'],
            'mapel' => ['nullable', 'string', 'max:120'],
            'jadwal_id' => ['nullable', 'integer', 'min:1'],
            'jadwal_ids' => ['nullable', 'array'],
            'jadwal_ids.*' => ['integer', 'min:1'],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'hari' => ['nullable', 'string', 'max:20'],
            'jam_ke' => ['nullable', 'integer', 'min:0'],
            'jam_list' => ['nullable', 'array'],
            'jam_list.*' => ['integer', 'min:1'],
            'materi_pokok' => ['required', 'string', 'max:5000'],
            'ketercapaian' => ['required', 'string', Rule::in(['tercapai', 'belum'])],
            'penugasan_siswa' => ['nullable', 'string', 'max:5000'],
            'catatan_guru' => ['nullable', 'string', 'max:5000'],
            'semester_id' => ['nullable', 'integer', 'min:1'],
            'semester_tipe' => ['nullable', 'string', 'max:20'],
            'semester_nama_tahun' => ['nullable', 'string', 'max:50'],
        ]);

        $jamList = array_values(array_map('intval', $data['jam_list'] ?? []));
        $jadwalIds = array_values(array_map('intval', $data['jadwal_ids'] ?? []));
        $jamKe = (int) ($data['jam_ke'] ?? ($jamList[0] ?? 0));
        $jadwalId = $data['jadwal_id'] ?? ($jadwalIds[0] ?? null);
        $tanggal = Carbon::createFromFormat('Y-m-d', $data['tanggal'], 'Asia/Jakarta');
        $hari = $data['hari'] ?? $this->hariIndonesia($tanggal);
        $namaMapel = $data['nama_mapel'] ?? $data['mapel'] ?? null;

        return [
            'kelas_id' => (int) $data['kelas_id'],
            'nama_kelas' => $data['nama_kelas'] ?? null,
            'mapel_id' => (int) $data['mapel_id'],
            'nama_mapel' => $namaMapel,
            'tanggal' => $data['tanggal'],
            'hari' => $hari,
            'jam_ke' => $jamKe,
            'jam_list' => $jamList,
            'jadwal_id' => $jadwalId,
            'jadwal_ids' => $jadwalIds,
            'materi_pokok' => $data['materi_pokok'],
            'ketercapaian' => $data['ketercapaian'],
            'penugasan_siswa' => $data['penugasan_siswa'] ?? null,
            'catatan_guru' => $data['catatan_guru'] ?? null,
            'semester_id' => $data['semester_id'] ?? null,
            'semester_tipe' => $data['semester_tipe'] ?? null,
            'semester_nama_tahun' => $data['semester_nama_tahun'] ?? null,
        ];
    }

    /**
     * @param  Collection<int, JurnalPembelajaran>  $entries
     * @return array{id: int|null, nama: string|null, nama_tahun: string|null, tipe: string|null}|null
     */
    private function semesterFromEntries($entries): ?array
    {
        /** @var JurnalPembelajaran|null $withSemester */
        $withSemester = $entries->first(fn (JurnalPembelajaran $row) => filled($row->semester_tipe) || filled($row->semester_nama_tahun));

        if ($withSemester === null) {
            return null;
        }

        return [
            'id' => $withSemester->semester_id,
            'nama' => null,
            'nama_tahun' => $withSemester->semester_nama_tahun,
            'tipe' => $withSemester->semester_tipe,
        ];
    }

    private function hariIndonesia(Carbon $date): string
    {
        return match ($date->dayOfWeek) {
            Carbon::MONDAY => 'Senin',
            Carbon::TUESDAY => 'Selasa',
            Carbon::WEDNESDAY => 'Rabu',
            Carbon::THURSDAY => 'Kamis',
            Carbon::FRIDAY => 'Jumat',
            Carbon::SATURDAY => 'Sabtu',
            default => 'Minggu',
        };
    }
}
