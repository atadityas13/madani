<?php

namespace App\Services;

use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\KelengkapanSiswa;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

class WaliKelasDashboardService
{
    private const PER_PAGE = 10;

    /** @var list<string> */
    private const LENGKAP_KEYS = [
        'login', 'data-siswa', 'orang-tua', 'alamat', 'rekam-didik', 'foto',
        'kk', 'akta_lahir', 'kip', 'kks', 'pkh', 'ijazah_sd', 'pernyataan',
    ];

    /** @var array<string, string> */
    private const FLAG_LABELS = [
        'login' => 'Login',
        'data-siswa' => 'Identitas',
        'orang-tua' => 'Ortu',
        'alamat' => 'Alamat',
        'rekam-didik' => 'Rekam didik',
        'foto' => 'Foto',
        'kk' => 'KK',
        'akta_lahir' => 'Akta',
        'kip' => 'KIP',
        'kks' => 'KKS',
        'pkh' => 'PKH',
        'ijazah_sd' => 'Ijazah',
        'pernyataan' => 'Pernyataan',
    ];

    /** @var list<string> */
    private const DOKUMEN_JENIS = ['kk', 'akta_lahir', 'kip', 'kks', 'pkh', 'ijazah_sd'];

    /**
     * @return array{
     *     rombel: Rombel|null,
     *     tahun_label: string|null,
     *     cards: list<array{key: string, label: string, value: int, tone: string, url: string|null}>,
     *     login_terakhir: list<array{nama: string, waktu: string, url: string|null}>,
     *     belum_lengkap: LengthAwarePaginator,
     *     belum_lengkap_url: string|null
     * }
     */
    public function untuk(Request $request, User $user): array
    {
        $rombel = $this->rombelWali($user);

        if ($rombel === null) {
            return [
                'rombel' => null,
                'tahun_label' => TahunAjaran::aktif()?->label(),
                'cards' => $this->buatKartu(null, [
                    'total' => 0,
                    'laki' => 0,
                    'perempuan' => 0,
                    'lengkap' => 0,
                    'belum_lengkap' => 0,
                    'belum_login' => 0,
                    'pengajuan_pending' => 0,
                ]),
                'login_terakhir' => [],
                'belum_lengkap' => $this->paginatorKosong($request),
                'belum_lengkap_url' => null,
            ];
        }

        $rows = $this->rowsUntukRombel($rombel);
        $lengkap = $rows->filter(fn (array $row) => $row['lengkap_global'])->count();
        $counts = [
            'total' => $rows->count(),
            'laki' => $rows->filter(fn (array $row) => $row['jenis_kelamin'] === 'L')->count(),
            'perempuan' => $rows->filter(fn (array $row) => $row['jenis_kelamin'] === 'P')->count(),
            'lengkap' => $lengkap,
            'belum_lengkap' => $rows->count() - $lengkap,
            'belum_login' => $rows->filter(fn (array $row) => ! $row['flags']['login'])->count(),
            'pengajuan_pending' => (int) $rows->sum(fn (array $row) => $row['pengajuan_pending']),
        ];

        $loginTerakhir = $rows
            ->filter(fn (array $row) => $row['last_login_at'] !== null)
            ->sortByDesc(fn (array $row) => $row['last_login_at']?->timestamp ?? 0)
            ->take(5)
            ->values()
            ->map(fn (array $row) => [
                'nama' => $row['nama'],
                'waktu' => $this->formatWaktu($row['last_login_at']),
                'url' => $row['show_url'],
            ])
            ->all();

        $belumLengkapRows = $rows
            ->filter(fn (array $row) => ! $row['lengkap_global'])
            ->values()
            ->map(fn (array $row) => [
                'nama' => $row['nama'],
                'kekurangan' => $this->kekurangan($row['flags']),
                'url' => $row['show_url'],
            ]);

        return [
            'rombel' => $rombel,
            'tahun_label' => $rombel->tahunAjaran?->label() ?? TahunAjaran::aktif()?->label(),
            'cards' => $this->buatKartu($rombel, $counts),
            'login_terakhir' => $loginTerakhir,
            'belum_lengkap' => $this->paginateCollection($belumLengkapRows, $request, 'page'),
            'belum_lengkap_url' => $this->daftarUrl(['status_lengkap' => 'belum_lengkap']),
        ];
    }

    /**
     * @return array{
     *     rombel: Rombel|null,
     *     tahun_label: string|null,
     *     judul: string,
     *     rows: LengthAwarePaginator,
     *     kembali_url: string
     * }
     */
    public function daftar(Request $request, User $user): array
    {
        $rombel = $this->rombelWali($user);
        $filters = $this->parseFilters($request);
        $judul = $this->judulDaftar($filters);

        if ($rombel === null) {
            return [
                'rombel' => null,
                'tahun_label' => TahunAjaran::aktif()?->label(),
                'judul' => $judul,
                'rows' => $this->paginatorKosong($request),
                'kembali_url' => route('talim.wali'),
            ];
        }

        $rows = $this->terapkanFilter($this->rowsUntukRombel($rombel), $filters)
            ->map(fn (array $row) => [
                'nama' => $row['nama'],
                'jenis_kelamin' => $row['jenis_kelamin'],
                'kekurangan' => $row['lengkap_global'] ? [] : $this->kekurangan($row['flags']),
                'lengkap' => $row['lengkap_global'],
                'url' => $row['show_url'],
            ])
            ->values();

        return [
            'rombel' => $rombel,
            'tahun_label' => $rombel->tahunAjaran?->label() ?? TahunAjaran::aktif()?->label(),
            'judul' => $judul,
            'rows' => $this->paginateCollection($rows, $request, 'page'),
            'kembali_url' => route('talim.wali'),
        ];
    }

    /**
     * @param  array<string, int>  $counts
     * @return list<array{key: string, label: string, value: int, tone: string, url: string|null}>
     */
    private function buatKartu(?Rombel $rombel, array $counts): array
    {
        if ($rombel === null) {
            return [
                ['key' => 'total', 'label' => 'Jumlah siswa', 'value' => 0, 'tone' => '', 'url' => null],
                ['key' => 'laki', 'label' => 'Laki-laki', 'value' => 0, 'tone' => '', 'url' => null],
                ['key' => 'perempuan', 'label' => 'Perempuan', 'value' => 0, 'tone' => '', 'url' => null],
                ['key' => 'lengkap', 'label' => 'Data lengkap', 'value' => 0, 'tone' => 'is-ok', 'url' => null],
                ['key' => 'belum_lengkap', 'label' => 'Belum lengkap', 'value' => 0, 'tone' => 'is-warn', 'url' => null],
                ['key' => 'belum_login', 'label' => 'Belum pernah login', 'value' => 0, 'tone' => 'is-warn', 'url' => null],
                ['key' => 'pengajuan_pending', 'label' => 'Pengajuan pending', 'value' => 0, 'tone' => '', 'url' => null],
            ];
        }

        return [
            [
                'key' => 'total',
                'label' => 'Jumlah siswa',
                'value' => $counts['total'],
                'tone' => '',
                'url' => $this->daftarUrl(),
            ],
            [
                'key' => 'laki',
                'label' => 'Laki-laki',
                'value' => $counts['laki'],
                'tone' => '',
                'url' => $this->daftarUrl(['jenis_kelamin' => 'L']),
            ],
            [
                'key' => 'perempuan',
                'label' => 'Perempuan',
                'value' => $counts['perempuan'],
                'tone' => '',
                'url' => $this->daftarUrl(['jenis_kelamin' => 'P']),
            ],
            [
                'key' => 'lengkap',
                'label' => 'Data lengkap',
                'value' => $counts['lengkap'],
                'tone' => 'is-ok',
                'url' => $this->daftarUrl(['status_lengkap' => 'sudah_lengkap']),
            ],
            [
                'key' => 'belum_lengkap',
                'label' => 'Belum lengkap',
                'value' => $counts['belum_lengkap'],
                'tone' => 'is-warn',
                'url' => $this->daftarUrl(['status_lengkap' => 'belum_lengkap']),
            ],
            [
                'key' => 'belum_login',
                'label' => 'Belum pernah login',
                'value' => $counts['belum_login'],
                'tone' => 'is-warn',
                'url' => $this->daftarUrl([
                    'status_lengkap' => 'belum_variabel',
                    'belum' => ['login'],
                ]),
            ],
            [
                'key' => 'pengajuan_pending',
                'label' => 'Pengajuan pending',
                'value' => $counts['pengajuan_pending'],
                'tone' => $counts['pengajuan_pending'] > 0 ? 'is-warn' : '',
                'url' => $this->daftarUrl([
                    'status_lengkap' => 'belum_variabel',
                    'belum' => ['pengajuan_pending'],
                ]),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function daftarUrl(array $extra = []): string
    {
        return route('talim.wali.siswa', $extra);
    }

    public function rombelWali(User $user): ?Rombel
    {
        if (! $user->gtk_id) {
            return null;
        }

        $tahun = TahunAjaran::aktif();

        return Rombel::query()
            ->with('tahunAjaran')
            ->where('gtk_id', $user->gtk_id)
            ->when($tahun, fn ($query) => $query->where('tahun_ajaran_id', $tahun->id))
            ->orderBy('id')
            ->first();
    }

    /**
     * @return array{jenis_kelamin: string, status_lengkap: string, belum: list<string>}
     */
    private function parseFilters(Request $request): array
    {
        $jenisKelamin = strtoupper(trim((string) $request->query('jenis_kelamin', '')));
        if (! in_array($jenisKelamin, ['L', 'P'], true)) {
            $jenisKelamin = '';
        }

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
                fn (string $key) => in_array($key, [...self::LENGKAP_KEYS, 'pengajuan_pending'], true),
            ))
            : [];

        return [
            'jenis_kelamin' => $jenisKelamin,
            'status_lengkap' => $statusLengkap,
            'belum' => $belum,
        ];
    }

    /**
     * @param  array{jenis_kelamin: string, status_lengkap: string, belum: list<string>}  $filters
     */
    private function judulDaftar(array $filters): string
    {
        return match (true) {
            $filters['jenis_kelamin'] === 'L' => 'Siswa laki-laki',
            $filters['jenis_kelamin'] === 'P' => 'Siswa perempuan',
            $filters['status_lengkap'] === 'sudah_lengkap' => 'Data lengkap',
            $filters['status_lengkap'] === 'belum_lengkap' => 'Siswa belum lengkap',
            $filters['status_lengkap'] === 'belum_variabel' && in_array('login', $filters['belum'], true) => 'Belum pernah login',
            $filters['status_lengkap'] === 'belum_variabel' && in_array('pengajuan_pending', $filters['belum'], true) => 'Pengajuan pending',
            default => 'Daftar siswa',
        };
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array{jenis_kelamin: string, status_lengkap: string, belum: list<string>}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function terapkanFilter(Collection $rows, array $filters): Collection
    {
        return $rows
            ->when($filters['jenis_kelamin'] !== '', fn (Collection $items) => $items
                ->filter(fn (array $row) => $row['jenis_kelamin'] === $filters['jenis_kelamin']))
            ->when($filters['status_lengkap'] === 'sudah_lengkap', fn (Collection $items) => $items
                ->filter(fn (array $row) => $row['lengkap_global']))
            ->when($filters['status_lengkap'] === 'belum_lengkap', fn (Collection $items) => $items
                ->filter(fn (array $row) => ! $row['lengkap_global']))
            ->when($filters['status_lengkap'] === 'belum_variabel' && $filters['belum'] !== [], function (Collection $items) use ($filters) {
                return $items->filter(function (array $row) use ($filters) {
                    foreach ($filters['belum'] as $key) {
                        if ($key === 'pengajuan_pending') {
                            if (((int) $row['pengajuan_pending']) > 0) {
                                return true;
                            }

                            continue;
                        }

                        if (! ($row['flags'][$key] ?? false)) {
                            return true;
                        }
                    }

                    return false;
                });
            })
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     */
    private function paginateCollection(Collection $items, Request $request, string $pageName): LengthAwarePaginator
    {
        $page = max(1, (int) $request->query($pageName, 1));
        $total = $items->count();
        $slice = $items->slice(($page - 1) * self::PER_PAGE, self::PER_PAGE)->values();

        return new Paginator($slice, $total, self::PER_PAGE, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
            'pageName' => $pageName,
        ]);
    }

    private function paginatorKosong(Request $request): LengthAwarePaginator
    {
        return $this->paginateCollection(collect(), $request, 'page');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function rowsUntukRombel(Rombel $rombel): Collection
    {
        $siswas = Siswa::query()
            ->with([
                'pernyataan',
                'dokumens',
                'orangTuas',
                'periodiks',
                'rekamDidik',
            ])
            ->withCount([
                'pengajuanPerubahans as pengajuan_pending_count' => fn ($q) => $q->where('status', 'pending'),
            ])
            ->whereHas('rombels', fn ($inner) => $inner
                ->where('rombels.id', $rombel->id)
                ->where('rombel_siswas.status', 'aktif'))
            ->orderBy('nama')
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

        $flags = [
            'login' => $siswa->first_login_at !== null,
            'data-siswa' => (bool) ($tabs->get('data-siswa')['selesai'] ?? false),
            'orang-tua' => (bool) ($tabs->get('orang-tua')['selesai'] ?? false),
            'alamat' => (bool) ($tabs->get('alamat')['selesai'] ?? false),
            'rekam-didik' => (bool) ($tabs->get('rekam-didik')['selesai'] ?? false),
            'foto' => filled($siswa->foto),
            'pernyataan' => $siswa->pernyataan !== null,
        ];

        foreach (self::DOKUMEN_JENIS as $jenis) {
            $dokumen = $siswa->dokumenJenis($jenis);
            $hasFile = $dokumen !== null && filled($dokumen->path);
            $flags[$jenis] = $this->dokumenOk($periodik, $jenis, $hasFile);
        }

        $lengkap = collect(self::LENGKAP_KEYS)->every(fn (string $key) => ($flags[$key] ?? false) === true);

        return [
            'nama' => $siswa->nama,
            'jenis_kelamin' => $siswa->jenis_kelamin,
            'last_login_at' => $siswa->last_login_at,
            'pengajuan_pending' => (int) $siswa->pengajuan_pending_count,
            'flags' => $flags,
            'lengkap_global' => $lengkap,
            'show_url' => route('siswa.show', $siswa),
        ];
    }

    /**
     * @param  array<string, bool>  $flags
     * @return list<string>
     */
    private function kekurangan(array $flags): array
    {
        $labels = [];
        foreach (self::LENGKAP_KEYS as $key) {
            if (! ($flags[$key] ?? false)) {
                $labels[] = self::FLAG_LABELS[$key] ?? $key;
            }
        }

        return $labels;
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

    public function formatWaktu(?CarbonInterface $at): string
    {
        if ($at === null) {
            return '—';
        }

        $local = $at->timezone(config('app.timezone'));

        return $this->hariIndonesia($local).', '.$local->format('d-m-Y').' pukul '.$local->format('H:i:s');
    }

    private function hariIndonesia(CarbonInterface $date): string
    {
        return match ($date->dayOfWeek) {
            CarbonInterface::MONDAY => 'Senin',
            CarbonInterface::TUESDAY => 'Selasa',
            CarbonInterface::WEDNESDAY => 'Rabu',
            CarbonInterface::THURSDAY => 'Kamis',
            CarbonInterface::FRIDAY => 'Jumat',
            CarbonInterface::SATURDAY => 'Sabtu',
            default => 'Minggu',
        };
    }
}
