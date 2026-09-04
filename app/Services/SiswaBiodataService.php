<?php

namespace App\Services;

use App\Models\PengajuanPerubahanSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Support\Wilayah;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SiswaBiodataService
{
    private const NAMA_ORANG = '/^[A-Za-zÀ-ÿ\-\'’`., ]+$/u';

    public function create(Request $request): Siswa
    {
        $data = $this->validateDataSiswa($request);

        return DB::transaction(function () use ($request, $data) {
            $siswa = Siswa::query()->create($this->siswaPayload($request, $data));

            foreach (['ayah', 'ibu', 'wali'] as $peran) {
                $siswa->orangTuas()->create([
                    'peran' => $peran,
                ]);
            }

            if ($tahun = TahunAjaran::aktif()) {
                $siswa->periodiks()->create($this->periodikIdentitasPayload($request, $tahun->id));
            }

            $this->simpanDokumen($request, $siswa, 'file_kk', 'kk');
            $this->simpanDokumen($request, $siswa, 'file_akta', 'akta_lahir');
            $this->simpanDokumen($request, $siswa, 'file_kip', 'kip');

            return $siswa;
        });
    }

    public function updateBagian(Request $request, Siswa $siswa, string $bagian, bool $kunciIdentitas = false): string
    {
        $this->ensureRelasi($siswa);

        return match ($bagian) {
            'orang-tua' => $this->updateOrangTua($request, $siswa),
            'alamat' => $this->updateAlamat($request, $siswa),
            'aktivitas' => $this->updateAktivitas($request, $siswa),
            'beasiswa' => $this->storeBeasiswa($request, $siswa),
            'prestasi' => $this->storePrestasi($request, $siswa),
            'rekam-didik' => $this->updateRekamDidik($request, $siswa),
            default => $this->updateDataSiswa($request, $siswa, $kunciIdentitas),
        };
    }

    public function hapusRelasi(Siswa $siswa, string $jenis, int $id): string
    {
        if ($jenis === 'beasiswa') {
            $item = $siswa->beasiswas()->whereKey($id)->first();
            if ($item?->bukti_path) {
                Storage::disk('r2')->delete($item->bukti_path);
            }
            $item?->delete();
        } elseif ($jenis === 'prestasi') {
            $item = $siswa->prestasis()->whereKey($id)->first();
            if ($item?->sertifikat_path) {
                Storage::disk('r2')->delete($item->sertifikat_path);
            }
            $item?->delete();
        }

        return $jenis === 'prestasi' ? 'prestasi' : 'beasiswa';
    }

    public function updateDataSiswa(Request $request, Siswa $siswa, bool $kunciIdentitas = false): string
    {
        if ($kunciIdentitas) {
            $request->merge([
                'nama' => $siswa->nama,
                'nis' => $siswa->nis,
                'nisn' => $siswa->nisn,
                'nik' => $siswa->nik,
                'tempat_lahir' => $siswa->tempat_lahir,
                'tanggal_lahir' => $siswa->tanggal_lahir?->toDateString(),
                'jenis_kelamin' => $siswa->jenis_kelamin,
            ]);
        }
        $data = $this->validateDataSiswa($request, $siswa);

        $siswa->update($this->siswaPayload($request, $data, $siswa));

        if ($tahun = TahunAjaran::aktif()) {
            $siswa->periodiks()->updateOrCreate(
                ['tahun_ajaran_id' => $tahun->id],
                $this->periodikIdentitasPayload($request, $tahun->id),
            );
        }

        $this->simpanDokumen($request, $siswa, 'file_kk', 'kk');
        $this->simpanDokumen($request, $siswa, 'file_akta', 'akta_lahir');
        $this->simpanDokumen($request, $siswa, 'file_kip', 'kip');
        $this->simpanFoto($request, $siswa);

        return 'Data siswa disimpan.';
    }

    public function ajukanPerubahan(Request $request, Siswa $siswa): string
    {
        $siswa->unsetRelation('periodiks');
        $siswa->unsetRelation('dokumens');
        $siswa->refresh();

        $tabDataSiswa = collect($siswa->kelengkapan()['tab'])->firstWhere('id', 'data-siswa');

        if (! ($tabDataSiswa['selesai'] ?? false)) {
            throw ValidationException::withMessages([
                'kelengkapan' => 'Lengkapi semua data dan dokumen terlebih dahulu.',
            ]);
        }

        $data = $request->validate([
            'field' => ['required', 'string', Rule::in(array_keys(PengajuanPerubahanSiswa::FIELDS))],
            'nilai_baru' => ['required', 'string', 'max:255'],
            'alasan' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $this->assertNilaiPerubahan($siswa, $data['field'], $data['nilai_baru']);

        if ($siswa->pengajuanPerubahans()->where('field', $data['field'])->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                'field' => 'Pengajuan untuk data ini masih menunggu konfirmasi madrasah.',
            ]);
        }

        $siswa->pengajuanPerubahans()->create([
            'field' => $data['field'],
            'nilai_lama' => $this->nilaiIdentitas($siswa, $data['field']),
            'nilai_baru' => $data['nilai_baru'],
            'alasan' => $data['alasan'],
            'status' => 'pending',
        ]);

        return 'Pengajuan perubahan dikirim. Menunggu konfirmasi madrasah.';
    }

    public function prosesPengajuan(Siswa $siswa, PengajuanPerubahanSiswa $pengajuan, string $aksi): string
    {
        if ($pengajuan->siswa_id !== $siswa->id) {
            abort(404);
        }

        if ($pengajuan->status !== 'pending') {
            throw ValidationException::withMessages([
                'pengajuan' => 'Pengajuan ini sudah diproses.',
            ]);
        }

        if ($aksi === 'tolak') {
            $pengajuan->update(['status' => 'ditolak']);

            return 'Pengajuan perubahan ditolak.';
        }

        $this->assertNilaiPerubahan($siswa, $pengajuan->field, $pengajuan->nilai_baru);
        $siswa->update([$pengajuan->field => $pengajuan->nilai_baru]);
        $pengajuan->update(['status' => 'diterima']);

        return 'Pengajuan perubahan diterima.';
    }

    public function updateOrangTua(Request $request, Siswa $siswa): string
    {
        $this->validateOrangTua($request, $siswa);

        $ayah = $this->ortuPayload($request->input('ortu.ayah', []), 'ayah');
        $ibu = $this->ortuPayload($request->input('ortu.ibu', []), 'ibu');
        $wali = $this->ortuPayload($request->input('ortu.wali', []), 'wali');

        $waliStatus = $wali['status'] ?? null;
        if ($waliStatus === 'Isi sendiri') {
            $waliStatus = 'Lainnya';
        }

        $ayahMeninggal = ($ayah['status_hidup'] ?? null) === 'meninggal';
        $ibuMeninggal = ($ibu['status_hidup'] ?? null) === 'meninggal';

        if ($waliStatus === 'Sama dengan ayah kandung' && ! $ayahMeninggal) {
            $wali = array_merge($wali, Arr::except($ayah, ['status', 'hubungan']));
            $wali['status'] = $waliStatus;
            $wali['hubungan'] = 'Ayah kandung';
        } elseif ($waliStatus === 'Sama dengan ibu kandung' && ! $ibuMeninggal) {
            $wali = array_merge($wali, Arr::except($ibu, ['status', 'hubungan']));
            $wali['status'] = $waliStatus;
            $wali['hubungan'] = 'Ibu kandung';
        } else {
            $wali['status'] = $waliStatus ?: null;
        }

        foreach (['ayah' => $ayah, 'ibu' => $ibu, 'wali' => $wali] as $peran => $payload) {
            $siswa->orangTuas()->updateOrCreate(
                ['peran' => $peran],
                $payload,
            );
        }

        $tidakPunyaKks = $request->boolean('tidak_punya_kks');
        $tidakPunyaPkh = $request->boolean('tidak_punya_pkh');

        if ($tahun = TahunAjaran::aktif()) {
            $siswa->periodiks()->updateOrCreate(
                ['tahun_ajaran_id' => $tahun->id],
                [
                    'penghasilan_gabungan' => $request->input('penghasilan_gabungan'),
                    'tidak_punya_kks' => $tidakPunyaKks,
                    'tidak_punya_pkh' => $tidakPunyaPkh,
                    'no_kks' => $tidakPunyaKks ? null : $request->input('no_kks'),
                    'no_pkh' => $tidakPunyaPkh ? null : $request->input('no_pkh'),
                ],
            );
        }

        $this->simpanDokumen($request, $siswa, 'file_kks', 'kks');
        $this->simpanDokumen($request, $siswa, 'file_pkh', 'pkh');

        return 'Data orang tua disimpan.';
    }

    public function updateAlamat(Request $request, Siswa $siswa): string
    {
        $this->siapkanNomorAlamat($request);

        $request->validate([
            'tempat_tinggal' => ['nullable', 'string', 'max:80'],
            'provinsi' => ['nullable', 'string', 'max:80'],
            'kota' => ['nullable', 'string', 'max:80'],
            'kecamatan' => ['nullable', 'string', 'max:80'],
            'desa' => ['nullable', 'string', 'max:80'],
            'blok' => ['nullable', 'string', 'max:80'],
            'rt' => ['nullable', 'digits:3'],
            'rw' => ['nullable', 'digits:3'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'kode_pos' => ['nullable', 'digits:5'],
            'koordinat' => ['nullable', 'string', 'max:80'],
            'jarak' => ['nullable', 'string', 'max:40'],
            'waktu_tempuh' => ['nullable', 'string', 'max:40'],
            'transportasi' => ['nullable', 'string', 'max:80'],
            'ortu.ayah.rt' => ['nullable', 'digits:3'],
            'ortu.ayah.rw' => ['nullable', 'digits:3'],
            'ortu.ayah.kode_pos' => ['nullable', 'digits:5'],
            'ortu.ibu.rt' => ['nullable', 'digits:3'],
            'ortu.ibu.rw' => ['nullable', 'digits:3'],
            'ortu.ibu.kode_pos' => ['nullable', 'digits:5'],
            'ortu.wali.rt' => ['nullable', 'digits:3'],
            'ortu.wali.rw' => ['nullable', 'digits:3'],
            'ortu.wali.kode_pos' => ['nullable', 'digits:5'],
        ], [
            'rt.digits' => 'RT harus 3 digit',
            'rw.digits' => 'RW harus 3 digit',
            'kode_pos.digits' => 'Kode pos harus 5 digit',
            'ortu.ayah.rt.digits' => 'RT ayah harus 3 digit',
            'ortu.ayah.rw.digits' => 'RW ayah harus 3 digit',
            'ortu.ayah.kode_pos.digits' => 'Kode pos ayah harus 5 digit',
            'ortu.ibu.rt.digits' => 'RT ibu harus 3 digit',
            'ortu.ibu.rw.digits' => 'RW ibu harus 3 digit',
            'ortu.ibu.kode_pos.digits' => 'Kode pos ibu harus 5 digit',
            'ortu.wali.rt.digits' => 'RT wali harus 3 digit',
            'ortu.wali.rw.digits' => 'RW wali harus 3 digit',
            'ortu.wali.kode_pos.digits' => 'Kode pos wali harus 5 digit',
        ]);

        $siswa->loadMissing('orangTuas');

        $ayahMeninggal = $this->sudahMeninggal($siswa, 'ayah');
        $ibuMeninggal = $this->sudahMeninggal($siswa, 'ibu');
        $waliMeninggal = $this->sudahMeninggal($siswa, 'wali');

        $ayah = $this->alamatOrtuPayload($request->input('ortu.ayah', []));
        $ibu = $this->alamatOrtuPayload($request->input('ortu.ibu', []));
        $wali = $this->alamatOrtuPayload($request->input('ortu.wali', []));
        $ibu['sama_dengan_ayah'] = ! $ayahMeninggal && $request->boolean('ortu.ibu.sama_dengan_ayah');

        if ($ibu['sama_dengan_ayah'] && ! $ayahMeninggal) {
            $ibu = array_merge($ibu, Arr::only($ayah, $this->alamatOrtuKeys()));
        }

        $waliStatus = $siswa->orangTuas->firstWhere('peran', 'wali')?->status;
        if ($waliStatus === 'Isi sendiri') {
            $waliStatus = 'Lainnya';
        }

        if ($waliStatus === 'Sama dengan ayah kandung' && ! $ayahMeninggal) {
            $wali = array_merge($wali, Arr::only($ayah, $this->alamatOrtuKeys()));
        } elseif ($waliStatus === 'Sama dengan ibu kandung' && ! $ibuMeninggal) {
            $wali = array_merge($wali, Arr::only($ibu, $this->alamatOrtuKeys()));
        }

        if (! $ayahMeninggal) {
            $siswa->orangTuas()->updateOrCreate(['peran' => 'ayah'], $ayah);
        }

        if (! $ibuMeninggal) {
            $siswa->orangTuas()->updateOrCreate(['peran' => 'ibu'], $ibu);
        } else {
            $siswa->orangTuas()->where('peran', 'ibu')->update(['sama_dengan_ayah' => false]);
        }

        if (! $waliMeninggal) {
            $siswa->orangTuas()->updateOrCreate(['peran' => 'wali'], $wali);
        }

        $alamat = $this->resolveAlamatSiswa($request, $siswa->fresh(['orangTuas']));

        if ($tahun = TahunAjaran::aktif()) {
            $siswa->periodiks()->updateOrCreate(
                ['tahun_ajaran_id' => $tahun->id],
                $alamat,
            );
        }

        return 'Data alamat disimpan.';
    }

    public function updateAktivitas(Request $request, Siswa $siswa): string
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
            DB::table('rombel_siswas')
                ->where('siswa_id', $siswa->id)
                ->where('status', 'aktif')
                ->update(['status' => 'nonaktif']);
            $siswa->rombels()->syncWithoutDetaching([
                $request->integer('rombel_id') => ['status' => 'aktif'],
            ]);
            $siswa->update(['status_keaktifan' => 'aktif']);
        }

        return 'Aktivitas belajar disimpan.';
    }

    public function storeBeasiswa(Request $request, Siswa $siswa): string
    {
        $data = $request->validate([
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'kategori' => ['required', 'string', Rule::in(array_keys(config('emis.jenis_beasiswa')))],
            'nomor_rekening' => ['nullable', 'regex:/^[0-9]+$/', 'max:50'],
            'nominal' => ['nullable', 'integer', 'min:0'],
            'bukti' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:1024'],
        ], [
            'nomor_rekening.regex' => 'Nomor rekening hanya boleh angka',
        ]);

        unset($data['bukti']);
        $data['nama'] = $data['kategori'];
        $data['nomor_rekening'] = $data['nomor_rekening'] ?: null;

        $beasiswa = $siswa->beasiswas()->create($data);

        if ($request->hasFile('bukti')) {
            $beasiswa->update([
                'bukti_path' => $request->file('bukti')->store("dokumen/{$siswa->id}/bantuan", 'r2'),
            ]);
        }

        return 'Bantuan pendidikan ditambahkan.';
    }

    public function storePrestasi(Request $request, Siswa $siswa): string
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'string', Rule::in(array_keys(config('emis.jenis_prestasi')))],
            'tingkat' => ['nullable', 'string', Rule::in(array_keys(config('emis.tingkat_prestasi')))],
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'penyelenggara' => ['nullable', 'string', 'max:255'],
            'sertifikat' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:1024'],
        ]);

        unset($data['sertifikat']);
        $prestasi = $siswa->prestasis()->create($data);

        if ($request->hasFile('sertifikat')) {
            $prestasi->update([
                'sertifikat_path' => $request->file('sertifikat')->store("dokumen/{$siswa->id}/prestasi", 'r2'),
            ]);
        }

        return 'Prestasi ditambahkan.';
    }

    public function updateRekamDidik(Request $request, Siswa $siswa): string
    {
        $siswa->loadMissing('dokumens');

        $request->merge([
            'npsn' => $this->hanyaDigit($request->input('npsn')),
            'nip_kepala_sekolah' => $this->hanyaDigit($request->input('nip_kepala_sekolah')),
        ]);

        $data = $request->validate([
            'nama_sd' => ['required', 'string', 'max:255'],
            'npsn' => ['required', 'digits:8'],
            'tahun_ajaran_kelulusan' => ['required', 'string', 'max:20'],
            'nip_kepala_sekolah' => ['required', 'digits:18'],
            'nama_kepala_sekolah' => ['required', 'string', 'max:255', 'regex:'.self::NAMA_ORANG],
            'nomor_seri_ijazah' => ['required', 'string', 'max:50'],
            'tanggal_terbit_ijazah' => ['required', 'date'],
            'ijazah_sesuai' => ['nullable', 'array'],
            'ijazah_sesuai.*' => ['in:nama,nisn,tempat_lahir,tanggal_lahir,jenis_kelamin,nama_ayah'],
            'file_ijazah' => [
                Rule::requiredIf(fn () => $siswa->dokumens()->where('jenis', 'ijazah_sd')->doesntExist()),
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:1024',
            ],
        ], [
            'nama_sd.required' => 'Nama sekolah wajib diisi',
            'npsn.required' => 'NPSN wajib diisi',
            'npsn.digits' => 'NPSN harus 8 digit',
            'tahun_ajaran_kelulusan.required' => 'Tahun ajaran lulusan wajib diisi',
            'nip_kepala_sekolah.required' => 'NIP kepala sekolah wajib diisi',
            'nip_kepala_sekolah.digits' => 'NIP kepala sekolah harus 18 digit',
            'nama_kepala_sekolah.required' => 'Nama kepala sekolah wajib diisi',
            'nama_kepala_sekolah.regex' => 'Nama kepala sekolah hanya dapat diisi huruf dan simbol -\'.,',
            'nomor_seri_ijazah.required' => 'Nomor seri ijazah wajib diisi',
            'tanggal_terbit_ijazah.required' => 'Tanggal terbit ijazah wajib diisi',
            'file_ijazah.required' => 'Unggah ijazah wajib dilampirkan',
        ]);

        $keys = ['nama', 'nisn', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'nama_ayah'];
        $fields = array_values(array_intersect($keys, $request->input('ijazah_sesuai', [])));
        $sesuai = count($fields) === count($keys);
        $siswa->loadMissing('ayah');

        $payload = [
            'nama_sd' => $data['nama_sd'] ?? null,
            'npsn' => $data['npsn'] ?? null,
            'tahun_ajaran_kelulusan' => $data['tahun_ajaran_kelulusan'] ?? null,
            'nip_kepala_sekolah' => $data['nip_kepala_sekolah'] ?? null,
            'nama_kepala_sekolah' => $data['nama_kepala_sekolah'] ?? null,
            'nomor_seri_ijazah' => $data['nomor_seri_ijazah'] ?? null,
            'tanggal_terbit_ijazah' => $data['tanggal_terbit_ijazah'] ?? null,
            'ijazah_sesuai' => $sesuai,
            'ijazah_sesuai_fields' => $fields,
            'status_verval' => $sesuai ? 'sudah' : 'belum',
        ];

        if ($sesuai) {
            $payload['nama_ijazah'] = $siswa->nama;
            $payload['tempat_lahir_ijazah'] = $siswa->tempat_lahir;
            $payload['tanggal_lahir_ijazah'] = $siswa->tanggal_lahir;
            $payload['jenis_kelamin_ijazah'] = $siswa->jenis_kelamin;
            $payload['nama_ayah_ijazah'] = $siswa->ayah?->nama
                ?: $siswa->rekamDidik?->nama_ayah_ijazah
                ?: $siswa->rekamDidik?->nama_ayah_kk;
        }

        $siswa->rekamDidik()->updateOrCreate(
            ['siswa_id' => $siswa->id],
            $payload,
        );

        $this->simpanDokumen($request, $siswa, 'file_ijazah', 'ijazah_sd');

        return 'Rekam didik disimpan.';
    }

    public function validateDataSiswa(Request $request, ?Siswa $siswa = null): array
    {
        $siswa?->load('dokumens');

        $tidakPunyaHp = $request->boolean('tidak_punya_hp');
        $tidakPunyaEmail = $request->boolean('tidak_punya_email');
        $tidakPunyaKip = $request->boolean('tidak_punya_kip');
        $noKip = $tidakPunyaKip ? null : $request->input('no_kip');

        $request->merge([
            'nis' => $this->hanyaDigit($request->input('nis')),
            'nisn' => $this->hanyaDigit($request->input('nisn')),
            'nik' => $this->hanyaDigit($request->input('nik')),
            'no_hp' => $this->normalisasiHp($request->input('no_hp')),
            'no_kk' => $this->hanyaDigit($request->input('no_kk')),
        ]);

        return $request->validate([
            'nama' => ['required', 'string', 'max:255', 'regex:'.self::NAMA_ORANG],
            'nis' => ['nullable', 'digits_between:1,20'],
            'nisn' => [
                'required',
                'digits:10',
                Rule::unique('siswas', 'nisn')->ignore($siswa?->id),
            ],
            'nik' => [
                'required',
                'digits:16',
                Rule::unique('siswas', 'nik')->ignore($siswa?->id),
            ],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'jumlah_saudara' => ['required', 'integer', 'min:0', 'max:20'],
            'anak_ke' => ['required', 'integer', 'min:1', 'max:21'],
            'agama' => ['required', 'string', 'max:30'],
            'cita_cita' => ['required', 'string', 'max:80'],
            'hobi' => ['required', 'string', 'max:80'],
            'tidak_punya_hp' => ['sometimes', 'boolean'],
            'tidak_punya_email' => ['sometimes', 'boolean'],
            'tidak_punya_kip' => ['sometimes', 'boolean'],
            'no_hp' => [
                Rule::requiredIf(! $tidakPunyaHp),
                'nullable',
                'regex:/^62[0-9]{8,15}$/',
            ],
            'email' => [
                Rule::requiredIf(! $tidakPunyaEmail),
                'nullable',
                'email',
                'max:255',
            ],
            'pembiaya' => ['required', 'string', 'max:80'],
            'no_kk' => ['required', 'digits:16'],
            'kepala_keluarga' => ['required', 'string', 'max:255', 'regex:'.self::NAMA_ORANG],
            'no_kip' => [
                Rule::requiredIf(! $tidakPunyaKip),
                'nullable',
                'string',
                'max:40',
            ],
            'kebutuhan_khusus' => ['required', 'string', Rule::in(array_keys(config('emis.kebutuhan_khusus')))],
            'kebutuhan_khusus_lainnya' => [
                Rule::requiredIf($request->input('kebutuhan_khusus') === 'Lainnya'),
                'nullable',
                'string',
                'max:255',
            ],
            'disabilitas' => ['nullable', 'array'],
            'disabilitas.*' => ['string', Rule::in(array_keys(config('emis.disabilitas')))],
            'disabilitas_lainnya' => [
                Rule::requiredIf(in_array('Lainnya', $request->input('disabilitas', []), true)),
                'nullable',
                'string',
                'max:255',
            ],
            'pernah_tk_ra' => ['sometimes', 'boolean'],
            'pernah_paud' => ['sometimes', 'boolean'],
            'imunisasi' => ['nullable', 'array'],
            'imunisasi.*' => ['string'],
            'file_kk' => [
                Rule::requiredIf($siswa === null || $siswa->dokumenJenis('kk') === null),
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:1024',
            ],
            'file_akta' => [
                Rule::requiredIf($siswa === null || $siswa->dokumenJenis('akta_lahir') === null),
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:1024',
            ],
            'file_kip' => [
                Rule::requiredIf(filled($noKip) && ($siswa === null || $siswa->dokumenJenis('kip') === null)),
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:1024',
            ],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:1024'],
        ], [
            'nama.regex' => 'Nama lengkap hanya dapat diisi huruf dan simbol -\'.,',
            'kepala_keluarga.regex' => 'Nama kepala keluarga hanya dapat diisi huruf dan simbol -\'.,',
            'no_hp.regex' => 'Nomor HP/Whatsapp harus diawali 62 diikuti 8 sampai 15 digit',
            'nis.digits_between' => 'NIS lokal hanya boleh angka',
            'nisn.required' => 'NISN tidak boleh kosong',
            'nisn.digits' => 'NISN harus 10 digit angka',
            'nik.required' => 'NIK tidak boleh kosong',
            'nik.digits' => 'NIK harus 16 digit angka',
            'no_kk.digits' => 'Nomor KK harus 16 digit angka',
            'jumlah_saudara.required' => 'Jumlah saudara tidak boleh kosong',
            'anak_ke.required' => 'Anak ke tidak boleh kosong',
            'anak_ke.min' => 'Anak ke tidak boleh NOL',
            'pembiaya.required' => 'Yang membiayai sekolah tidak boleh kosong',
            'no_hp.required' => 'Nomor HP/Whatsapp tidak boleh kosong',
            'email.required' => 'Email tidak boleh kosong',
            'email.email' => 'Email harus valid',
            'hobi.required' => 'Hobi wajib diisi',
            'no_kk.required' => 'Nomor KK wajib diisi',
            'kepala_keluarga.required' => 'Nama kepala keluarga wajib diisi',
            'no_kip.required' => 'Nomor KIP wajib diisi',
            'kebutuhan_khusus.required' => 'Kebutuhan khusus wajib dipilih',
            'kebutuhan_khusus_lainnya.required' => 'Sebutkan kebutuhan khusus lainnya',
            'disabilitas_lainnya.required' => 'Sebutkan disabilitas lainnya',
            'file_kk.required' => 'Unggah Kartu Keluarga',
            'file_akta.required' => 'Unggah Akta Kelahiran',
            'file_kip.required' => 'Unggah kartu KIP karena nomor KIP diisi',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function siswaPayload(Request $request, array $data, ?Siswa $siswa = null): array
    {
        $tidakPunyaHp = $request->boolean('tidak_punya_hp');
        $tidakPunyaEmail = $request->boolean('tidak_punya_email');

        $payload = [
            'nama' => $data['nama'],
            'nis' => $data['nis'] ?? null,
            'punya_nisn' => true,
            'nisn' => $data['nisn'],
            'punya_nik' => true,
            'nik' => $data['nik'],
            'kewarganegaraan' => 'WNI',
            'tempat_lahir' => $data['tempat_lahir'],
            'tanggal_lahir' => $data['tanggal_lahir'],
            'jenis_kelamin' => $data['jenis_kelamin'],
            'jumlah_saudara' => $data['jumlah_saudara'],
            'anak_ke' => $data['anak_ke'],
            'agama' => $data['agama'],
            'cita_cita' => $data['cita_cita'],
            'hobi' => $data['hobi'] ?? null,
            'tidak_punya_hp' => $tidakPunyaHp,
            'no_hp' => $tidakPunyaHp ? null : ($data['no_hp'] ?? null),
            'tidak_punya_email' => $tidakPunyaEmail,
            'email' => $tidakPunyaEmail ? null : ($data['email'] ?? null),
        ];

        if (! $siswa) {
            $payload['status_keaktifan'] = 'aktif_tanpa_rombel';
        }

        if ($request->filled('jumlah_saudara') && $request->filled('anak_ke')) {
            if ((int) $data['anak_ke'] > ((int) $data['jumlah_saudara'] + 1)) {
                throw ValidationException::withMessages([
                    'anak_ke' => 'Anak ke tidak bisa lebih dari jumlah saudara + 1',
                ]);
            }
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function periodikIdentitasPayload(Request $request, int $tahunId): array
    {
        $tidakPunyaKip = $request->boolean('tidak_punya_kip');

        return [
            'tahun_ajaran_id' => $tahunId,
            'pembiaya' => $request->input('pembiaya'),
            'no_kk' => $request->input('no_kk'),
            'kepala_keluarga' => $request->input('kepala_keluarga'),
            'tidak_punya_kip' => $tidakPunyaKip,
            'no_kip' => $tidakPunyaKip ? null : $request->input('no_kip'),
            'pernah_tk_ra' => $request->boolean('pernah_tk_ra'),
            'pernah_paud' => $request->boolean('pernah_paud'),
            'imunisasi' => array_values($request->input('imunisasi', [])),
            'kebutuhan_khusus' => $request->filled('kebutuhan_khusus') ? [$request->input('kebutuhan_khusus')] : null,
            'kebutuhan_khusus_lainnya' => $request->input('kebutuhan_khusus') === 'Lainnya'
                ? $request->input('kebutuhan_khusus_lainnya')
                : null,
            'disabilitas' => $this->disabilitasPayload($request),
            'disabilitas_lainnya' => in_array('Lainnya', $request->input('disabilitas', []), true)
                ? $request->input('disabilitas_lainnya')
                : null,
        ];
    }

    public function ensureRelasi(Siswa $siswa): void
    {
        foreach (['ayah', 'ibu', 'wali'] as $peran) {
            $siswa->orangTuas()->firstOrCreate(
                ['peran' => $peran],
            );
        }

        if ($tahun = TahunAjaran::aktif()) {
            $siswa->periodiks()->firstOrCreate(['tahun_ajaran_id' => $tahun->id]);
        }
    }

    public function simpanDokumen(Request $request, Siswa $siswa, string $field, string $jenis): void
    {
        if (! $request->hasFile($field)) {
            return;
        }

        $file = $request->file($field);
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'jpg';
        $lama = $siswa->dokumens()->where('jenis', $jenis)->value('path');
        $path = $file->storeAs("dokumen/{$siswa->id}", "{$jenis}.{$ext}", 'r2');

        $siswa->dokumens()->updateOrCreate(
            ['jenis' => $jenis],
            ['path' => $path, 'nama_asli' => $file->getClientOriginalName()],
        );

        if ($lama && $lama !== $path) {
            Storage::disk('r2')->delete($lama);
        }
    }

    public function simpanFoto(Request $request, Siswa $siswa): void
    {
        if (! $request->hasFile('foto')) {
            return;
        }

        $file = $request->file('foto');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'jpg';
        $lama = $siswa->foto;
        $path = $file->storeAs("foto/{$siswa->id}", "profil.{$ext}", 'r2');
        $siswa->update(['foto' => $path]);

        if ($lama && $lama !== $path) {
            Storage::disk('r2')->delete($lama);
        }
    }

    /**
     * @return list<string>|null
     */
    private function disabilitasPayload(Request $request): ?array
    {
        $items = array_values(array_filter($request->input('disabilitas', [])));

        if ($items === []) {
            return null;
        }

        if (in_array('Tidak Ada', $items, true)) {
            return ['Tidak Ada'];
        }

        return $items;
    }

    private function validateOrangTua(Request $request, Siswa $siswa): void
    {
        $siswa->loadMissing('dokumens');

        $ayahMeninggal = $request->input('ortu.ayah.status_hidup') === 'meninggal';
        $ibuMeninggal = $request->input('ortu.ibu.status_hidup') === 'meninggal';
        if ($ayahMeninggal && $ibuMeninggal) {
            $ortu = $request->input('ortu', []);
            $ortu['wali'] = array_merge($ortu['wali'] ?? [], ['status' => 'Lainnya']);
            $request->merge(['ortu' => $ortu]);
        }

        $waliStatus = $request->input('ortu.wali.status');
        $waliLainnya = in_array($waliStatus, ['Lainnya', 'Isi sendiri'], true);

        if ($waliLainnya) {
            $ortu = $request->input('ortu', []);
            $ortu['wali'] = array_merge($ortu['wali'] ?? [], ['status_hidup' => 'hidup']);
            $request->merge(['ortu' => $ortu]);
        }
        $tidakPunyaKks = $request->boolean('tidak_punya_kks');
        $tidakPunyaPkh = $request->boolean('tidak_punya_pkh');
        $this->siapkanNomorOrtu($request);
        $noKks = $tidakPunyaKks ? null : $request->input('no_kks');
        $noPkh = $tidakPunyaPkh ? null : $request->input('no_pkh');

        $request->validate(array_merge(
            [
                'ortu' => ['required', 'array'],
                'ortu.wali.status' => [
                    'required',
                    'string',
                    Rule::in(array_keys(config('emis.status_wali'))),
                    function (string $attribute, mixed $value, \Closure $fail) use ($ayahMeninggal, $ibuMeninggal): void {
                        if ($ayahMeninggal && $value === 'Sama dengan ayah kandung') {
                            $fail('Status wali tidak dapat sama dengan ayah kandung karena ayah sudah meninggal dunia.');
                        }

                        if ($ibuMeninggal && $value === 'Sama dengan ibu kandung') {
                            $fail('Status wali tidak dapat sama dengan ibu kandung karena ibu sudah meninggal dunia.');
                        }
                    },
                ],
                'ortu.wali.hubungan' => [Rule::requiredIf($waliLainnya), 'nullable', 'string', Rule::in(array_keys(config('emis.hubungan_wali')))],
                'penghasilan_gabungan' => ['required', 'string', Rule::in(array_keys(config('emis.penghasilan_gabungan')))],
                'tidak_punya_kks' => ['sometimes', 'boolean'],
                'tidak_punya_pkh' => ['sometimes', 'boolean'],
                'no_kks' => [Rule::requiredIf(! $tidakPunyaKks), 'nullable', 'string', 'max:30'],
                'no_pkh' => [Rule::requiredIf(! $tidakPunyaPkh), 'nullable', 'string', 'max:30'],
                'file_kks' => [
                    Rule::requiredIf(filled($noKks) && $siswa->dokumenJenis('kks') === null),
                    'nullable',
                    'file',
                    'mimes:pdf,jpg,jpeg,png',
                    'max:1024',
                ],
                'file_pkh' => [
                    Rule::requiredIf(filled($noPkh) && $siswa->dokumenJenis('pkh') === null),
                    'nullable',
                    'file',
                    'mimes:pdf,jpg,jpeg,png',
                    'max:1024',
                ],
            ],
            $this->aturanDataOrtu($request, 'ortu.ayah', true),
            $this->aturanDataOrtu($request, 'ortu.ibu', true),
            $this->aturanDataOrtu($request, 'ortu.wali', $waliLainnya),
        ), [
            'ortu.ayah.nama.required' => 'Nama ayah wajib diisi',
            'ortu.ayah.nama.regex' => 'Nama ayah hanya dapat diisi huruf dan simbol -\'.,',
            'ortu.ayah.status_hidup.required' => 'Status ayah wajib dipilih',
            'ortu.ayah.nik.required' => 'NIK ayah wajib diisi',
            'ortu.ayah.nik.digits' => 'NIK ayah harus 16 digit angka',
            'ortu.ayah.tempat_lahir.required' => 'Tempat lahir ayah wajib diisi',
            'ortu.ayah.tanggal_lahir.required' => 'Tanggal lahir ayah wajib diisi',
            'ortu.ayah.pendidikan.required' => 'Pendidikan ayah wajib dipilih',
            'ortu.ayah.pekerjaan.required' => 'Pekerjaan ayah wajib dipilih',
            'ortu.ayah.penghasilan.required' => 'Penghasilan ayah wajib dipilih',
            'ortu.ayah.no_hp.required' => 'Nomor HP ayah wajib diisi',
            'ortu.ayah.no_hp.regex' => 'Nomor HP ayah harus diawali 62 diikuti 8 sampai 15 digit',
            'ortu.ibu.nama.required' => 'Nama ibu wajib diisi',
            'ortu.ibu.nama.regex' => 'Nama ibu hanya dapat diisi huruf dan simbol -\'.,',
            'ortu.ibu.status_hidup.required' => 'Status ibu wajib dipilih',
            'ortu.ibu.nik.required' => 'NIK ibu wajib diisi',
            'ortu.ibu.nik.digits' => 'NIK ibu harus 16 digit angka',
            'ortu.ibu.tempat_lahir.required' => 'Tempat lahir ibu wajib diisi',
            'ortu.ibu.tanggal_lahir.required' => 'Tanggal lahir ibu wajib diisi',
            'ortu.ibu.pendidikan.required' => 'Pendidikan ibu wajib dipilih',
            'ortu.ibu.pekerjaan.required' => 'Pekerjaan ibu wajib dipilih',
            'ortu.ibu.penghasilan.required' => 'Penghasilan ibu wajib dipilih',
            'ortu.ibu.no_hp.required' => 'Nomor HP ibu wajib diisi',
            'ortu.ibu.no_hp.regex' => 'Nomor HP ibu harus diawali 62 diikuti 8 sampai 15 digit',
            'ortu.wali.status.required' => 'Status wali wajib dipilih',
            'ortu.wali.hubungan.required' => 'Hubungan wali wajib dipilih',
            'ortu.wali.nama.required' => 'Nama wali wajib diisi',
            'ortu.wali.nama.regex' => 'Nama wali hanya dapat diisi huruf dan simbol -\'.,',
            'ortu.wali.nik.required' => 'NIK wali wajib diisi',
            'ortu.wali.nik.digits' => 'NIK wali harus 16 digit angka',
            'ortu.wali.tempat_lahir.required' => 'Tempat lahir wali wajib diisi',
            'ortu.wali.tanggal_lahir.required' => 'Tanggal lahir wali wajib diisi',
            'ortu.wali.pendidikan.required' => 'Pendidikan wali wajib dipilih',
            'ortu.wali.pekerjaan.required' => 'Pekerjaan wali wajib dipilih',
            'ortu.wali.penghasilan.required' => 'Penghasilan wali wajib dipilih',
            'ortu.wali.no_hp.required' => 'Nomor HP wali wajib diisi',
            'ortu.wali.no_hp.regex' => 'Nomor HP wali harus diawali 62 diikuti 8 sampai 15 digit',
            'penghasilan_gabungan.required' => 'Penghasilan gabungan wajib dipilih',
            'no_kks.required' => 'Nomor KKS wajib diisi, atau centang tidak memiliki KKS',
            'no_pkh.required' => 'Nomor PKH wajib diisi, atau centang tidak memiliki PKH',
            'file_kks.required' => 'Unggah kartu KKS karena nomor KKS diisi',
            'file_pkh.required' => 'Unggah kartu PKH karena nomor PKH diisi',
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function aturanDataOrtu(Request $request, string $prefix, bool $wajibIdentitas): array
    {
        $hidup = $request->input("{$prefix}.status_hidup") === 'hidup';
        $wajibHidup = $wajibIdentitas && $hidup;
        $tidakPunyaHp = $request->boolean("{$prefix}.tidak_punya_hp");

        return [
            "{$prefix}.nama" => [Rule::requiredIf($wajibIdentitas), 'nullable', 'string', 'max:255', 'regex:'.self::NAMA_ORANG],
            "{$prefix}.status_hidup" => [
                Rule::requiredIf($wajibIdentitas),
                'nullable',
                'string',
                Rule::in(array_keys(config('emis.status_hidup'))),
            ],
            "{$prefix}.nik" => [Rule::requiredIf($wajibHidup), 'nullable', 'digits:16'],
            "{$prefix}.tempat_lahir" => [Rule::requiredIf($wajibHidup), 'nullable', 'string', 'max:100'],
            "{$prefix}.tanggal_lahir" => [Rule::requiredIf($wajibHidup), 'nullable', 'date'],
            "{$prefix}.pendidikan" => [
                Rule::requiredIf($wajibHidup),
                'nullable',
                'string',
                Rule::in(array_keys(config('emis.pendidikan'))),
            ],
            "{$prefix}.pekerjaan" => [
                Rule::requiredIf($wajibHidup),
                'nullable',
                'string',
                Rule::in(array_keys(config('emis.pekerjaan'))),
            ],
            "{$prefix}.penghasilan" => [
                Rule::requiredIf($wajibHidup),
                'nullable',
                'string',
                Rule::in(array_keys(config('emis.penghasilan'))),
            ],
            "{$prefix}.tidak_punya_hp" => ['sometimes', 'boolean'],
            "{$prefix}.no_hp" => [
                Rule::requiredIf($wajibHidup && ! $tidakPunyaHp),
                'nullable',
                'regex:/^62[0-9]{8,15}$/',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function ortuPayload(array $input, string $peran): array
    {
        $tidakPunyaHp = (bool) ($input['tidak_punya_hp'] ?? false);
        $statusHidup = $input['status_hidup'] ?? null;
        $hidup = $statusHidup === 'hidup';

        return [
            'nama' => $input['nama'] ?? null,
            'status_hidup' => $statusHidup,
            'status' => $peran === 'wali' ? ($input['status'] ?? null) : $statusHidup,
            'nik' => $hidup ? ($input['nik'] ?? null) : null,
            'tempat_lahir' => $hidup ? ($input['tempat_lahir'] ?? null) : null,
            'tanggal_lahir' => $hidup && ($input['tanggal_lahir'] ?? '') !== '' ? $input['tanggal_lahir'] : null,
            'pendidikan' => $hidup ? ($input['pendidikan'] ?? null) : null,
            'pekerjaan' => $hidup ? ($input['pekerjaan'] ?? null) : null,
            'penghasilan' => $hidup ? ($input['penghasilan'] ?? null) : null,
            'tidak_punya_hp' => $hidup ? $tidakPunyaHp : false,
            'no_hp' => $hidup && ! $tidakPunyaHp ? ($input['no_hp'] ?? null) : null,
            'hubungan' => $peran === 'wali' ? ($input['hubungan'] ?? null) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function alamatOrtuPayload(array $input): array
    {
        $provinsi = $input['provinsi'] ?? null;
        $kota = $input['kota'] ?? null;
        $kecamatan = $input['kecamatan'] ?? null;
        $desa = $input['desa'] ?? null;
        $blok = $input['blok'] ?? null;
        $rt = $input['rt'] ?? null;
        $rw = $input['rw'] ?? null;

        return [
            'status_tempat_tinggal' => $input['status_tempat_tinggal'] ?? null,
            'provinsi' => $provinsi,
            'kota' => $kota,
            'kecamatan' => $kecamatan,
            'desa' => $desa,
            'blok' => $blok,
            'rt' => $rt,
            'rw' => $rw,
            'kode_pos' => ($input['kode_pos'] ?? null) ?: Wilayah::kodePos(
                (string) $provinsi,
                (string) $kota,
                (string) $kecamatan,
                (string) $desa,
            ),
            'alamat' => Wilayah::formatAlamat($blok, $rt, $rw, $desa, $kecamatan, $kota),
        ];
    }

    private function sudahMeninggal(Siswa $siswa, string $peran): bool
    {
        return $siswa->orangTuas->firstWhere('peran', $peran)?->status_hidup === 'meninggal';
    }

    /**
     * @return list<string>
     */
    private function alamatOrtuKeys(): array
    {
        return [
            'status_tempat_tinggal', 'provinsi', 'kota', 'kecamatan', 'desa',
            'blok', 'rt', 'rw', 'kode_pos', 'alamat',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function alamatOrtuUtama(Siswa $siswa): array
    {
        $siswa->loadMissing('orangTuas');

        foreach (['ayah', 'ibu', 'wali'] as $peran) {
            $ortu = $siswa->orangTuas->firstWhere('peran', $peran);

            if ($ortu && $ortu->status_hidup !== 'meninggal' && filled($ortu->desa)) {
                return Arr::only($ortu->toArray(), [
                    'provinsi', 'kota', 'kecamatan', 'desa',
                    'blok', 'rt', 'rw', 'kode_pos', 'alamat',
                ]);
            }
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveAlamatSiswa(Request $request, Siswa $siswa): array
    {
        $tempat = $request->input('tempat_tinggal');
        $source = [];

        if ($tempat === 'Asrama Madrasah') {
            $source = Arr::except(config('emis.asrama_madrasah', []), ['koordinat']);
        } elseif ($tempat === 'Tinggal dengan Orang Tua/Wali') {
            $source = $this->alamatOrtuUtama($siswa);
        }

        if ($source === []) {
            $source = $request->only([
                'provinsi', 'kota', 'kecamatan', 'desa',
                'blok', 'rt', 'rw', 'kode_pos',
            ]);
        }

        $provinsi = $source['provinsi'] ?? null;
        $kota = $source['kota'] ?? null;
        $kecamatan = $source['kecamatan'] ?? null;
        $desa = $source['desa'] ?? null;
        $blok = $source['blok'] ?? null;
        $rt = $source['rt'] ?? null;
        $rw = $source['rw'] ?? null;

        return [
            'tempat_tinggal' => $tempat,
            'provinsi' => $provinsi,
            'kota' => $kota,
            'kecamatan' => $kecamatan,
            'desa' => $desa,
            'blok' => $blok,
            'rt' => $rt,
            'rw' => $rw,
            'kode_pos' => ($source['kode_pos'] ?? null) ?: Wilayah::kodePos(
                (string) $provinsi,
                (string) $kota,
                (string) $kecamatan,
                (string) $desa,
            ),
            'alamat' => Wilayah::formatAlamat($blok, $rt, $rw, $desa, $kecamatan, $kota),
            'koordinat' => $request->input('koordinat')
                ?: (config('emis.asrama_madrasah.koordinat') && $tempat === 'Asrama Madrasah'
                    ? config('emis.asrama_madrasah.koordinat')
                    : null),
            'jarak' => $request->input('jarak'),
            'waktu_tempuh' => $request->input('waktu_tempuh'),
            'transportasi' => $request->input('transportasi'),
        ];
    }

    private function nilaiIdentitas(Siswa $siswa, string $field): ?string
    {
        $nilai = $siswa->getAttribute($field);

        if ($nilai instanceof \DateTimeInterface) {
            return $nilai->format('Y-m-d');
        }

        return $nilai === null ? null : (string) $nilai;
    }

    private function assertNilaiPerubahan(Siswa $siswa, string $field, string $nilaiBaru): void
    {
        $aturan = match ($field) {
            'nama' => ['required', 'string', 'max:255', 'regex:'.self::NAMA_ORANG],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'nisn' => ['required', 'digits:10', Rule::unique('siswas', 'nisn')->ignore($siswa->id)],
            'nik' => ['required', 'digits:16', Rule::unique('siswas', 'nik')->ignore($siswa->id)],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date'],
            default => ['required', 'string', 'max:255'],
        };

        validator(
            ['nilai_baru' => $nilaiBaru],
            ['nilai_baru' => $aturan],
            [
                'nilai_baru.regex' => 'Nama lengkap hanya dapat diisi huruf dan simbol -\'.,',
                'nilai_baru.digits' => $field === 'nisn' ? 'NISN harus 10 digit' : 'NIK harus 16 digit',
                'nilai_baru.unique' => 'Nilai ini sudah dipakai siswa lain',
            ],
        )->validate();
    }

    private function normalisasiHp(mixed $nomor): ?string
    {
        $digits = $this->hanyaDigit($nomor);

        if ($digits === null) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return $digits === '' ? null : $digits;
    }

    private function hanyaDigit(mixed $nilai): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $nilai) ?? '';

        return $digits === '' ? null : $digits;
    }

    private function padDigit(mixed $nilai, int $panjang): ?string
    {
        $digits = $this->hanyaDigit($nilai);

        if ($digits === null) {
            return null;
        }

        return str_pad(substr($digits, 0, $panjang), $panjang, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function padAlamatNomor(array $input): array
    {
        foreach (['rt', 'rw'] as $field) {
            if (array_key_exists($field, $input)) {
                $input[$field] = $this->padDigit($input[$field] ?? null, 3);
            }
        }

        if (array_key_exists('kode_pos', $input)) {
            $input['kode_pos'] = $this->hanyaDigit($input['kode_pos'] ?? null);
        }

        return $input;
    }

    private function siapkanNomorOrtu(Request $request): void
    {
        $ortu = $request->input('ortu', []);

        if (! is_array($ortu)) {
            return;
        }

        foreach (['ayah', 'ibu', 'wali'] as $peran) {
            if (! isset($ortu[$peran]) || ! is_array($ortu[$peran])) {
                continue;
            }

            if (array_key_exists('no_hp', $ortu[$peran])) {
                $ortu[$peran]['no_hp'] = $this->normalisasiHp($ortu[$peran]['no_hp'] ?? null);
            }

            if (array_key_exists('nik', $ortu[$peran])) {
                $ortu[$peran]['nik'] = $this->hanyaDigit($ortu[$peran]['nik'] ?? null);
            }
        }

        $request->merge(['ortu' => $ortu]);
    }

    private function siapkanNomorAlamat(Request $request): void
    {
        $merge = $this->padAlamatNomor($request->only(['rt', 'rw', 'kode_pos']));
        $ortu = $request->input('ortu', []);

        if (is_array($ortu)) {
            foreach (['ayah', 'ibu', 'wali'] as $peran) {
                if (! isset($ortu[$peran]) || ! is_array($ortu[$peran])) {
                    continue;
                }

                $ortu[$peran] = $this->padAlamatNomor($ortu[$peran]);
            }

            $merge['ortu'] = $ortu;
        }

        $request->merge($merge);
    }
}
