<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\OrangTua;
use App\Models\RekamDidik;
use App\Models\Siswa;
use App\Models\SiswaPeriodik;
use App\Models\TahunAjaran;
use App\Support\PernyataanSiswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiswaPernyataanApiTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function test_preview_rejected_when_kelengkapan_incomplete(): void
    {
        Storage::fake('r2');
        $this->seed();
        $siswa = $this->buatSiswa();
        $token = $this->tokenSiswa($siswa);

        $this->withToken($token)
            ->post('/api/v1/siswa/pernyataan/preview', $this->payloadPernyataan())
            ->assertStatus(422)
            ->assertJsonValidationErrors('kelengkapan');
    }

    public function test_preview_and_store_pernyataan_when_complete(): void
    {
        Storage::fake('r2');
        $this->seed();
        $siswa = $this->buatSiswaLengkap();
        $token = $this->tokenSiswa($siswa);

        $preview = $this->withToken($token)
            ->postJson('/api/v1/siswa/pernyataan/preview', $this->payloadPernyataan());
        $preview->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $preview->headers->get('content-type'));
        $this->assertGreaterThan(1000, strlen($preview->getContent()));
        $this->assertStringStartsWith('%PDF', $preview->getContent());

        $this->withToken($token)
            ->postJson('/api/v1/siswa/pernyataan', $this->payloadPernyataan())
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pernyataan.sudah', true)
            ->assertJsonPath('data.pernyataan.data_terkunci', true)
            ->assertJsonPath('data.nama_wali_efektif', 'Ayah Contoh');

        $siswa->refresh();
        $this->assertNotNull($siswa->pernyataan);
        $this->assertSame(PernyataanSiswa::VERSI, $siswa->pernyataan->versi_teks);
        $this->assertTrue(Storage::disk('r2')->exists($siswa->pernyataan->ttd_siswa_path));
        $this->assertTrue(Storage::disk('r2')->exists($siswa->pernyataan->ttd_wali_path));

        $this->withToken($token)
            ->get('/api/v1/siswa/pernyataan/unduh')
            ->assertOk();

        $this->withToken($token)
            ->putJson('/api/v1/siswa/data-siswa', ['hobi' => 'Olahraga'])
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadPernyataan(): array
    {
        return [
            'setuju_poin_1' => true,
            'setuju_poin_2' => true,
            'ttd_siswa' => self::PNG_1X1,
            'ttd_wali' => self::PNG_1X1,
        ];
    }

    private function tokenSiswa(Siswa $siswa): string
    {
        $siswa->gantiPassword('sandibaru1');

        return $this->postJson('/api/v1/siswa/login', [
            'nisn' => $siswa->nisn,
            'password' => 'sandibaru1',
        ])->assertOk()->json('token');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function buatSiswa(array $overrides = []): Siswa
    {
        return Siswa::query()->create(array_merge([
            'nama' => 'Siswa Contoh',
            'nisn' => '1234567890',
            'nik' => '3210010101120001',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-09-02',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif',
        ], $overrides));
    }

    private function buatSiswaLengkap(): Siswa
    {
        $siswa = $this->buatSiswa([
            'cita_cita' => 'Guru',
            'hobi' => 'Membaca',
            'anak_ke' => 1,
            'jumlah_saudara' => 1,
            'tidak_punya_hp' => true,
            'tidak_punya_email' => true,
        ]);

        $tahun = TahunAjaran::aktif();
        $this->assertNotNull($tahun);

        SiswaPeriodik::query()->create([
            'siswa_id' => $siswa->id,
            'tahun_ajaran_id' => $tahun->id,
            'pembiaya' => 'Orang Tua',
            'no_kk' => '3210010101120001',
            'kepala_keluarga' => 'Ayah Contoh',
            'tidak_punya_kip' => true,
            'kebutuhan_khusus' => ['Tidak Ada'],
            'tidak_punya_kks' => true,
            'tidak_punya_pkh' => true,
            'penghasilan_gabungan' => 'dibawah 800.000',
            'tempat_tinggal' => 'Bersama orang tua',
            'desa' => 'Cigasong',
        ]);

        $hidup = [
            'status_hidup' => 'hidup',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '1970-01-01',
            'pendidikan' => 'SMA/Sederajat',
            'pekerjaan' => 'Wiraswasta',
            'penghasilan' => '1.000.000 - 1.999.999',
            'no_hp' => '628123456789',
            'tidak_punya_hp' => false,
        ];

        OrangTua::query()->create(array_merge($hidup, [
            'siswa_id' => $siswa->id,
            'peran' => 'ayah',
            'nama' => 'Ayah Contoh',
            'nik' => '3210010101700001',
        ]));
        OrangTua::query()->create(array_merge($hidup, [
            'siswa_id' => $siswa->id,
            'peran' => 'ibu',
            'nama' => 'Ibu Contoh',
            'nik' => '3210010101720002',
        ]));
        OrangTua::query()->create([
            'siswa_id' => $siswa->id,
            'peran' => 'wali',
            'status' => 'Sama dengan ayah kandung',
        ]);

        RekamDidik::query()->create([
            'siswa_id' => $siswa->id,
            'nama_sd' => 'SD Negeri 1',
            'npsn' => '20200001',
            'tahun_ajaran_kelulusan' => '2024/2025',
            'nip_kepala_sekolah' => '197001011990031001',
            'nama_kepala_sekolah' => 'Kepala SD',
            'nomor_seri_ijazah' => 'DN-123',
            'tanggal_terbit_ijazah' => '2025-06-15',
        ]);

        foreach (['kk', 'akta_lahir', 'ijazah_sd'] as $jenis) {
            Dokumen::query()->create([
                'siswa_id' => $siswa->id,
                'jenis' => $jenis,
                'path' => 'siswa/'.$siswa->id.'/'.$jenis.'.jpg',
                'nama_asli' => $jenis.'.jpg',
            ]);
            Storage::disk('r2')->put('siswa/'.$siswa->id.'/'.$jenis.'.jpg', 'fake');
        }

        return $siswa->fresh();
    }
}
