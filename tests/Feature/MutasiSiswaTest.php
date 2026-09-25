<?php

namespace Tests\Feature;

use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\SiswaMutasi;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MutasiSiswaTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_mutasi_bertab_masuk_keluar_dan_do(): void
    {
        $this->actingAsOperator();

        $this->get(route('mutasi.index'))
            ->assertOk()
            ->assertSee('Mutasi/DO')
            ->assertSee('Tambah')
            ->assertSee('Dropout')
            ->assertSee('Tahun ajaran');

        $this->get(route('mutasi.index', ['tab' => 'keluar']))
            ->assertOk()
            ->assertSee('Tambah mutasi keluar', false);

        $this->get(route('mutasi.index', ['tab' => 'do']))
            ->assertOk()
            ->assertSee('Tambah dropout', false);
    }

    public function test_daftar_mutasi_difilter_tahun_ajaran_aktif_secara_default(): void
    {
        $this->actingAsOperator();
        $tahunAktif = TahunAjaran::aktif();
        $this->assertNotNull($tahunAktif);

        $tahunLama = TahunAjaran::query()->create([
            'nama' => '2024/2025',
            'tanggal_mulai' => '2024-07-01',
            'tanggal_selesai' => '2025-06-30',
            'is_aktif' => false,
            'status' => TahunAjaran::STATUS_ARSIP,
        ]);

        $siswaAktif = Siswa::query()->create([
            'nama' => 'Siswa TA Aktif',
            'nisn' => '1010101010',
            'status_keaktifan' => 'aktif_tanpa_rombel',
            'angkatan' => 'VII',
        ]);
        $siswaLama = Siswa::query()->create([
            'nama' => 'Siswa TA Lama',
            'nisn' => '2020202020',
            'status_keaktifan' => 'aktif_tanpa_rombel',
            'angkatan' => 'VII',
        ]);

        SiswaMutasi::query()->create([
            'jenis' => SiswaMutasi::JENIS_MASUK,
            'siswa_id' => $siswaAktif->id,
            'tanggal' => now()->toDateString(),
            'alasan' => 'Lainnya',
            'jenis_sekolah' => SiswaMutasi::SEKOLAH_UMUM,
            'nama_sekolah' => 'SMP Aktif',
            'tahun_ajaran_id' => $tahunAktif->id,
        ]);
        SiswaMutasi::query()->create([
            'jenis' => SiswaMutasi::JENIS_MASUK,
            'siswa_id' => $siswaLama->id,
            'tanggal' => now()->toDateString(),
            'alasan' => 'Lainnya',
            'jenis_sekolah' => SiswaMutasi::SEKOLAH_UMUM,
            'nama_sekolah' => 'SMP Lama',
            'tahun_ajaran_id' => $tahunLama->id,
        ]);

        $this->get(route('mutasi.index', ['tab' => 'masuk']))
            ->assertOk()
            ->assertSee('Siswa TA Aktif')
            ->assertDontSee('Siswa TA Lama');

        $this->get(route('mutasi.index', ['tab' => 'masuk', 'tahun_ajaran_id' => $tahunLama->id]))
            ->assertOk()
            ->assertSee('Siswa TA Lama')
            ->assertDontSee('Siswa TA Aktif');
    }

    public function test_mutasi_masuk_membuat_siswa_aktif_tanpa_rombel_dan_periodik_pindahan(): void
    {
        $this->actingAsOperator();

        $payload = $this->payloadMasuk();

        $this->post(route('mutasi.masuk.store'), $payload)
            ->assertRedirect(route('mutasi.index', ['tab' => 'masuk']));

        $siswa = Siswa::query()->where('nisn', $payload['nisn'])->first();
        $this->assertNotNull($siswa);
        $this->assertSame('aktif_tanpa_rombel', $siswa->status_keaktifan);
        $this->assertSame('VIII', $siswa->angkatan);
        $this->assertSame('Sama dengan ayah kandung', $siswa->wali?->status);
        $this->assertSame('Ayah Contoh', $siswa->ayah?->nama);

        $periodik = $siswa->periodikAktif();
        $this->assertNotNull($periodik);
        $this->assertSame('Pindahan', $periodik->alasan_masuk);
        $this->assertSame('MTs Asal', $periodik->nama_sekolah_asal);

        $mutasi = SiswaMutasi::query()->where('siswa_id', $siswa->id)->first();
        $this->assertNotNull($mutasi);
        $this->assertSame(SiswaMutasi::JENIS_MASUK, $mutasi->jenis);
        $this->assertSame('12345678', $mutasi->nomor_dokumen_emis);

        $this->assertSame('Pindahan', $siswa->dataMasukAkademik()['status']);
        $this->assertSame('Ikut pindah orang tua', $siswa->dataMutasiMasuk()['alasan']);
    }

    public function test_mutasi_masuk_madrasah_boleh_tanpa_nomor_dokumen_emis(): void
    {
        $this->actingAsOperator();

        $this->post(route('mutasi.masuk.store'), $this->payloadMasuk([
            'jenis_sekolah' => 'madrasah',
            'nomor_dokumen_emis' => null,
            'nama_sekolah' => 'MTs Tanpa EMIS',
            'nisn' => '1357913579',
            'nik' => '3210010101010099',
        ]))->assertRedirect(route('mutasi.index', ['tab' => 'masuk']));

        $mutasi = SiswaMutasi::query()->whereHas('siswa', fn ($q) => $q->where('nisn', '1357913579'))->first();
        $this->assertNotNull($mutasi);
        $this->assertSame(SiswaMutasi::SEKOLAH_MADRASAH, $mutasi->jenis_sekolah);
        $this->assertNull($mutasi->nomor_dokumen_emis);
    }

    public function test_nomor_dokumen_emis_bisa_diedit_dari_daftar_masuk(): void
    {
        $this->actingAsOperator();

        $this->post(route('mutasi.masuk.store'), $this->payloadMasuk([
            'jenis_sekolah' => 'madrasah',
            'nomor_dokumen_emis' => null,
            'nama_sekolah' => 'MTs Edit EMIS',
            'nisn' => '2468246824',
            'nik' => '3210010101010088',
        ]))->assertRedirect();

        $mutasi = SiswaMutasi::query()->whereHas('siswa', fn ($q) => $q->where('nisn', '2468246824'))->firstOrFail();

        $this->from(route('mutasi.index', ['tab' => 'masuk']))
            ->patch(route('mutasi.nomor-dokumen-emis.update', $mutasi), [
                'nomor_dokumen_emis' => 'EMIS-EDIT-01',
            ])
            ->assertRedirect();

        $mutasi->refresh();
        $this->assertSame('EMIS-EDIT-01', $mutasi->nomor_dokumen_emis);
        $this->assertSame('EMIS-EDIT-01', $mutasi->siswa?->periodikAktif()?->npsn_asal);
    }

    public function test_mutasi_masuk_tolak_nisn_siswa_aktif(): void
    {
        $this->actingAsOperator();

        Siswa::query()->create([
            'nama' => 'Sudah Ada',
            'nisn' => '1234567890',
            'status_keaktifan' => 'aktif',
            'angkatan' => 'VII',
        ]);

        $this->post(route('mutasi.masuk.store'), $this->payloadMasuk([
            'nisn' => '1234567890',
            'jenis_sekolah' => 'umum',
            'nomor_dokumen_emis' => null,
        ]))->assertSessionHasErrors('nisn');
    }

    public function test_batalkan_masuk_hapus_siswa_jika_belum_nis(): void
    {
        $this->actingAsOperator();

        $this->post(route('mutasi.masuk.store'), $this->payloadMasuk([
            'jenis_sekolah' => 'umum',
            'nomor_dokumen_emis' => null,
            'nama_sekolah' => 'SMP Asal',
        ]))->assertRedirect();

        $siswa = Siswa::query()->where('nisn', '1234567890')->firstOrFail();
        $mutasi = SiswaMutasi::query()->where('siswa_id', $siswa->id)->firstOrFail();

        $this->delete(route('mutasi.batalkan', $mutasi))
            ->assertRedirect(route('mutasi.index', ['tab' => 'masuk']));

        $this->assertDatabaseMissing('siswa_mutasis', ['id' => $mutasi->id]);
        $this->assertDatabaseMissing('siswas', ['id' => $siswa->id]);
    }

    public function test_batalkan_masuk_ditolak_jika_sudah_nis(): void
    {
        $this->actingAsOperator();

        $this->post(route('mutasi.masuk.store'), $this->payloadMasuk([
            'jenis_sekolah' => 'umum',
            'nomor_dokumen_emis' => null,
            'nama_sekolah' => 'SMP Asal',
        ]))->assertRedirect();

        $siswa = Siswa::query()->where('nisn', '1234567890')->firstOrFail();
        $siswa->update(['nis' => '2026001']);
        $mutasi = SiswaMutasi::query()->where('siswa_id', $siswa->id)->firstOrFail();

        $this->from(route('mutasi.index', ['tab' => 'masuk']))
            ->delete(route('mutasi.batalkan', $mutasi))
            ->assertRedirect()
            ->assertSessionHasErrors('mutasi');

        $this->assertDatabaseHas('siswa_mutasis', ['id' => $mutasi->id]);
        $this->assertDatabaseHas('siswas', ['id' => $siswa->id]);
    }

    public function test_mutasi_keluar_nonaktifkan_dan_simpan_rombel(): void
    {
        $this->actingAsOperator();
        $tahun = TahunAjaran::aktif();

        $rombel = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => '1',
        ]);

        $siswa = Siswa::query()->create([
            'nama' => 'Siswa Keluar',
            'nisn' => '0987654321',
            'status_keaktifan' => 'aktif',
            'angkatan' => 'VII',
        ]);
        $siswa->orangTuas()->create(['peran' => 'wali', 'nama' => 'Wali Siswa', 'status' => 'Lainnya']);
        $rombel->siswas()->attach($siswa->id, ['status' => 'aktif']);

        $this->post(route('mutasi.keluar.store'), [
            'siswa_id' => $siswa->id,
            'tanggal' => now()->toDateString(),
            'alasan' => 'Kendala ekonomi',
            'jenis_sekolah' => 'umum',
            'nama_sekolah' => 'SMA Tujuan',
        ])->assertRedirect(route('mutasi.index', ['tab' => 'keluar']));

        $siswa->refresh();
        $this->assertSame('nonaktif', $siswa->status_keaktifan);
        $this->assertSame('Kendala ekonomi', $siswa->alasan_nonaktif);
        $this->assertFalse($siswa->rombels()->wherePivot('status', 'aktif')->exists());

        $mutasi = SiswaMutasi::query()->where('siswa_id', $siswa->id)->where('jenis', 'keluar')->first();
        $this->assertNotNull($mutasi);
        $this->assertSame($rombel->id, $mutasi->rombel_id);
    }

    public function test_batalkan_keluar_restore_rombel_dan_hapus_record(): void
    {
        $this->actingAsOperator();
        $tahun = TahunAjaran::aktif();

        $rombel = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => '2',
        ]);

        $siswa = Siswa::query()->create([
            'nama' => 'Siswa Restore',
            'nisn' => '1122334455',
            'status_keaktifan' => 'aktif',
            'angkatan' => 'VII',
        ]);
        $rombel->siswas()->attach($siswa->id, ['status' => 'aktif']);

        $this->post(route('mutasi.keluar.store'), [
            'siswa_id' => $siswa->id,
            'alasan' => 'Ikut pindah orang tua',
            'jenis_sekolah' => 'madrasah',
            'nomor_dokumen_emis' => 'EMIS-99',
            'nama_sekolah' => 'MTs Tujuan',
        ])->assertRedirect();

        $mutasi = SiswaMutasi::query()->where('siswa_id', $siswa->id)->where('jenis', 'keluar')->firstOrFail();

        $this->delete(route('mutasi.batalkan', $mutasi))
            ->assertRedirect(route('mutasi.index', ['tab' => 'keluar']));

        $siswa->refresh();
        $this->assertSame('aktif', $siswa->status_keaktifan);
        $this->assertNull($siswa->tanggal_nonaktif);
        $this->assertTrue($siswa->rombels()->wherePivot('status', 'aktif')->where('rombels.id', $rombel->id)->exists());
        $this->assertDatabaseMissing('siswa_mutasis', ['id' => $mutasi->id]);
    }

    public function test_dropout_nonaktifkan_tanpa_sekolah_tujuan(): void
    {
        $this->actingAsOperator();
        $tahun = TahunAjaran::aktif();

        $rombel = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VIII',
            'nama' => '1',
        ]);

        $siswa = Siswa::query()->create([
            'nama' => 'Siswa DO',
            'nisn' => '5566778899',
            'status_keaktifan' => 'aktif',
            'angkatan' => 'VIII',
        ]);
        $rombel->siswas()->attach($siswa->id, ['status' => 'aktif']);

        $this->post(route('mutasi.do.store'), [
            'siswa_id' => $siswa->id,
            'tanggal' => now()->toDateString(),
            'alasan' => 'Kendala akademik',
        ])->assertRedirect(route('mutasi.index', ['tab' => 'do']));

        $siswa->refresh();
        $this->assertSame('nonaktif', $siswa->status_keaktifan);
        $this->assertSame('Kendala akademik', $siswa->alasan_nonaktif);
        $this->assertFalse($siswa->rombels()->wherePivot('status', 'aktif')->exists());

        $mutasi = SiswaMutasi::query()->where('siswa_id', $siswa->id)->where('jenis', 'do')->first();
        $this->assertNotNull($mutasi);
        $this->assertNull($mutasi->nama_sekolah);
        $this->assertNull($mutasi->jenis_sekolah);
        $this->assertSame($rombel->id, $mutasi->rombel_id);

        $keluar = $siswa->dataMutasiKeluar();
        $this->assertSame('do', $keluar['jenis']);
        $this->assertSame('Dropout', $keluar['label']);
        $this->assertSame('Kendala akademik', $keluar['alasan']);
    }

    public function test_batalkan_dropout_restore_rombel(): void
    {
        $this->actingAsOperator();
        $tahun = TahunAjaran::aktif();

        $rombel = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VIII',
            'nama' => '3',
        ]);

        $siswa = Siswa::query()->create([
            'nama' => 'Siswa DO Restore',
            'nisn' => '6677889900',
            'status_keaktifan' => 'aktif',
            'angkatan' => 'VIII',
        ]);
        $rombel->siswas()->attach($siswa->id, ['status' => 'aktif']);

        $this->post(route('mutasi.do.store'), [
            'siswa_id' => $siswa->id,
            'alasan' => 'Lainnya',
        ])->assertRedirect();

        $mutasi = SiswaMutasi::query()->where('siswa_id', $siswa->id)->where('jenis', 'do')->firstOrFail();

        $this->delete(route('mutasi.batalkan', $mutasi))
            ->assertRedirect(route('mutasi.index', ['tab' => 'do']));

        $siswa->refresh();
        $this->assertSame('aktif', $siswa->status_keaktifan);
        $this->assertTrue($siswa->rombels()->wherePivot('status', 'aktif')->where('rombels.id', $rombel->id)->exists());
        $this->assertDatabaseMissing('siswa_mutasis', ['id' => $mutasi->id]);
    }

    public function test_cetak_surat_masih_placeholder(): void
    {
        $this->actingAsOperator();

        $siswa = Siswa::query()->create([
            'nama' => 'Siswa Surat',
            'status_keaktifan' => 'aktif_tanpa_rombel',
            'angkatan' => 'VII',
        ]);

        $mutasi = SiswaMutasi::query()->create([
            'jenis' => SiswaMutasi::JENIS_MASUK,
            'siswa_id' => $siswa->id,
            'tanggal' => now()->toDateString(),
            'alasan' => 'Lainnya',
            'jenis_sekolah' => SiswaMutasi::SEKOLAH_UMUM,
            'nama_sekolah' => 'SMP X',
            'dicatat_oleh' => User::query()->where('username', 'admin')->value('id'),
        ]);

        $this->get(route('mutasi.cetak', $mutasi))
            ->assertRedirect(route('mutasi.index', ['tab' => 'masuk']))
            ->assertSessionHas('error');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payloadMasuk(array $overrides = []): array
    {
        return array_merge([
            'tanggal' => now()->toDateString(),
            'alasan' => 'Ikut pindah orang tua',
            'jenis_sekolah' => 'madrasah',
            'nomor_dokumen_emis' => '12345678',
            'nama_sekolah' => 'MTs Asal',
            'nama' => 'Siswa Mutasi Masuk',
            'nisn' => '1234567890',
            'nik' => '3210010101010001',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-01-15',
            'jenis_kelamin' => 'L',
            'angkatan' => 'VIII',
            'wali_dari' => 'ayah',
            'nama_ortu' => 'Ayah Contoh',
            'pekerjaan' => 'Petani',
            'no_hp' => '081234567890',
            'alamat' => 'Jl. Merdeka 1',
            'desa' => 'Cigasong',
            'kecamatan' => 'Cigasong',
            'kota' => 'Majalengka',
            'provinsi' => 'Jawa Barat',
        ], $overrides);
    }

    private function actingAsOperator(): static
    {
        $this->seed();

        return $this->actingAs(User::query()->where('username', 'admin')->first());
    }
}
