<?php

namespace Tests\Feature;

use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\SiswaBiodataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AngkatanRombelGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_tambah_anggota_beda_angkatan_ditolak(): void
    {
        $this->actingAsOperator();
        $tahun = TahunAjaran::aktif();

        $rombel = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => '1',
        ]);

        $siswa = Siswa::query()->create([
            'nama' => 'Siswa VIII',
            'status_keaktifan' => 'aktif_tanpa_rombel',
            'angkatan' => 'VIII',
        ]);

        $this->post(route('rombel.anggota.store', $rombel), [
            'siswa_ids' => [$siswa->id],
        ])->assertSessionHasErrors('siswa_ids');

        $this->assertFalse($siswa->fresh()->rombels()->wherePivot('status', 'aktif')->exists());
    }

    public function test_tambah_anggota_sama_angkatan_berhasil(): void
    {
        $this->actingAsOperator();
        $tahun = TahunAjaran::aktif();

        $rombel = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => '1',
        ]);

        $siswa = Siswa::query()->create([
            'nama' => 'Siswa VII',
            'status_keaktifan' => 'aktif_tanpa_rombel',
            'angkatan' => 'VII',
        ]);

        $this->post(route('rombel.anggota.store', $rombel), [
            'siswa_ids' => [$siswa->id],
        ])->assertRedirect(route('rombel.show', $rombel));

        $this->assertTrue($siswa->fresh()->rombels()->wherePivot('status', 'aktif')->exists());
    }

    public function test_pindah_beda_tingkat_ditolak(): void
    {
        $this->actingAsOperator();
        $tahun = TahunAjaran::aktif();

        $sumber = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => '1',
        ]);
        $tujuan = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VIII',
            'nama' => '1',
        ]);

        $siswa = Siswa::query()->create([
            'nama' => 'Siswa Pindah Beda',
            'status_keaktifan' => 'aktif',
            'angkatan' => 'VII',
        ]);
        $sumber->siswas()->attach($siswa->id, ['status' => 'aktif']);

        $this->post(route('rombel.anggota.pindah', [$sumber, $siswa]), [
            'rombel_tujuan_id' => $tujuan->id,
        ])->assertSessionHasErrors('rombel_tujuan_id');

        $this->assertTrue($sumber->fresh()->siswas()->wherePivot('status', 'aktif')->where('siswas.id', $siswa->id)->exists());
        $this->assertFalse($tujuan->fresh()->siswas()->wherePivot('status', 'aktif')->where('siswas.id', $siswa->id)->exists());
    }

    public function test_kandidat_tambah_hanya_angkatan_sama(): void
    {
        $this->actingAsOperator();
        $tahun = TahunAjaran::aktif();

        $rombel = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => '1',
        ]);

        $cocok = Siswa::query()->create([
            'nama' => 'Kandidat Cocok',
            'status_keaktifan' => 'aktif_tanpa_rombel',
            'angkatan' => 'VII',
        ]);
        $beda = Siswa::query()->create([
            'nama' => 'Kandidat Beda',
            'status_keaktifan' => 'aktif_tanpa_rombel',
            'angkatan' => 'IX',
        ]);
        $tanpa = Siswa::query()->create([
            'nama' => 'Kandidat Tanpa Angkatan',
            'status_keaktifan' => 'aktif_tanpa_rombel',
            'angkatan' => null,
        ]);

        $this->get(route('rombel.show', $rombel))
            ->assertOk()
            ->assertSee('Kandidat Cocok', false)
            ->assertDontSee('Kandidat Beda', false)
            ->assertDontSee('Kandidat Tanpa Angkatan', false);

        $this->assertTrue($cocok->exists());
        $this->assertTrue($beda->exists());
        $this->assertTrue($tanpa->exists());
    }

    public function test_create_siswa_tanpa_angkatan_gagal_validasi(): void
    {
        Storage::fake('r2');
        $this->seed();

        $payload = $this->payloadDataSiswa();
        unset($payload['angkatan']);

        $request = Request::create('/siswa', 'POST', $payload, files: [
            'file_kk' => UploadedFile::fake()->create('kk.pdf', 100, 'application/pdf'),
            'file_akta' => UploadedFile::fake()->create('akta.pdf', 100, 'application/pdf'),
        ]);

        $this->expectException(ValidationException::class);

        try {
            app(SiswaBiodataService::class)->create($request);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('angkatan', $e->errors());
            throw $e;
        }
    }

    public function test_create_siswa_dengan_angkatan_berhasil(): void
    {
        Storage::fake('r2');
        $this->seed();

        $request = Request::create('/siswa', 'POST', $this->payloadDataSiswa([
            'angkatan' => 'VII',
        ]), files: [
            'file_kk' => UploadedFile::fake()->create('kk.pdf', 100, 'application/pdf'),
            'file_akta' => UploadedFile::fake()->create('akta.pdf', 100, 'application/pdf'),
        ]);

        $siswa = app(SiswaBiodataService::class)->create($request);

        $this->assertSame('VII', $siswa->fresh()->angkatan);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payloadDataSiswa(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'Siswa Angkatan',
            'angkatan' => 'VII',
            'nisn' => '1234567890',
            'nik' => '3210230911120003',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-11-09',
            'jenis_kelamin' => 'L',
            'jumlah_saudara' => 1,
            'anak_ke' => 1,
            'agama' => 'Islam',
            'cita_cita' => 'Guru',
            'hobi' => 'Membaca',
            'tidak_punya_hp' => true,
            'tidak_punya_email' => true,
            'tidak_punya_kip' => true,
            'pembiaya' => 'Orang tua',
            'no_kk' => '3210230911120001',
            'kepala_keluarga' => 'Ayah Siswa',
            'kebutuhan_khusus' => 'Tidak Ada',
        ], $overrides);
    }

    private function actingAsOperator(): static
    {
        $this->seed();

        return $this->actingAs(User::query()->where('username', 'admin')->first());
    }
}
