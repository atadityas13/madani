<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuruAuthApiTest extends TestCase
{
    use RefreshDatabase;

    private function buatAkunGuru(array $gtkAttrs = [], array $userAttrs = []): User
    {
        Role::findOrCreate(Peran::GURU);

        $gtk = Gtk::query()->create(array_merge([
            'nama' => 'Budi Santoso',
            'gelar_depan' => 'Drs.',
            'nip' => '198001012005011001',
            'jenis' => 'guru',
            'status' => 'aktif',
            'jabatan' => 'Guru Mapel',
            'jenis_kelamin' => 'L',
        ], $gtkAttrs));

        $user = User::factory()->create(array_merge([
            'name' => $gtk->nama,
            'username' => $gtk->nip,
            'password' => 'password123',
            'must_change_password' => true,
            'is_aktif' => true,
            'gtk_id' => $gtk->id,
        ], $userAttrs));

        $user->syncRoles([Peran::GURU]);

        return $user->fresh()->load('gtk');
    }

    public function test_login_berhasil_dengan_nip_dan_password(): void
    {
        $this->buatAkunGuru();

        $this->postJson('/api/v1/guru/login', [
            'username' => '198001012005011001',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('requires_password_change', true)
            ->assertJsonPath('user.nip', '198001012005011001')
            ->assertJsonPath('user.nama_lengkap', 'Drs. Budi Santoso')
            ->assertJsonStructure(['token', 'user' => ['guru' => ['jenis_kelamin']]]);
    }

    public function test_login_gagal_jika_gtk_belum_punya_akun(): void
    {
        Gtk::query()->create([
            'nama' => 'Tanpa Akun',
            'nip' => '198001012005011099',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $this->postJson('/api/v1/guru/login', [
            'username' => '198001012005011099',
            'password' => 'password123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['username']);
    }

    public function test_me_dan_ubah_password(): void
    {
        $user = $this->buatAkunGuru();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/guru/me')
            ->assertOk()
            ->assertJsonPath('user.username', $user->username);

        $this->putJson('/api/v1/guru/password', [
            'current_password' => 'password123',
            'password' => 'baru1234',
            'password_confirmation' => 'baru1234',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_update_biodata_dan_kontak(): void
    {
        $user = $this->buatAkunGuru();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/guru/profile/biodata', [
            'jenis_kelamin' => 'P',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '1980-01-01',
            'agama' => 'Islam',
        ])
            ->assertOk()
            ->assertJsonPath('user.guru.tempat_lahir', 'Majalengka')
            ->assertJsonPath('user.guru.jenis_kelamin', 'P');

        $this->putJson('/api/v1/guru/profile/kontak', [
            'nomor_hp' => '08123456789',
            'email' => 'budi@example.com',
            'alamat' => 'Jl. Merdeka',
        ])
            ->assertOk()
            ->assertJsonPath('user.guru.nomor_hp', '08123456789')
            ->assertJsonPath('user.guru.email', 'budi@example.com');
    }

    public function test_update_foto(): void
    {
        Storage::fake('r2');
        $user = $this->buatAkunGuru();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/guru/profile/foto', [
            'foto' => UploadedFile::fake()->image('foto.jpg', 200, 200),
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull($user->fresh()->foto);
        Storage::disk('r2')->assertExists($user->fresh()->foto);
    }

    public function test_akun_tanpa_gtk_ditolak(): void
    {
        Role::findOrCreate(Peran::ADMIN);
        $user = User::factory()->create(['gtk_id' => null, 'is_aktif' => true]);
        $user->syncRoles([Peran::ADMIN]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/guru/me')
            ->assertForbidden();
    }
}
