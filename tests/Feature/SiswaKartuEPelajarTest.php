<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Models\SiswaPeriodik;
use App\Models\TahunAjaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SiswaKartuEPelajarTest extends TestCase
{
    use RefreshDatabase;

    public function test_siswa_can_fetch_kartu_e_pelajar_payload(): void
    {
        $this->seed();
        [$token, $siswa] = $this->siswaWithKartuData();

        $this->withToken($token)
            ->getJson('/api/v1/siswa/kartu-e-pelajar')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nama', $siswa->nama)
            ->assertJsonPath('data.nisn', $siswa->nisn)
            ->assertJsonPath('data.nis', $siswa->nis)
            ->assertJsonPath('data.jenis_kelamin_label', 'Laki-laki')
            ->assertJsonPath('data.alamat', 'Blok A, RT. 001 RW. 002 Desa Rawa Kec. Cingambul Kab. Majalengka, Jawa Barat, 45467')
            ->assertJsonStructure([
                'data' => [
                    'ttl',
                    'verify_url',
                    'madrasah' => ['nama', 'alamat', 'kontak', 'logo_kemenag_url'],
                ],
            ]);
    }

    public function test_signed_kartu_verification_page_shows_siswa(): void
    {
        $this->seed();
        [, $siswa] = $this->siswaWithKartuData();

        $url = URL::signedRoute('kartu-e-pelajar.cek', ['siswa' => $siswa->id]);

        $this->get($url)
            ->assertOk()
            ->assertSee('Kartu E-Pelajar Terverifikasi')
            ->assertSee($siswa->nama)
            ->assertSee($siswa->nisn);
    }

    public function test_unsigned_kartu_verification_is_forbidden(): void
    {
        $this->seed();
        [, $siswa] = $this->siswaWithKartuData();

        $this->get(route('kartu-e-pelajar.cek', ['siswa' => $siswa->id]))
            ->assertForbidden();
    }

    /**
     * @return array{0: string, 1: Siswa}
     */
    private function siswaWithKartuData(): array
    {
        $siswa = Siswa::query()->create([
            'nama' => 'Rafa Al Maulazki',
            'nisn' => '3124989746',
            'nis' => '12345',
            'nik' => '3210230603120003',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-03-06',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif',
        ]);
        $siswa->gantiPassword('sandibaru1');

        $tahun = TahunAjaran::aktif();
        $this->assertNotNull($tahun);

        SiswaPeriodik::query()->create([
            'siswa_id' => $siswa->id,
            'tahun_ajaran_id' => $tahun->id,
            'alamat' => 'Blok A, RT. 001 RW. 002 Desa Rawa Kec. Cingambul Kab. Majalengka',
            'blok' => 'A',
            'rt' => '001',
            'rw' => '002',
            'desa' => 'Rawa',
            'kecamatan' => 'Cingambul',
            'kota' => 'Majalengka',
            'provinsi' => 'Jawa Barat',
            'kode_pos' => '45467',
        ]);

        $token = $this->postJson('/api/v1/siswa/login', [
            'nisn' => $siswa->nisn,
            'password' => 'sandibaru1',
        ])->assertOk()->json('token');

        return [$token, $siswa->fresh()];
    }
}
