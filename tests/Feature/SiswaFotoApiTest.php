<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Support\R2Url;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiswaFotoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_siswa_cannot_upload_foto_via_multipart(): void
    {
        Storage::fake('r2');
        $this->seed();
        $siswa = $this->buatSiswa();
        $token = $this->tokenSiswa($siswa);

        $this->withToken($token)
            ->post('/api/v1/siswa/dokumen/foto', [
                'foto' => UploadedFile::fake()->image('profil.jpg', 200, 200),
            ], ['Accept' => 'application/json'])
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Foto siswa hanya dapat dikelola oleh madrasah.');

        $this->assertNull($siswa->fresh()->foto);
    }

    public function test_siswa_cannot_request_presigned_url_for_foto(): void
    {
        $this->seed();
        $siswa = $this->buatSiswa();
        $token = $this->tokenSiswa($siswa);

        $this->withToken($token)
            ->postJson('/api/v1/siswa/upload-url', [
                'jenis' => 'foto',
                'filename' => 'profil.jpg',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('jenis');
    }

    public function test_siswa_cannot_confirm_foto_object_key(): void
    {
        Storage::fake('r2');
        $this->seed();
        $siswa = $this->buatSiswa();
        $token = $this->tokenSiswa($siswa);
        $key = "foto/{$siswa->id}/profil.jpg";
        Storage::disk('r2')->put($key, 'fake');

        $this->withToken($token)
            ->postJson('/api/v1/siswa/upload-confirm', [
                'object_key' => $key,
                'jenis' => 'foto',
                'nama_asli' => 'profil.jpg',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('jenis');

        $this->assertNull($siswa->fresh()->foto);
    }

    public function test_siswa_cannot_change_foto_via_data_siswa_update(): void
    {
        Storage::fake('r2');
        $this->seed();
        $existing = 'foto/existing/profil.jpg';
        Storage::disk('r2')->put($existing, 'lama');
        $siswa = $this->buatSiswa(['foto' => $existing]);
        $token = $this->tokenSiswa($siswa);

        $this->withToken($token)
            ->post('/api/v1/siswa/dokumen/kk', [
                'file_kk' => UploadedFile::fake()->image('kk.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->withToken($token)
            ->post('/api/v1/siswa/dokumen/akta_lahir', [
                'file_akta' => UploadedFile::fake()->image('akta.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->withToken($token)
            ->put('/api/v1/siswa/data-siswa', array_merge($this->payloadDataSiswa(), [
                'foto' => UploadedFile::fake()->image('baru.jpg', 200, 200),
            ]), ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertSame($existing, $siswa->fresh()->foto);
    }

    public function test_me_exposes_readable_foto_url(): void
    {
        Storage::fake('r2');
        config(['filesystems.disks.r2.url' => 'https://cdn.example.test']);
        $this->seed();
        $path = 'foto/siswa-uuid/profil.jpg';
        Storage::disk('r2')->put($path, 'bytes');
        $siswa = $this->buatSiswa(['foto' => $path]);
        $token = $this->tokenSiswa($siswa);

        $response = $this->withToken($token)
            ->getJson('/api/v1/siswa/me')
            ->assertOk()
            ->assertJsonPath('success', true);

        $fotoUrl = $response->json('data.foto_url');
        $this->assertIsString($fotoUrl);
        $this->assertNotSame('', $fotoUrl);
        $this->assertSame(R2Url::readable($path), $fotoUrl);
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
            'nama' => 'Siswa Foto',
            'nisn' => '1234567890',
            'nik' => '3210010101120099',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-01-01',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif',
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadDataSiswa(): array
    {
        return [
            'nama' => 'Siswa Foto',
            'nisn' => '1234567890',
            'nik' => '3210010101120099',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-01-01',
            'jenis_kelamin' => 'L',
            'jumlah_saudara' => 1,
            'anak_ke' => 1,
            'agama' => 'Islam',
            'cita_cita' => 'Guru',
            'hobi' => 'Membaca',
            'pembiaya' => 'Orang Tua/Wali',
            'tidak_punya_hp' => true,
            'tidak_punya_email' => true,
            'tidak_punya_kip' => true,
            'no_kk' => '3210010101120001',
            'kepala_keluarga' => 'Ayah Contoh',
            'kebutuhan_khusus' => 'Tidak Ada',
            'pernah_tk_ra' => false,
            'pernah_paud' => false,
        ];
    }
}
