<?php

namespace Tests\Feature;

use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiswaKewarganegaraanRemovedTest extends TestCase
{
    use RefreshDatabase;

    public function test_siswas_table_does_not_have_kewarganegaraan_column(): void
    {
        $this->assertFalse(Schema::hasColumn('siswas', 'kewarganegaraan'));
    }

    public function test_portal_payload_omits_kewarganegaraan(): void
    {
        $this->seed();

        $siswa = Siswa::query()->create([
            'nama' => 'Siswa Contoh',
            'nisn' => '1234567890',
            'nik' => '3210010101120001',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-09-02',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif',
            'must_change_password' => false,
            'password' => 'password',
        ]);

        $token = $siswa->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/siswa/me')
            ->assertOk()
            ->assertJsonMissingPath('data.kewarganegaraan');
    }
}
