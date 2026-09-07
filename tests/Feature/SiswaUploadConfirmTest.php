<?php

namespace Tests\Feature;

use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiswaUploadConfirmTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_rejects_object_key_milik_siswa_lain(): void
    {
        Storage::fake('r2');
        $this->seed();

        $siswa = $this->buatSiswa();
        $token = $this->tokenSiswa($siswa);
        $foreignKey = 'dokumen/siswa-lain/kk.jpg';
        Storage::disk('r2')->put($foreignKey, 'fake');

        $this->withToken($token)
            ->postJson('/api/v1/siswa/upload-confirm', [
                'object_key' => $foreignKey,
                'jenis' => 'kk',
                'nama_asli' => 'kk.jpg',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Object key tidak valid untuk akun ini.');
    }

    public function test_confirm_accepts_object_key_milik_sendiri(): void
    {
        Storage::fake('r2');
        $this->seed();

        $siswa = $this->buatSiswa();
        $token = $this->tokenSiswa($siswa);
        $key = "dokumen/{$siswa->id}/kk.jpg";
        Storage::disk('r2')->put($key, 'fake');

        $this->withToken($token)
            ->postJson('/api/v1/siswa/upload-confirm', [
                'object_key' => $key,
                'jenis' => 'kk',
                'nama_asli' => 'kk.jpg',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('dokumens', [
            'siswa_id' => $siswa->id,
            'jenis' => 'kk',
            'path' => $key,
        ]);
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
            'nama' => 'Siswa Upload',
            'nisn' => '1234567890',
            'nik' => '3210010101120099',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-01-01',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif',
        ], $overrides));
    }
}
