<?php

namespace App\Services;

use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\KelengkapanSiswa;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class WaliKelasDashboardService
{
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
     *     belum_lengkap: list<array{nama: string, kekurangan: list<string>, url: string|null}>,
     *     belum_lengkap_url: string|null
     * }
     */
    public function untuk(User $user): array
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
                'belum_lengkap' => [],
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

        $belumLengkap = $rows
            ->filter(fn (array $row) => ! $row['lengkap_global'])
            ->take(10)
            ->values()
            ->map(fn (array $row) => [
                'nama' => $row['nama'],
                'kekurangan' => $this->kekurangan($row['flags']),
                'url' => $row['show_url'],
            ])
            ->all();

        return [
            'rombel' => $rombel,
            'tahun_label' => $rombel->tahunAjaran?->label() ?? TahunAjaran::aktif()?->label(),
            'cards' => $this->buatKartu($rombel, $counts),
            'login_terakhir' => $loginTerakhir,
            'belum_lengkap' => $belumLengkap,
            'belum_lengkap_url' => $this->monitoringUrl($rombel, ['status_lengkap' => 'belum_lengkap']),
        ];
    }

    /**
     * @param  array<string, int>  $counts
     * @return list<array{key: string, label: string, value: int, tone: string, url: string|null}>
     */
    private function buatKartu(?Rombel $rombel, array $counts): array
    {
        return [
            [
                'key' => 'total',
                'label' => 'Jumlah siswa',
                'value' => $counts['total'],
                'tone' => '',
                'url' => $this->monitoringUrl($rombel),
            ],
            [
                'key' => 'laki',
                'label' => 'Laki-laki',
                'value' => $counts['laki'],
                'tone' => '',
                'url' => $this->monitoringUrl($rombel, ['jenis_kelamin' => 'L']),
            ],
            [
                'key' => 'perempuan',
                'label' => 'Perempuan',
                'value' => $counts['perempuan'],
                'tone' => '',
                'url' => $this->monitoringUrl($rombel, ['jenis_kelamin' => 'P']),
            ],
            [
                'key' => 'lengkap',
                'label' => 'Data lengkap',
                'value' => $counts['lengkap'],
                'tone' => 'is-ok',
                'url' => $this->monitoringUrl($rombel, ['status_lengkap' => 'sudah_lengkap']),
            ],
            [
                'key' => 'belum_lengkap',
                'label' => 'Belum lengkap',
                'value' => $counts['belum_lengkap'],
                'tone' => 'is-warn',
                'url' => $this->monitoringUrl($rombel, ['status_lengkap' => 'belum_lengkap']),
            ],
            [
                'key' => 'belum_login',
                'label' => 'Belum pernah login',
                'value' => $counts['belum_login'],
                'tone' => 'is-warn',
                'url' => $this->monitoringUrl($rombel, [
                    'status_lengkap' => 'belum_variabel',
                    'belum' => ['login'],
                ]),
            ],
            [
                'key' => 'pengajuan_pending',
                'label' => 'Pengajuan pending',
                'value' => $counts['pengajuan_pending'],
                'tone' => $counts['pengajuan_pending'] > 0 ? 'is-warn' : '',
                'url' => $this->monitoringUrl($rombel, [
                    'status_lengkap' => 'belum_variabel',
                    'belum' => ['pengajuan_pending'],
                ]),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function monitoringUrl(?Rombel $rombel, array $extra = []): ?string
    {
        if ($rombel === null) {
            return null;
        }

        return route('siswa.monitoring', array_merge([
            'rombel_id' => $rombel->id,
        ], $extra));
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
