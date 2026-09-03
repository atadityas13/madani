<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Models\User;
use App\Support\SiswaPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SiswaPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_siswa_can_login_with_nisn_and_birthdate_password(): void
    {
        $this->seed();
        $siswa = $this->buatSiswa();

        $this->post('/siswa/masuk', [
            'nisn' => '1234567890',
            'password' => '02092012',
        ])->assertRedirect('/siswa/password');

        $this->assertAuthenticatedAs($siswa, 'siswa');
    }

    public function test_siswa_login_fails_with_wrong_password(): void
    {
        $this->seed();
        $this->buatSiswa();

        $this->from('/siswa/masuk')
            ->post('/siswa/masuk', [
                'nisn' => '1234567890',
                'password' => 'salah',
            ])
            ->assertRedirect('/siswa/masuk')
            ->assertSessionHasErrors('nisn');
    }

    public function test_inactive_siswa_cannot_login(): void
    {
        $this->seed();
        $this->buatSiswa(['status_keaktifan' => 'nonaktif']);

        $this->from('/siswa/masuk')
            ->post('/siswa/masuk', [
                'nisn' => '1234567890',
                'password' => '02092012',
            ])
            ->assertRedirect('/siswa/masuk')
            ->assertSessionHasErrors('nisn');
    }

    public function test_siswa_must_change_password_before_using_portal(): void
    {
        $this->seed();
        $siswa = $this->buatSiswa();

        $this->actingAs($siswa, 'siswa')
            ->get('/siswa/portal')
            ->assertRedirect('/siswa/password');

        $this->actingAs($siswa, 'siswa')
            ->put('/siswa/password', [
                'current_password' => '02092012',
                'password' => 'sandibaru1',
                'password_confirmation' => 'sandibaru1',
            ])
            ->assertRedirect('/siswa/portal');

        $siswa->refresh();
        $this->assertFalse($siswa->must_change_password);
        $this->assertTrue(Hash::check('sandibaru1', $siswa->getAuthPassword()));

        $this->actingAs($siswa, 'siswa')
            ->get('/siswa/portal')
            ->assertOk()
            ->assertSee('Data saya');
    }

    public function test_gtk_can_reset_siswa_password(): void
    {
        $this->seed();
        $siswa = $this->buatSiswa();
        $siswa->gantiPassword('sandibaru1');
        $admin = User::query()->where('username', 'admin')->first();

        $this->actingAs($admin)
            ->post('/siswa/'.$siswa->id.'/reset-password')
            ->assertRedirect();

        $siswa->refresh();
        $this->assertTrue($siswa->must_change_password);
        $this->assertTrue(Hash::check('02092012', $siswa->getAuthPassword()));
    }

    public function test_api_login_me_and_password_change(): void
    {
        $this->seed();
        $siswa = $this->buatSiswa();

        $this->postJson('/api/v1/siswa/login', [
            'nisn' => '1234567890',
            'password' => 'salah',
        ])->assertUnprocessable();

        $login = $this->postJson('/api/v1/siswa/login', [
            'nisn' => '1234567890',
            'password' => '02092012',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('must_change_password', true);

        $token = $login->json('token');
        $this->assertNotEmpty($token);

        $this->withToken($token)
            ->getJson('/api/v1/siswa/me')
            ->assertOk()
            ->assertJsonPath('data.nisn', '1234567890')
            ->assertJsonPath('data.nama', 'Siswa Contoh');

        $this->withToken($token)
            ->putJson('/api/v1/siswa/data-siswa', [
                'nama' => 'Siswa Contoh',
                'nisn' => '1234567890',
                'nik' => '3210010101120001',
                'tempat_lahir' => 'Majalengka',
                'tanggal_lahir' => '2012-09-02',
                'jenis_kelamin' => 'L',
                'jumlah_saudara' => 1,
                'anak_ke' => 1,
                'agama' => 'Islam',
                'cita_cita' => 'Guru',
                'pembiaya' => 'Orang Tua',
                'tidak_punya_hp' => true,
            ])
            ->assertForbidden()
            ->assertJsonPath('must_change_password', true);

        $this->withToken($token)
            ->putJson('/api/v1/siswa/password', [
                'current_password' => '02092012',
                'password' => 'sandibaru1',
                'password_confirmation' => 'sandibaru1',
            ])
            ->assertOk()
            ->assertJsonPath('must_change_password', false);

        $this->withToken($token)
            ->putJson('/api/v1/siswa/data-siswa', [
                'nama' => 'Siswa Contoh',
                'nisn' => '1234567890',
                'nik' => '3210010101120001',
                'tempat_lahir' => 'Majalengka',
                'tanggal_lahir' => '2012-09-02',
                'jenis_kelamin' => 'L',
                'jumlah_saudara' => 1,
                'anak_ke' => 1,
                'agama' => 'Islam',
                'cita_cita' => 'Guru',
                'pembiaya' => 'Orang Tua',
                'tidak_punya_hp' => true,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->withToken($token)
            ->getJson('/api/v1/siswa/me')
            ->assertOk()
            ->assertJsonPath('data.id', $siswa->id)
            ->assertJsonPath('data.nisn', '1234567890');
    }

    public function test_password_awal_command_fills_missing_passwords(): void
    {
        $this->seed();
        $siswa = Siswa::query()->create([
            'nama' => 'Tanpa Password',
            'nisn' => '1111222233',
            'tanggal_lahir' => '2011-01-15',
            'status_keaktifan' => 'aktif_tanpa_rombel',
        ]);
        $siswa->forceFill(['password' => null])->save();

        $this->artisan('siswa:set-password-awal')->assertSuccessful();

        $siswa->refresh();
        $this->assertTrue(Hash::check(SiswaPassword::dariTanggalLahir('2011-01-15'), $siswa->getAuthPassword()));
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
}
