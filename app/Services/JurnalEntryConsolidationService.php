<?php

namespace App\Services;

use App\Models\JurnalPembelajaran;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class JurnalEntryConsolidationService
{
    /**
     * Batasi penggabungan agar tidak menelan banyak jam sehari
     * hanya karena materi sama / placeholder "-".
     */
    private const MAX_ENTRIES_PER_MERGE = 3;

    /**
     * Gabungkan entri jam berurutan (mis. jam 1 & 2) menjadi satu baris dengan jam_list.
     *
     * Hanya untuk pasangan/tiga serangkai yang materi-nya bermakna (bukan "-"),
     * dan setiap baris masih mewakili ≤1 jam sebelum digabung.
     *
     * @return array{groups_merged: int, rows_removed: int, skipped_runs: int, dry_run: bool}
     */
    public function consolidate(?int $userId = null, bool $dryRun = false): array
    {
        $groupsMerged = 0;
        $rowsRemoved = 0;
        $skippedRuns = 0;

        $query = JurnalPembelajaran::query()
            ->orderBy('user_id')
            ->orderBy('tanggal')
            ->orderBy('kelas_id')
            ->orderBy('mapel_id')
            ->orderBy('id');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $byBucket = $query->get()->groupBy(fn (JurnalPembelajaran $row) => implode('|', [
            (string) $row->user_id,
            optional($row->tanggal)->format('Y-m-d') ?? '',
            (string) $row->kelas_id,
            (string) $row->mapel_id,
            (string) $row->materi_pokok,
            (string) $row->ketercapaian,
        ]));

        $runner = function () use ($byBucket, $dryRun, &$groupsMerged, &$rowsRemoved, &$skippedRuns): void {
            foreach ($byBucket as $entries) {
                /** @var Collection<int, JurnalPembelajaran> $entries */
                if ($entries->count() < 2) {
                    continue;
                }

                if (! $this->materiBolehDigabung((string) $entries->first()->materi_pokok)) {
                    $skippedRuns++;

                    continue;
                }

                $runs = $this->consecutiveRuns($entries);
                foreach ($runs as $run) {
                    if ($run->count() < 2) {
                        continue;
                    }

                    if ($run->count() > self::MAX_ENTRIES_PER_MERGE) {
                        $skippedRuns++;

                        continue;
                    }

                    if ($run->contains(fn (JurnalPembelajaran $e) => count($this->jamListOf($e)) > 1)) {
                        $skippedRuns++;

                        continue;
                    }

                    $groupsMerged++;
                    $rowsRemoved += $run->count() - 1;

                    if ($dryRun) {
                        continue;
                    }

                    $this->mergeRun($run);
                }
            }
        };

        if ($dryRun) {
            $runner();
        } else {
            DB::transaction($runner);
        }

        return [
            'groups_merged' => $groupsMerged,
            'rows_removed' => $rowsRemoved,
            'skipped_runs' => $skippedRuns,
            'dry_run' => $dryRun,
        ];
    }

    private function materiBolehDigabung(string $materi): bool
    {
        $materi = trim($materi);

        if ($materi === '' || $materi === '-') {
            return false;
        }

        return mb_strlen($materi) >= 3;
    }

    /**
     * @param  Collection<int, JurnalPembelajaran>  $entries
     * @return list<Collection<int, JurnalPembelajaran>>
     */
    private function consecutiveRuns(Collection $entries): array
    {
        $sorted = $entries
            ->sortBy(fn (JurnalPembelajaran $e) => sprintf(
                '%03d-%010d',
                (int) ($this->jamListOf($e)[0] ?? $e->jam_ke),
                (int) $e->id,
            ))
            ->values();

        $runs = [];
        $current = collect();

        foreach ($sorted as $entry) {
            if ($current->isEmpty()) {
                $current->push($entry);

                continue;
            }

            /** @var JurnalPembelajaran $last */
            $last = $current->last();
            $entryJams = $this->jamListOf($entry);
            $lastJams = $this->jamListOf($last);
            $firstJam = (int) ($entryJams[0] ?? 0);
            $lastJam = (int) ($lastJams[array_key_last($lastJams)] ?? 0);

            // Hanya jam berurutan ketat; overlap tidak digabung otomatis.
            if ($firstJam > 0 && $firstJam === $lastJam + 1) {
                $current->push($entry);

                continue;
            }

            $runs[] = $current;
            $current = collect([$entry]);
        }

        if ($current->isNotEmpty()) {
            $runs[] = $current;
        }

        return $runs;
    }

    /**
     * @param  Collection<int, JurnalPembelajaran>  $run
     */
    private function mergeRun(Collection $run): void
    {
        $keeper = $run->sortBy([
            fn (JurnalPembelajaran $e) => (int) ($this->jamListOf($e)[0] ?? $e->jam_ke),
            fn (JurnalPembelajaran $e) => (int) $e->id,
        ])->first();

        $allJams = $run
            ->flatMap(fn (JurnalPembelajaran $e) => $this->jamListOf($e))
            ->map(fn ($j) => (int) $j)
            ->filter(fn (int $j) => $j > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $allJadwal = $run
            ->flatMap(function (JurnalPembelajaran $e) {
                $ids = array_values(array_map('intval', $e->jadwal_ids ?? []));
                if ($ids !== []) {
                    return $ids;
                }

                return $e->jadwal_id ? [(int) $e->jadwal_id] : [];
            })
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $keeper->update([
            'jam_ke' => $allJams[0] ?? (int) $keeper->jam_ke,
            'jam_list' => $allJams,
            'jadwal_id' => $allJadwal[0] ?? $keeper->jadwal_id,
            'jadwal_ids' => $allJadwal,
            'penugasan_siswa' => $run->pluck('penugasan_siswa')->first(fn ($v) => filled($v)) ?? $keeper->penugasan_siswa,
            'catatan_guru' => $run->pluck('catatan_guru')->first(fn ($v) => filled($v)) ?? $keeper->catatan_guru,
            'nama_kelas' => $run->pluck('nama_kelas')->first(fn ($v) => filled($v)) ?? $keeper->nama_kelas,
            'nama_mapel' => $run->pluck('nama_mapel')->first(fn ($v) => filled($v)) ?? $keeper->nama_mapel,
            'hari' => $run->pluck('hari')->first(fn ($v) => filled($v)) ?? $keeper->hari,
        ]);

        $removeIds = $run
            ->reject(fn (JurnalPembelajaran $e) => (int) $e->id === (int) $keeper->id)
            ->pluck('id')
            ->all();

        if ($removeIds !== []) {
            JurnalPembelajaran::query()->whereIn('id', $removeIds)->delete();
        }
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
}
