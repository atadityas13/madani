<?php

namespace App\Services;

use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\KelengkapanSiswa;
use App\Support\R2Url;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiswaMonitoringService
{
    /** @var list<string> */
    public const FILTER_VARIABEL = [
        'login',
        'data-siswa',
        'orang-tua',
        'alamat',
        'rekam-didik',
        'foto',
        'kk',
        'akta_lahir',
        'kip',
        'kks',
        'pkh',
        'ijazah_sd',
        'pernyataan',
        'pengajuan_pending',
        'nis',
    ];

    /** @var list<string> */
    private const DOKUMEN_JENIS = ['kk', 'akta_lahir', 'kip', 'kks', 'pkh', 'ijazah_sd'];

    /**
     * @return array{
     *     q: string,
     *     tingkat: string,
     *     rombel_id: string,
     *     per_page: int,
     *     status_lengkap: string,
     *     belum: list<string>,
     *     tingkat_options: Collection<int, string>,
     *     rombels: Collection<int, Rombel>,
     *     rows: LengthAwarePaginator
     * }
     */
    public function halaman(Request $request, User $user): array
    {
        $filters = $this->parseFilters($request, $user);
        $rows = $this->kumpulkanRows($filters, $user);
        $rows = $this->terapkanFilterLengkap($rows, $filters['status_lengkap'], $filters['belum']);

        $page = max(1, (int) $request->query('page', 1));
        $perPage = $filters['per_page'];
        $total = $rows->count();
        $slice = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new Paginator($slice, $total, $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return [
            ...$filters,
            'tingkat_options' => $filters['tingkat_options'],
            'rombels' => $filters['rombels'],
            'rows' => $paginator,
        ];
    }

    public function ekspor(Request $request, User $user): StreamedResponse
    {
        $filters = $this->parseFilters($request, $user);
        $rows = $this->terapkanFilterLengkap(
            $this->kumpulkanRows($filters, $user),
            $filters['status_lengkap'],
            $filters['belum'],
        );

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('monitoring');

        $headers = [
            'No', 'Nama', 'NISN', 'NIS', 'Rombel',
            'Login', 'Orang tua', 'Alamat', 'Rekam didik',
            'Foto', 'KK', 'Akta', 'KIP', 'KKS', 'PKH', 'Ijazah SD',
            'Pernyataan biodata', 'Pernyataan peserta didik', 'Pengajuan pending',
        ];
        $sheet->fromArray([$headers], null, 'A1');

        $data = [];
        foreach ($rows->values() as $i => $row) {
            $data[] = [
                $i + 1,
                $row['nama'],
                $row['nisn'] ?: '',
                $row['nis'] ?: '',
                $row['rombel_label'],
                $this->yaTidak($row['flags']['login']),
                $this->yaTidak($row['flags']['orang-tua']),
                $this->yaTidak($row['flags']['alamat']),
                $this->yaTidak($row['flags']['rekam-didik']),
                $this->yaTidak($row['flags']['foto']),
                $this->yaTidak($row['flags']['kk']),
                $this->yaTidak($row['flags']['akta_lahir']),
                $this->yaTidak($row['flags']['kip']),
                $this->yaTidak($row['flags']['kks']),
                $this->yaTidak($row['flags']['pkh']),
                $this->yaTidak($row['flags']['ijazah_sd']),
                $this->yaTidak($row['flags']['pernyataan']),
                $this->yaTidak($row['flags']['pernyataan']),
                $row['pengajuan_pending'],
            ];
        }
        if ($data !== []) {
            $sheet->fromArray($data, null, 'A2');
        }

        foreach (range('A', 'S') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, 'monitoring-siswa.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return array{
     *     q: string,
     *     tingkat: string,
     *     rombel_id: string,
     *     per_page: int,
     *     status_lengkap: string,
     *     belum: list<string>,
     *     tingkat_options: Collection<int, string>,
     *     rombels: Collection<int, Rombel>
     * }
     */
    private function parseFilters(Request $request, User $user): array
    {
        $q = trim((string) $request->query('q', ''));
        $tingkat = trim((string) $request->query('tingkat', ''));
        $rombelId = trim((string) $request->query('rombel_id', ''));
        $perPageRaw = (string) $request->query('per_page', '10');
        $perPage = in_array($perPageRaw, ['10', '25', '50', '100'], true) ? (int) $perPageRaw : 10;
        $statusLengkap = trim((string) $request->query('status_lengkap', ''));
        if (! in_array($statusLengkap, ['sudah_lengkap', 'belum_lengkap', 'belum_variabel'], true)) {
            $statusLengkap = '';
        }

        $belumRaw = $request->query('belum', []);
        if (! is_array($belumRaw)) {
            $belumRaw = filled($belumRaw) ? [(string) $belumRaw] : [];
        }
        $belum = $statusLengkap === 'belum_variabel'
            ? array_values(array_filter(
                array_map('strval', $belumRaw),
                fn (string $key) => in_array($key, self::FILTER_VARIABEL, true),
            ))
            : [];

        $tahun = TahunAjaran::aktif();
        $rombels = Rombel::query()
            ->when($tahun, fn ($query) => $query->where('tahun_ajaran_id', $tahun->id))
            ->when($user->adalahWali(), function ($query) use ($user) {
                $ids = $user->rombelIdsAktif();
                $query->whereIn('id', $ids === [] ? [0] : $ids);
            })
            ->orderBy('tingkat')
            ->orderBy('nama')
            ->get();

        $tingkatOptions = $rombels
            ->pluck('tingkat')
            ->filter(fn ($item) => filled($item))
            ->unique()
            ->sortBy(fn ($item) => Rombel::tingkatOrder($item))
            ->values();

        if ($tingkat !== '' && ! $tingkatOptions->contains($tingkat)) {
            $tingkat = '';
        }

        $rombelsForSelect = $tingkat !== ''
            ? $rombels->where('tingkat', $tingkat)->values()
            : $rombels->values();

        if ($rombelId !== '' && ! $rombelsForSelect->contains(fn (Rombel $rombel) => (string) $rombel->id === $rombelId)) {
            $rombelId = '';
        }

        return [
            'q' => $q,
            'tingkat' => $tingkat,
            'rombel_id' => $rombelId,
            'per_page' => $perPage,
            'status_lengkap' => $statusLengkap,
            'belum' => $belum,
            'tingkat_options' => $tingkatOptions,
            'rombels' => $rombelsForSelect,
        ];
    }

    /**
     * @param  array{q: string, tingkat: string, rombel_id: string}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function kumpulkanRows(array $filters, User $user): Collection
    {
        $tahun = TahunAjaran::aktif();

        $query = Siswa::query()
            ->with([
                'pernyataan',
                'dokumens',
                'orangTuas',
                'periodiks',
                'rekamDidik',
                'rombels' => function ($rombelQuery) use ($tahun) {
                    $rombelQuery->wherePivot('status', 'aktif')
                        ->when($tahun, fn ($inner) => $inner->where('tahun_ajaran_id', $tahun->id));
                },
            ])
            ->withCount([
                'pengajuanPerubahans as pengajuan_pending_count' => fn ($q) => $q->where('status', 'pending'),
            ])
            ->when($user->adalahWali(), function ($siswaQuery) use ($user) {
                $ids = $user->rombelIdsAktif();
                if ($ids === []) {
                    $siswaQuery->whereRaw('0 = 1');

                    return;
                }

                $siswaQuery->whereHas('rombels', fn ($inner) => $inner
                    ->whereIn('rombels.id', $ids)
                    ->where('rombel_siswas.status', 'aktif'));
            })
            ->when($filters['q'] !== '', function ($siswaQuery) use ($filters) {
                $q = $filters['q'];
                $siswaQuery->where(function ($inner) use ($q) {
                    $inner->where('nama', 'like', "%{$q}%")
                        ->orWhere('nisn', 'like', "%{$q}%")
                        ->orWhere('nik', 'like', "%{$q}%")
                        ->orWhere('nis', 'like', "%{$q}%");
                });
            })
            ->when($filters['tingkat'] !== '', function ($siswaQuery) use ($filters, $tahun) {
                $siswaQuery->whereHas('rombels', fn ($inner) => $inner
                    ->where('tingkat', $filters['tingkat'])
                    ->where('rombel_siswas.status', 'aktif')
                    ->when($tahun, fn ($rombel) => $rombel->where('tahun_ajaran_id', $tahun->id)));
            })
            ->when($filters['rombel_id'] !== '', function ($siswaQuery) use ($filters) {
                $siswaQuery->whereHas('rombels', fn ($inner) => $inner
                    ->where('rombels.id', $filters['rombel_id'])
                    ->where('rombel_siswas.status', 'aktif'));
            });

        $rombelUrut = DB::table('rombel_siswas')
            ->join('rombels', 'rombels.id', '=', 'rombel_siswas.rombel_id')
            ->where('rombel_siswas.status', 'aktif')
            ->when($tahun, fn ($join) => $join->where('rombels.tahun_ajaran_id', $tahun->id))
            ->select('rombel_siswas.siswa_id', 'rombels.tingkat', 'rombels.nama as rombel_nama');

        $siswas = $query
            ->leftJoinSub($rombelUrut, 'rombel_urut', 'rombel_urut.siswa_id', '=', 'siswas.id')
            ->select('siswas.*')
            ->orderByRaw("CASE siswas.angkatan WHEN 'VII' THEN 1 WHEN 'VIII' THEN 2 WHEN 'IX' THEN 3 ELSE 9 END")
            ->orderBy('rombel_urut.rombel_nama')
            ->orderBy('siswas.nama')
            ->get();

        return $siswas->map(fn (Siswa $siswa) => $this->buatRow($siswa))->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function buatRow(Siswa $siswa): array
    {
        $kelengkapan = KelengkapanSiswa::ringkasan($siswa);
        $tabs = collect($kelengkapan['tab'])->keyBy('id');
        $periodik = $siswa->periodikAktif();
        $rombel = $siswa->rombels->first();

        $flags = [
            'login' => $siswa->first_login_at !== null,
            'data-siswa' => (bool) ($tabs->get('data-siswa')['selesai'] ?? false),
            'orang-tua' => (bool) ($tabs->get('orang-tua')['selesai'] ?? false),
            'alamat' => (bool) ($tabs->get('alamat')['selesai'] ?? false),
            'rekam-didik' => (bool) ($tabs->get('rekam-didik')['selesai'] ?? false),
            'foto' => filled($siswa->foto),
            'pernyataan' => $siswa->pernyataan !== null,
            'pengajuan_pending' => ((int) $siswa->pengajuan_pending_count) > 0,
            'nis' => filled($siswa->nis),
        ];

        $previews = [
            'foto' => $flags['foto'] ? [
                'preview_url' => R2Url::temporary($siswa->foto),
                'download_url' => route('siswa.foto.download', $siswa),
                'is_pdf' => false,
            ] : null,
        ];

        foreach (self::DOKUMEN_JENIS as $jenis) {
            $dokumen = $siswa->dokumenJenis($jenis);
            $hasFile = $dokumen !== null && filled($dokumen->path);
            $flags[$jenis] = $this->dokumenOk($periodik, $jenis, $hasFile);
            $previews[$jenis] = $hasFile ? [
                'preview_url' => R2Url::temporary($dokumen->path),
                'download_url' => route('siswa.dokumen.download', [$siswa, $jenis]),
                'is_pdf' => str_ends_with(strtolower((string) $dokumen->path), '.pdf')
                    || str_ends_with(strtolower((string) $dokumen->nama_asli), '.pdf'),
            ] : null;
        }

        $previews['pernyataan_biodata'] = $flags['pernyataan'] ? [
            'preview_url' => route('siswa.pernyataan.stream', [$siswa, 'biodata']),
            'download_url' => route('siswa.pernyataan.download', [$siswa, 'biodata']),
            'is_pdf' => true,
        ] : null;
        $previews['pernyataan_peserta_didik'] = $flags['pernyataan'] ? [
            'preview_url' => route('siswa.pernyataan.stream', [$siswa, 'peserta-didik']),
            'download_url' => route('siswa.pernyataan.download', [$siswa, 'peserta-didik']),
            'is_pdf' => true,
        ] : null;
        $previews['kartu'] = [
            'preview_url' => route('siswa.kartu.stream', $siswa),
            'download_url' => route('siswa.kartu.download', $siswa),
            'is_pdf' => true,
            'external' => true,
        ];

        $globalKeys = [
            'login', 'data-siswa', 'orang-tua', 'alamat', 'rekam-didik', 'foto',
            'kk', 'akta_lahir', 'kip', 'kks', 'pkh', 'ijazah_sd', 'pernyataan',
        ];
        $globalOk = collect($globalKeys)->every(fn (string $key) => $flags[$key] === true);

        return [
            'id' => $siswa->id,
            'nama' => $siswa->nama,
            'nisn' => $siswa->nisn,
            'nis' => $siswa->nis,
            'angkatan' => $siswa->angkatan,
            'rombel_label' => $rombel ? $rombel->label() : '—',
            'status_keaktifan' => $siswa->status_keaktifan ?: '—',
            'last_login_at' => $siswa->last_login_at,
            'pengajuan_pending' => (int) $siswa->pengajuan_pending_count,
            'show_url' => route('siswa.show', $siswa),
            'ajuan_url' => route('siswa.edit', ['siswa' => $siswa, 'tab' => 'data-siswa']).'#pengajuan-perubahan',
            'flags' => $flags,
            'previews' => $previews,
            'lengkap_global' => $globalOk,
        ];
    }

    private function dokumenOk(mixed $periodik, string $jenis, bool $hasFile): bool
    {
        return match ($jenis) {
            'kk', 'akta_lahir', 'ijazah_sd' => $hasFile,
            'kip' => (bool) ($periodik?->tidak_punya_kip) || $hasFile,
            'kks' => (bool) ($periodik?->tidak_punya_kks) || $hasFile,
            'pkh' => (bool) ($periodik?->tidak_punya_pkh) || $hasFile,
            default => $hasFile,
        };
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  list<string>  $belum
     * @return Collection<int, array<string, mixed>>
     */
    private function terapkanFilterLengkap(Collection $rows, string $statusLengkap, array $belum): Collection
    {
        return $rows
            ->when($statusLengkap === 'sudah_lengkap', fn (Collection $c) => $c->filter(fn (array $row) => $row['lengkap_global']))
            ->when($statusLengkap === 'belum_lengkap', fn (Collection $c) => $c->filter(fn (array $row) => ! $row['lengkap_global']))
            ->when($statusLengkap === 'belum_variabel' && $belum !== [], function (Collection $c) use ($belum) {
                return $c->filter(function (array $row) use ($belum) {
                    foreach ($belum as $key) {
                        if (($row['flags'][$key] ?? false) === true) {
                            return false;
                        }
                    }

                    return true;
                });
            })
            ->values();
    }

    private function yaTidak(bool $ok): string
    {
        return $ok ? 'Ya' : 'Tidak';
    }
}
