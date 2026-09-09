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

    public function test_halaman_edit_admin_menampilkan_unggah_foto_di_slot_foto(): void
    {
        $this->seed();
        $siswa = $this->buatSiswa();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('siswa.edit', ['siswa' => $siswa, 'tab' => 'data-siswa']))
            ->assertOk()
            ->assertSee('siswa-foto-slot', false)
            ->assertSee(route('siswa.foto.upload', $siswa), false)
            ->assertSee('rasio 3:4', false);
    }

    public function test_halaman_index_menampilkan_kolom_foto(): void
    {
        Storage::fake('r2');
        $this->seed();
        $path = 'foto/siswa-index/profil.jpg';
        Storage::disk('r2')->put($path, 'fake');
        $this->buatSiswa(['foto' => $path, 'nama' => 'Siswa Index Foto']);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('siswa.index'))
            ->assertOk()
            ->assertSee('>Foto</th>', false)
            ->assertSee('siswa-index-foto', false)
            ->assertSee('Siswa Index Foto', false);
    }

    public function test_admin_bisa_upload_foto_siswa_via_endpoint_khusus(): void
    {
        Storage::fake('r2');
        $this->seed();
        $siswa = $this->buatSiswa();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('siswa.edit', ['siswa' => $siswa, 'tab' => 'data-siswa']))
            ->post(route('siswa.foto.upload', $siswa), [
                'foto' => UploadedFile::fake()->image('profil.jpg', 300, 400),
            ])
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
            ->post(route('siswa.foto.upload', $siswa), [
                'foto' => UploadedFile::fake()->image('lebar.jpg', 400, 400),
            ])
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
            ->from(route('siswa.edit', ['siswa' => $siswa, 'tab' => 'data-siswa']))
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
}
