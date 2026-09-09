<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SiswaAdminFotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_edit_admin_menampilkan_unggah_foto_siswa(): void
    {
        $this->seed();
        $siswa = $this->buatSiswa();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('siswa.edit', ['siswa' => $siswa, 'tab' => 'data-siswa']))
            ->assertOk()
            ->assertSee('Foto Siswa', false)
            ->assertSee('name="foto"', false)
            ->assertSee('rasio 3:4', false);
    }

    public function test_admin_bisa_upload_foto_siswa_saat_update(): void
    {
        Storage::fake('r2');
        $this->seed();
        $siswa = $this->buatSiswa();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('siswa.update', $siswa), array_merge($this->payloadDataSiswa($siswa), [
                'bagian' => 'data-siswa',
                'foto' => UploadedFile::fake()->image('profil.jpg', 300, 400),
            ]))
            ->assertRedirect(route('siswa.edit', ['siswa' => $siswa, 'tab' => 'data-siswa']));

        $siswa->refresh();
        $this->assertNotNull($siswa->foto);
        Storage::disk('r2')->assertExists($siswa->foto);
    }

    public function test_admin_upload_foto_ditolak_jika_rasio_bukan_3_4(): void
    {
        Storage::fake('r2');
        $this->seed();
        $siswa = $this->buatSiswa();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('siswa.edit', ['siswa' => $siswa, 'tab' => 'data-siswa']))
            ->put(route('siswa.update', $siswa), array_merge($this->payloadDataSiswa($siswa), [
                'bagian' => 'data-siswa',
                'foto' => UploadedFile::fake()->image('lebar.jpg', 400, 400),
            ]))
            ->assertRedirect(route('siswa.edit', ['siswa' => $siswa, 'tab' => 'data-siswa']))
            ->assertSessionHasErrors('foto');

        $this->assertNull($siswa->fresh()->foto);
    }

    public function test_admin_bisa_hapus_foto_siswa(): void
    {
        Storage::fake('r2');
        $this->seed();
        $path = 'foto/siswa-test/profil.jpg';
        Storage::disk('r2')->put($path, 'fake-bytes');
        $siswa = $this->buatSiswa(['foto' => $path]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('siswa.foto.destroy', $siswa))
            ->assertRedirect(route('siswa.edit', ['siswa' => $siswa, 'tab' => 'data-siswa']))
            ->assertSessionHas('status');

        $this->assertNull($siswa->fresh()->foto);
        Storage::disk('r2')->assertMissing($path);
    }

    public function test_guru_tidak_boleh_hapus_foto_siswa(): void
    {
        Storage::fake('r2');
        $this->seed();
        $path = 'foto/siswa-test/profil.jpg';
        Storage::disk('r2')->put($path, 'fake-bytes');
        $siswa = $this->buatSiswa(['foto' => $path]);
        $guru = $this->guru();

        $this->actingAs($guru)
            ->delete(route('siswa.foto.destroy', $siswa))
            ->assertForbidden();

        $this->assertSame($path, $siswa->fresh()->foto);
        Storage::disk('r2')->assertExists($path);
    }

    private function admin(): User
    {
        return User::query()->where('username', 'admin')->firstOrFail();
    }

    private function guru(): User
    {
        Role::findOrCreate(Peran::GURU);
        $user = User::factory()->create(['is_aktif' => true]);
        $user->syncRoles([Peran::GURU]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function buatSiswa(array $overrides = []): Siswa
    {
        return Siswa::query()->create(array_merge([
            'nama' => 'Siswa Foto Admin',
            'nisn' => '1234567890',
            'nik' => '3210010101120099',
            'angkatan' => 'VII',
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
    private function payloadDataSiswa(Siswa $siswa): array
    {
        return [
            'nama' => $siswa->nama,
            'angkatan' => $siswa->angkatan ?: 'VII',
            'nisn' => $siswa->nisn,
            'nik' => $siswa->nik,
            'tempat_lahir' => $siswa->tempat_lahir,
            'tanggal_lahir' => optional($siswa->tanggal_lahir)->format('Y-m-d') ?: '2012-01-01',
            'jenis_kelamin' => $siswa->jenis_kelamin ?: 'L',
            'jumlah_saudara' => 1,
            'anak_ke' => 1,
            'agama' => 'Islam',
            'cita_cita' => 'Guru',
            'hobi' => 'Membaca',
            'pembiaya' => 'Orang Tua',
            'tidak_punya_hp' => true,
            'tidak_punya_email' => true,
            'tidak_punya_kip' => true,
            'no_kk' => '3210010101120001',
            'kepala_keluarga' => 'Ayah Contoh',
            'kebutuhan_khusus' => 'Tidak Ada',
            'file_kk' => UploadedFile::fake()->create('kk.pdf', 100, 'application/pdf'),
            'file_akta' => UploadedFile::fake()->create('akta.pdf', 100, 'application/pdf'),
        ];
    }
}
