<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenggunaTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_manage_users_and_admin_cannot(): void
    {
        $this->seed();
        $superadmin = User::query()->where('username', 'admin')->first();

        $this->actingAs($superadmin)
            ->get('/pengguna')
            ->assertOk()
            ->assertSee('Manajemen akun')
            ->assertSee('Super admin');

        $this->actingAs($superadmin)
            ->post('/pengguna', [
                'name' => 'Admin Biasa',
                'username' => 'adminbiasa',
                'email' => 'adminbiasa@mtsn11majalengka.sch.id',
                'password' => 'password123',
                'role' => 'admin',
                'is_aktif' => '1',
            ])
            ->assertRedirect('/pengguna');

        $admin = User::query()->where('username', 'adminbiasa')->first();
        $this->assertTrue($admin->hasRole('admin'));

        $this->actingAs($admin)
            ->get('/pengguna')
            ->assertForbidden();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Pengguna');
    }

    public function test_wali_kelas_only_sees_students_in_their_rombel(): void
    {
        $this->seed();
        $superadmin = User::query()->where('username', 'admin')->first();
        $tahun = TahunAjaran::aktif();

        $gtk = Gtk::query()->create([
            'nama' => 'Wali Tujuh A',
            'status' => 'aktif',
        ]);

        $rombel = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => 'A',
            'gtk_id' => $gtk->id,
        ]);

        $milikWali = Siswa::query()->create([
            'nama' => 'Siswa Wali',
            'status_keaktifan' => 'aktif',
        ]);
        $lain = Siswa::query()->create([
            'nama' => 'Siswa Lain',
            'status_keaktifan' => 'aktif_tanpa_rombel',
        ]);
        $rombel->siswas()->attach($milikWali->id, ['status' => 'aktif']);

        $this->actingAs($superadmin)->post('/pengguna', [
            'name' => 'Guru Wali',
            'username' => 'wali7a',
            'email' => 'wali7a@mtsn11majalengka.sch.id',
            'password' => 'password123',
            'role' => 'wali_kelas',
            'gtk_id' => $gtk->id,
            'is_aktif' => '1',
        ])->assertRedirect('/pengguna');

        $wali = User::query()->where('username', 'wali7a')->first();

        $this->actingAs($wali)
            ->get('/siswa')
            ->assertOk()
            ->assertSee('Siswa Wali')
            ->assertDontSee('Siswa Lain')
            ->assertDontSee('Tambah siswa');

        $this->actingAs($wali)
            ->get('/siswa/'.$lain->id)
            ->assertForbidden();

        $this->actingAs($wali)
            ->get('/siswa/create')
            ->assertForbidden();

        $this->actingAs($wali)
            ->get('/gtk')
            ->assertForbidden();
    }
}
