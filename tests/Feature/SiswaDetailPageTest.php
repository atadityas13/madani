<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiswaDetailPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_button_shows_read_only_siswa_profile(): void
    {
        $this->seed();

        $siswa = Siswa::query()->create([
            'nama' => 'Adam Muhamad Albar',
            'nisn' => '3127710305',
            'nik' => '3210230911120003',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-11-09',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'hobi' => 'Membaca',
            'status_keaktifan' => 'aktif',
        ]);

        $admin = User::query()->where('username', 'admin')->first();

        $this->actingAs($admin)
            ->get(route('siswa.show', $siswa))
            ->assertOk()
            ->assertSee('Detail Siswa', false)
            ->assertSee('Adam Muhamad Albar', false)
            ->assertSee('3210230911120003', false)
            ->assertSee('3127710305', false)
            ->assertSee('Majalengka', false)
            ->assertSee('09 November 2012', false)
            ->assertSee('Laki-laki', false)
            ->assertSee('Islam', false)
            ->assertSee('siswa-detail', false)
            ->assertDontSee('name="bagian"', false)
            ->assertDontSee('Lengkapi tab lain', false);
    }

    public function test_legacy_show_tab_query_redirects_to_edit(): void
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
        ]);

        $admin = User::query()->where('username', 'admin')->first();

        $this->actingAs($admin)
            ->get(route('siswa.show', ['siswa' => $siswa, 'tab' => 'orang-tua']))
            ->assertRedirect(route('siswa.edit', ['siswa' => $siswa, 'tab' => 'orang-tua']));
    }
}
