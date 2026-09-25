<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\SiswaMutasi;
use App\Models\TahunAjaran;
use App\Services\MutasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MutasiController extends Controller
{
    public function __construct(private MutasiService $mutasiService) {}

    public function index(Request $request): View
    {
        $tab = $request->string('tab')->toString();
        if (! array_key_exists($tab, SiswaMutasi::tabOptions())) {
            $tab = SiswaMutasi::JENIS_MASUK;
        }

        $tahunAjarans = TahunAjaran::query()->orderByDesc('tanggal_mulai')->get();
        $tahunAktif = TahunAjaran::aktif();
        $tahunAjaran = $this->resolveTahunAjaran($request, $tahunAktif);

        $mutasis = SiswaMutasi::query()
            ->with(['siswa.wali', 'siswa.rombels' => fn ($q) => $q->wherePivot('status', 'aktif'), 'rombel', 'tahunAjaran'])
            ->where('jenis', $tab)
            ->when($tahunAjaran, fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaran->id))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('mutasi.index', [
            'tab' => $tab,
            'mutasis' => $mutasis,
            'tabOptions' => SiswaMutasi::tabOptions(),
            'alasanOptions' => SiswaMutasi::alasanOptions(),
            'jenisSekolahOptions' => SiswaMutasi::jenisSekolahOptions(),
            'pekerjaanOptions' => config('emis.pekerjaan', []),
            'tingkatOptions' => ['VII', 'VIII', 'IX'],
            'cariSiswaUrl' => route('mutasi.siswa-cari'),
            'tahunAjarans' => $tahunAjarans,
            'tahunAjaran' => $tahunAjaran,
            'tahunAktif' => $tahunAktif,
        ]);
    }

    public function storeMasuk(Request $request): RedirectResponse
    {
        $data = $this->validateMasuk($request);
        $this->mutasiService->storeMasuk($data, $request->user());

        return redirect()
            ->route('mutasi.index', ['tab' => 'masuk'])
            ->with('status', 'Mutasi masuk berhasil ditambahkan.');
    }

    public function storeKeluar(Request $request): RedirectResponse
    {
        $data = $this->validateKeluar($request);
        $this->mutasiService->storeKeluar($data, $request->user());

        return redirect()
            ->route('mutasi.index', ['tab' => 'keluar'])
            ->with('status', 'Mutasi keluar berhasil ditambahkan.');
    }

    public function storeDo(Request $request): RedirectResponse
    {
        $data = $this->validateDo($request);
        $this->mutasiService->storeDo($data, $request->user());

        return redirect()
            ->route('mutasi.index', ['tab' => 'do'])
            ->with('status', 'Dropout berhasil ditambahkan.');
    }

    public function cariSiswa(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tingkat' => ['required', Rule::in(['VII', 'VIII', 'IX'])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $q = trim((string) ($data['q'] ?? ''));

        $siswas = Siswa::query()
            ->with(['wali', 'rombels' => fn ($query) => $query->wherePivot('status', 'aktif')])
            ->where('angkatan', $data['tingkat'])
            ->whereIn('status_keaktifan', ['aktif', 'aktif_tanpa_rombel'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('nama', 'like', '%'.$q.'%')
                        ->orWhere('nisn', 'like', '%'.$q.'%');
                });
            })
            ->orderBy('nama')
            ->limit(50)
            ->get()
            ->map(function (Siswa $siswa) {
                $rombel = $siswa->rombels->first();
                $rombelLabel = $rombel?->label() ?? '—';
                $nisn = $siswa->nisn ?: '—';

                return [
                    'id' => $siswa->id,
                    'nama' => $siswa->nama,
                    'nisn' => $siswa->nisn,
                    'rombel' => $rombelLabel,
                    'wali' => $siswa->wali?->nama ?: '—',
                    'label' => $nisn.' - '.$siswa->nama.' - '.$rombelLabel,
                ];
            });

        return response()->json(['data' => $siswas]);
    }

    public function batalkan(SiswaMutasi $mutasi): RedirectResponse
    {
        $tab = $mutasi->jenis;

        if ($mutasi->isMasuk()) {
            $this->mutasiService->batalkanMasuk($mutasi);
            $pesan = 'Mutasi masuk dibatalkan. Data siswa dihapus.';
        } else {
            $isDo = $mutasi->isDo();
            $this->mutasiService->batalkanNonaktif($mutasi);
            $pesan = $isDo
                ? 'Dropout dibatalkan. Siswa diaktifkan kembali.'
                : 'Mutasi keluar dibatalkan. Siswa diaktifkan kembali.';
        }

        return redirect()
            ->route('mutasi.index', ['tab' => $tab])
            ->with('status', $pesan);
    }

    public function cetak(SiswaMutasi $mutasi): RedirectResponse
    {
        return redirect()
            ->route('mutasi.index', ['tab' => $mutasi->jenis])
            ->with('error', 'Cetak surat belum tersedia. Template surat penerimaan/keluar akan menyusul.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateMasuk(Request $request): array
    {
        return $request->validate([
            'tanggal' => ['nullable', 'date'],
            'alasan' => ['required', 'string', Rule::in(array_keys(SiswaMutasi::alasanOptions()))],
            'jenis_sekolah' => ['required', Rule::in(array_keys(SiswaMutasi::jenisSekolahOptions()))],
            'nomor_dokumen_emis' => ['nullable', 'string', 'max:50'],
            'nama_sekolah' => ['required', 'string', 'max:150'],
            'nama' => ['required', 'string', 'max:150'],
            'nisn' => ['required', 'digits:10', Rule::unique('siswas', 'nisn')],
            'nik' => ['required', 'digits:16', Rule::unique('siswas', 'nik')],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'angkatan' => ['required', Rule::in(['VII', 'VIII', 'IX'])],
            'wali_dari' => ['required', Rule::in(['ayah', 'ibu', 'lainnya'])],
            'nama_ortu' => ['required', 'string', 'max:150'],
            'pekerjaan' => ['required', 'string', Rule::in(array_keys(config('emis.pekerjaan', [])))],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'blok' => ['nullable', 'string', 'max:100'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'desa' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kota' => ['nullable', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateKeluar(Request $request): array
    {
        return $request->validate([
            'siswa_id' => $this->siswaIdRules(),
            'tanggal' => ['nullable', 'date'],
            'alasan' => ['required', 'string', Rule::in(array_keys(SiswaMutasi::alasanOptions()))],
            'jenis_sekolah' => ['required', Rule::in(array_keys(SiswaMutasi::jenisSekolahOptions()))],
            'nomor_dokumen_emis' => [
                Rule::requiredIf($request->input('jenis_sekolah') === SiswaMutasi::SEKOLAH_MADRASAH),
                'nullable',
                'string',
                'max:50',
            ],
            'nama_sekolah' => ['required', 'string', 'max:150'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateDo(Request $request): array
    {
        return $request->validate([
            'siswa_id' => $this->siswaIdRules(),
            'tanggal' => ['nullable', 'date'],
            'alasan' => ['required', 'string', Rule::in(array_keys(SiswaMutasi::alasanOptions()))],
        ]);
    }

    /**
     * @return list<mixed>
     */
    private function siswaIdRules(): array
    {
        return [
            'required',
            'uuid',
            Rule::exists('siswas', 'id')->where(function ($query) {
                $query->whereIn('status_keaktifan', ['aktif', 'aktif_tanpa_rombel']);
            }),
        ];
    }

    private function resolveTahunAjaran(Request $request, ?TahunAjaran $tahunAktif): ?TahunAjaran
    {
        $tahunAjaranId = $request->integer('tahun_ajaran_id');

        if ($tahunAjaranId > 0) {
            return TahunAjaran::query()->find($tahunAjaranId) ?? $tahunAktif;
        }

        return $tahunAktif;
    }
}
