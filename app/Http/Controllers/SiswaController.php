<?php

namespace App\Http\Controllers;

use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SiswaController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $siswas = Siswa::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('nama', 'like', "%{$q}%")
                        ->orWhere('nisn', 'like', "%{$q}%")
                        ->orWhere('nik', 'like', "%{$q}%")
                        ->orWhere('nis', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('siswa.index', compact('siswas', 'q'));
    }

    public function create(): View
    {
        return view('siswa.create', [
            'emis' => config('emis'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateDataSiswa($request);

        $siswa = DB::transaction(function () use ($request, $data) {
            $siswa = Siswa::query()->create($this->siswaPayload($request, $data));

            foreach (['ayah', 'ibu', 'wali'] as $peran) {
                $siswa->orangTuas()->create([
                    'peran' => $peran,
                    'status' => $peran === 'wali' ? 'Sama dengan ayah kandung' : null,
                    'domisili' => 'Dalam Negeri',
                    'kewarganegaraan' => 'WNI',
                ]);
            }

            if ($tahun = TahunAjaran::aktif()) {
                $siswa->periodiks()->create($this->periodikIdentitasPayload($request, $tahun->id));
            }

            $this->simpanDokumen($request, $siswa, 'file_kk', 'kk');
            $this->simpanDokumen($request, $siswa, 'file_kip', 'kip');

            return $siswa;
        });

        return redirect()
            ->route('siswa.show', $siswa)
            ->with('status', 'Siswa berhasil dicatat. Lengkapi tab lain mengikuti EMIS 4.0.');
    }

    public function show(Siswa $siswa): View
    {
        $this->ensureRelasi($siswa);
        $siswa->load([
            'orangTuas', 'periodiks.tahunAjaran', 'rombels.tahunAjaran',
            'beasiswas', 'prestasis', 'rekamDidik', 'dokumens', 'ayah', 'ibu',
        ]);

        $tab = request('tab', 'data-siswa');
        $rombels = Rombel::query()
            ->with('tahunAjaran')
            ->when(TahunAjaran::aktif(), fn ($q) => $q->where('tahun_ajaran_id', TahunAjaran::aktif()->id))
            ->orderBy('tingkat')
            ->orderBy('nama')
            ->get();

        return view('siswa.show', [
            'siswa' => $siswa,
            'periodik' => $siswa->periodikAktif(),
            'emis' => config('emis'),
            'tab' => $tab,
            'rombels' => $rombels,
        ]);
    }

    public function update(Request $request, Siswa $siswa): RedirectResponse
    {
        $this->ensureRelasi($siswa);

        $bagian = (string) $request->input('bagian', 'data-siswa');

        return match ($bagian) {
            'orang-tua' => $this->updateOrangTua($request, $siswa),
            'alamat' => $this->updateAlamat($request, $siswa),
            'aktivitas' => $this->updateAktivitas($request, $siswa),
            'kebutuhan-khusus' => $this->updateKebutuhanKhusus($request, $siswa),
            'beasiswa' => $this->storeBeasiswa($request, $siswa),
            'prestasi' => $this->storePrestasi($request, $siswa),
            'rekam-didik' => $this->updateRekamDidik($request, $siswa),
            default => $this->updateDataSiswa($request, $siswa),
        };
    }

    public function destroyRelasi(Request $request, Siswa $siswa): RedirectResponse
    {
        $jenis = (string) $request->input('jenis');
        $id = (int) $request->input('id');

        match ($jenis) {
            'beasiswa' => $siswa->beasiswas()->whereKey($id)->delete(),
            'prestasi' => $siswa->prestasis()->whereKey($id)->delete(),
            default => null,
        };

        $tab = $jenis === 'prestasi' ? 'prestasi' : 'beasiswa';

        return redirect()
            ->route('siswa.show', ['siswa' => $siswa, 'tab' => $tab])
            ->with('status', 'Data dihapus.');
    }

    private function updateDataSiswa(Request $request, Siswa $siswa): RedirectResponse
    {
        $data = $this->validateDataSiswa($request, $siswa);

        $siswa->update($this->siswaPayload($request, $data, $siswa));

        if ($tahun = TahunAjaran::aktif()) {
            $siswa->periodiks()->updateOrCreate(
                ['tahun_ajaran_id' => $tahun->id],
                $this->periodikIdentitasPayload($request, $tahun->id),
            );
        }

        $this->simpanDokumen($request, $siswa, 'file_kk', 'kk');
        $this->simpanDokumen($request, $siswa, 'file_kip', 'kip');

        return redirect()
            ->route('siswa.show', ['siswa' => $siswa, 'tab' => 'data-siswa'])
            ->with('status', 'Data siswa disimpan.');
    }

    private function updateOrangTua(Request $request, Siswa $siswa): RedirectResponse
    {
        $request->validate([
            'ortu' => ['required', 'array'],
            'ortu.ayah.nama' => ['nullable', 'string', 'max:255'],
            'ortu.ibu.nama' => ['nullable', 'string', 'max:255'],
            'ortu.wali.nama' => ['nullable', 'string', 'max:255'],
            'file_kk_ayah' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'file_kk_ibu' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'file_kk_wali' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'file_kks' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'file_pkh' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        foreach (['ayah', 'ibu', 'wali'] as $peran) {
            $input = $request->input("ortu.$peran", []);
            $siswa->orangTuas()->updateOrCreate(
                ['peran' => $peran],
                $this->ortuPayload($input, $peran),
            );
        }

        if ($tahun = TahunAjaran::aktif()) {
            $siswa->periodiks()->updateOrCreate(
                ['tahun_ajaran_id' => $tahun->id],
                [
                    'no_kks' => $request->input('no_kks'),
                    'no_pkh' => $request->input('no_pkh'),
                ],
            );
        }

        $this->simpanDokumen($request, $siswa, 'file_kk_ayah', 'kk_ayah');
        $this->simpanDokumen($request, $siswa, 'file_kk_ibu', 'kk_ibu');
        $this->simpanDokumen($request, $siswa, 'file_kk_wali', 'kk_wali');
        $this->simpanDokumen($request, $siswa, 'file_kks', 'kks');
        $this->simpanDokumen($request, $siswa, 'file_pkh', 'pkh');

        return redirect()
            ->route('siswa.show', ['siswa' => $siswa, 'tab' => 'orang-tua'])
            ->with('status', 'Data orang tua disimpan.');
    }

    private function updateAlamat(Request $request, Siswa $siswa): RedirectResponse
    {
        $request->validate([
            'tempat_tinggal' => ['nullable', 'string', 'max:80'],
            'provinsi' => ['nullable', 'string', 'max:80'],
            'kota' => ['nullable', 'string', 'max:80'],
            'kecamatan' => ['nullable', 'string', 'max:80'],
            'desa' => ['nullable', 'string', 'max:80'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
            'koordinat' => ['nullable', 'string', 'max:80'],
            'jarak' => ['nullable', 'string', 'max:40'],
            'waktu_tempuh' => ['nullable', 'string', 'max:40'],
            'transportasi' => ['nullable', 'string', 'max:80'],
        ]);

        if ($tahun = TahunAjaran::aktif()) {
            $siswa->periodiks()->updateOrCreate(
                ['tahun_ajaran_id' => $tahun->id],
                $request->only([
                    'tempat_tinggal', 'provinsi', 'kota', 'kecamatan', 'desa',
                    'rt', 'rw', 'alamat', 'kode_pos', 'koordinat', 'jarak',
                    'waktu_tempuh', 'transportasi',
                ]),
            );
        }

        return redirect()
            ->route('siswa.show', ['siswa' => $siswa, 'tab' => 'alamat'])
            ->with('status', 'Data alamat disimpan.');
    }

    private function updateAktivitas(Request $request, Siswa $siswa): RedirectResponse
    {
        $request->validate([
            'tanggal_masuk' => ['nullable', 'date'],
            'alasan_masuk' => ['nullable', 'string', 'max:50'],
            'npsn_asal' => ['nullable', 'string', 'max:16'],
            'nama_sekolah_asal' => ['nullable', 'string', 'max:255'],
            'rombel_id' => ['nullable', 'exists:rombels,id'],
        ]);

        if ($tahun = TahunAjaran::aktif()) {
            $siswa->periodiks()->updateOrCreate(
                ['tahun_ajaran_id' => $tahun->id],
                $request->only(['tanggal_masuk', 'alasan_masuk', 'npsn_asal', 'nama_sekolah_asal']),
            );
        }

        if ($request->filled('rombel_id')) {
            $siswa->rombels()->wherePivot('status', 'aktif')->update(['status' => 'nonaktif']);
            $siswa->rombels()->syncWithoutDetaching([
                $request->integer('rombel_id') => ['status' => 'aktif'],
            ]);
            $siswa->update(['status_keaktifan' => 'aktif']);
        }

        return redirect()
            ->route('siswa.show', ['siswa' => $siswa, 'tab' => 'aktivitas'])
            ->with('status', 'Aktivitas belajar disimpan.');
    }

    private function updateKebutuhanKhusus(Request $request, Siswa $siswa): RedirectResponse
    {
        $request->validate([
            'kebutuhan_khusus' => ['nullable', 'string', 'max:80'],
            'kebutuhan_khusus_lainnya' => ['nullable', 'string', 'max:255'],
            'disabilitas' => ['nullable', 'string', 'max:80'],
            'disabilitas_lainnya' => ['nullable', 'string', 'max:255'],
        ]);

        if ($tahun = TahunAjaran::aktif()) {
            $siswa->periodiks()->updateOrCreate(
                ['tahun_ajaran_id' => $tahun->id],
                [
                    'kebutuhan_khusus' => $request->filled('kebutuhan_khusus') ? [$request->input('kebutuhan_khusus')] : null,
                    'kebutuhan_khusus_lainnya' => $request->input('kebutuhan_khusus_lainnya'),
                    'disabilitas' => $request->filled('disabilitas') ? [$request->input('disabilitas')] : null,
                    'disabilitas_lainnya' => $request->input('disabilitas_lainnya'),
                ],
            );
        }

        return redirect()
            ->route('siswa.show', ['siswa' => $siswa, 'tab' => 'kebutuhan-khusus'])
            ->with('status', 'Kebutuhan khusus disimpan.');
    }

    private function storeBeasiswa(Request $request, Siswa $siswa): RedirectResponse
    {
        $data = $request->validate([
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'kategori' => ['required', 'string', 'max:80'],
            'nama' => ['required', 'string', 'max:255'],
            'instansi' => ['nullable', 'string', 'max:255'],
            'jenis_instansi' => ['nullable', 'string', 'max:80'],
            'jangka_bulan' => ['nullable', 'integer', 'min:1', 'max:120'],
            'nominal' => ['nullable', 'integer', 'min:0'],
        ]);

        $siswa->beasiswas()->create($data);

        return redirect()
            ->route('siswa.show', ['siswa' => $siswa, 'tab' => 'beasiswa'])
            ->with('status', 'Beasiswa / bantuan ditambahkan.');
    }

    private function storePrestasi(Request $request, Siswa $siswa): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['nullable', 'string', 'max:50'],
            'tingkat' => ['nullable', 'string', 'max:50'],
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'penyelenggara' => ['nullable', 'string', 'max:255'],
            'sertifikat' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        unset($data['sertifikat']);
        $prestasi = $siswa->prestasis()->create($data);

        if ($request->hasFile('sertifikat')) {
            $prestasi->update([
                'sertifikat_path' => $request->file('sertifikat')->store("dokumen/{$siswa->id}/prestasi", 'public'),
            ]);
        }

        return redirect()
            ->route('siswa.show', ['siswa' => $siswa, 'tab' => 'prestasi'])
            ->with('status', 'Prestasi ditambahkan.');
    }

    private function updateRekamDidik(Request $request, Siswa $siswa): RedirectResponse
    {
        $data = $request->validate([
            'nik_kk' => ['nullable', 'digits:16'],
            'nama_kk' => ['nullable', 'string', 'max:255'],
            'tempat_lahir_kk' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir_kk' => ['nullable', 'date'],
            'jenis_kelamin_kk' => ['nullable', 'in:L,P'],
            'nama_ibu_kk' => ['nullable', 'string', 'max:255'],
            'nama_ayah_kk' => ['nullable', 'string', 'max:255'],
            'nama_ijazah' => ['nullable', 'string', 'max:255'],
            'tempat_lahir_ijazah' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir_ijazah' => ['nullable', 'date'],
            'jenis_kelamin_ijazah' => ['nullable', 'in:L,P'],
            'nama_ayah_ijazah' => ['nullable', 'string', 'max:255'],
            'nama_sd' => ['nullable', 'string', 'max:255'],
            'tahun_ajaran_kelulusan' => ['nullable', 'string', 'max:20'],
            'nip_kepala_sekolah' => ['nullable', 'string', 'max:30'],
            'nama_kepala_sekolah' => ['nullable', 'string', 'max:255'],
            'nomor_seri_ijazah' => ['nullable', 'string', 'max:50'],
            'tanggal_terbit_ijazah' => ['nullable', 'date'],
            'status_verval' => ['nullable', 'in:belum,sudah'],
            'file_ijazah' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        unset($data['file_ijazah']);
        $data['status_verval'] = $data['status_verval'] ?? 'belum';

        $siswa->rekamDidik()->updateOrCreate(
            ['siswa_id' => $siswa->id],
            $data,
        );

        $this->simpanDokumen($request, $siswa, 'file_ijazah', 'ijazah_sd');

        return redirect()
            ->route('siswa.show', ['siswa' => $siswa, 'tab' => 'rekam-didik'])
            ->with('status', 'Rekam didik disimpan.');
    }

    private function validateDataSiswa(Request $request, ?Siswa $siswa = null): array
    {
        $punyaNisn = ! $request->boolean('tidak_punya_nisn');
        $punyaNik = ! $request->boolean('tidak_punya_nik');
        $tidakPunyaHp = $request->boolean('tidak_punya_hp');
        $wna = $request->input('kewarganegaraan') === 'WNA';

        return $request->validate([
            'nama' => ['required', 'string', 'max:255', 'regex:/^[A-Za-zÀ-ÿ\-\'’`., ]+$/u'],
            'nis' => ['nullable', 'string', 'max:20'],
            'tidak_punya_nisn' => ['sometimes', 'boolean'],
            'nisn' => [
                Rule::requiredIf($punyaNisn),
                'nullable',
                'digits:10',
                Rule::unique('siswas', 'nisn')->ignore($siswa?->id),
            ],
            'tidak_punya_nik' => ['sometimes', 'boolean'],
            'nik' => [
                Rule::requiredIf($punyaNik && ! $wna),
                'nullable',
                'digits:16',
                Rule::unique('siswas', 'nik')->ignore($siswa?->id),
            ],
            'kewarganegaraan' => ['required', 'in:WNI,WNA'],
            'kitas' => [Rule::requiredIf($wna), 'nullable', 'string', 'max:30'],
            'negara_asal' => [Rule::requiredIf($wna), 'nullable', 'string', 'max:80'],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'jumlah_saudara' => ['required', 'integer', 'min:0', 'max:20'],
            'anak_ke' => ['required', 'integer', 'min:1', 'max:21'],
            'agama' => ['required', 'string', 'max:30'],
            'cita_cita' => ['required', 'string', 'max:80'],
            'cita_cita_lainnya' => [Rule::requiredIf($request->input('cita_cita') === 'Lainnya'), 'nullable', 'string', 'max:80'],
            'hobi' => ['nullable', 'string', 'max:80'],
            'tidak_punya_hp' => ['sometimes', 'boolean'],
            'no_hp' => [
                Rule::requiredIf(! $tidakPunyaHp),
                'nullable',
                'regex:/^62[0-9]{8,15}$/',
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'pembiaya' => ['required', 'string', 'max:80'],
            'no_kk' => ['nullable', 'digits:16'],
            'kepala_keluarga' => ['nullable', 'string', 'max:255'],
            'no_kip' => ['nullable', 'string', 'max:30'],
            'pernah_tk_ra' => ['sometimes', 'boolean'],
            'pernah_paud' => ['sometimes', 'boolean'],
            'imunisasi' => ['nullable', 'array'],
            'imunisasi.*' => ['string'],
            'file_kk' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'file_kip' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ], [
            'nama.regex' => 'Nama lengkap hanya dapat diisi huruf dan simbol -\'.,',
            'no_hp.regex' => 'Nomor handphone tidak bisa diawali 0, harus kode negara, misal: 62',
            'nisn.required' => 'NISN tidak boleh kosong',
            'nik.required' => 'NIK tidak boleh kosong',
            'kewarganegaraan.required' => 'Kewarganegaraan tidak boleh kosong',
            'jumlah_saudara.required' => 'Jumlah saudara tidak boleh kosong',
            'anak_ke.required' => 'Anak ke tidak boleh kosong',
            'anak_ke.min' => 'Anak ke tidak boleh NOL',
            'pembiaya.required' => 'Yang membiayai sekolah tidak boleh kosong',
            'no_hp.required' => 'Nomor handphone tidak boleh kosong',
            'email.email' => 'Email harus valid',
        ]);
    }

    private function siswaPayload(Request $request, array $data, ?Siswa $siswa = null): array
    {
        $punyaNisn = ! $request->boolean('tidak_punya_nisn');
        $punyaNik = ! $request->boolean('tidak_punya_nik');
        $tidakPunyaHp = $request->boolean('tidak_punya_hp');

        $payload = [
            'nama' => $data['nama'],
            'nis' => $data['nis'] ?? null,
            'punya_nisn' => $punyaNisn,
            'nisn' => $punyaNisn ? ($data['nisn'] ?? null) : null,
            'punya_nik' => $punyaNik,
            'nik' => $punyaNik ? ($data['nik'] ?? null) : null,
            'kewarganegaraan' => $data['kewarganegaraan'],
            'kitas' => $data['kewarganegaraan'] === 'WNA' ? ($data['kitas'] ?? null) : null,
            'negara_asal' => $data['kewarganegaraan'] === 'WNA' ? ($data['negara_asal'] ?? null) : null,
            'tempat_lahir' => $data['tempat_lahir'],
            'tanggal_lahir' => $data['tanggal_lahir'],
            'jenis_kelamin' => $data['jenis_kelamin'],
            'jumlah_saudara' => $data['jumlah_saudara'],
            'anak_ke' => $data['anak_ke'],
            'agama' => $data['agama'],
            'cita_cita' => $data['cita_cita'],
            'cita_cita_lainnya' => $data['cita_cita'] === 'Lainnya' ? ($data['cita_cita_lainnya'] ?? null) : null,
            'hobi' => $data['hobi'] ?? null,
            'tidak_punya_hp' => $tidakPunyaHp,
            'no_hp' => $tidakPunyaHp ? null : ($data['no_hp'] ?? null),
            'email' => $data['email'] ?? null,
        ];

        if (! $siswa) {
            $payload['status_keaktifan'] = 'aktif_tanpa_rombel';
        }

        if ($request->filled('jumlah_saudara') && $request->filled('anak_ke')) {
            if ((int) $data['anak_ke'] > ((int) $data['jumlah_saudara'] + 1)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'anak_ke' => 'Anak ke tidak bisa lebih dari jumlah saudara + 1',
                ]);
            }
        }

        return $payload;
    }

    private function periodikIdentitasPayload(Request $request, int $tahunId): array
    {
        return [
            'tahun_ajaran_id' => $tahunId,
            'pembiaya' => $request->input('pembiaya'),
            'no_kk' => $request->input('no_kk'),
            'kepala_keluarga' => $request->input('kepala_keluarga'),
            'no_kip' => $request->input('no_kip'),
            'pernah_tk_ra' => $request->boolean('pernah_tk_ra'),
            'pernah_paud' => $request->boolean('pernah_paud'),
            'imunisasi' => array_values($request->input('imunisasi', [])),
        ];
    }

    private function ortuPayload(array $input, string $peran): array
    {
        $tidakPunyaHp = (bool) ($input['tidak_punya_hp'] ?? false);

        return [
            'nama' => $input['nama'] ?? null,
            'status_hidup' => $input['status_hidup'] ?? null,
            'status' => $peran === 'wali' ? ($input['status'] ?? null) : ($input['status_hidup'] ?? null),
            'nik' => $input['nik'] ?? null,
            'kewarganegaraan' => $input['kewarganegaraan'] ?? 'WNI',
            'kitas' => $input['kitas'] ?? null,
            'negara_asal' => $input['negara_asal'] ?? null,
            'tempat_lahir' => $input['tempat_lahir'] ?? null,
            'tanggal_lahir' => $input['tanggal_lahir'] ?: null,
            'pendidikan' => $input['pendidikan'] ?? null,
            'pekerjaan' => $input['pekerjaan'] ?? null,
            'penghasilan' => $input['penghasilan'] ?? null,
            'tidak_punya_hp' => $tidakPunyaHp,
            'no_hp' => $tidakPunyaHp ? null : ($input['no_hp'] ?? null),
            'domisili' => $input['domisili'] ?? 'Dalam Negeri',
            'status_tempat_tinggal' => $input['status_tempat_tinggal'] ?? null,
            'provinsi' => $input['provinsi'] ?? null,
            'kota' => $input['kota'] ?? null,
            'kecamatan' => $input['kecamatan'] ?? null,
            'desa' => $input['desa'] ?? null,
            'rt' => $input['rt'] ?? null,
            'rw' => $input['rw'] ?? null,
            'alamat' => $input['alamat'] ?? null,
            'kode_pos' => $input['kode_pos'] ?? null,
            'sama_dengan_ayah' => $peran === 'ibu' && ! empty($input['sama_dengan_ayah']),
        ];
    }

    private function simpanDokumen(Request $request, Siswa $siswa, string $field, string $jenis): void
    {
        if (! $request->hasFile($field)) {
            return;
        }

        $file = $request->file($field);
        $path = $file->store("dokumen/{$siswa->id}", 'public');

        $siswa->dokumens()->updateOrCreate(
            ['jenis' => $jenis],
            ['path' => $path, 'nama_asli' => $file->getClientOriginalName()],
        );
    }

    private function ensureRelasi(Siswa $siswa): void
    {
        foreach (['ayah', 'ibu', 'wali'] as $peran) {
            $siswa->orangTuas()->firstOrCreate(
                ['peran' => $peran],
                [
                    'status' => $peran === 'wali' ? 'Sama dengan ayah kandung' : null,
                    'domisili' => 'Dalam Negeri',
                    'kewarganegaraan' => 'WNI',
                ],
            );
        }

        if ($tahun = TahunAjaran::aktif()) {
            $siswa->periodiks()->firstOrCreate(['tahun_ajaran_id' => $tahun->id]);
        }
    }
}
