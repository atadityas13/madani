<?php

namespace Tests\Feature;

use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiswaIndexPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_filters_actions_and_hides_legacy_buttons(): void
    {
        $this->seed();

        $tahun = TahunAjaran::aktif();
        $rombel = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => 'A',
        ]);

        $siswa = Siswa::query()->create([
            'nama' => 'Adam Muhamad Albar',
            'nisn' => '3127710305',
            'nis' => '2026001',
            'nik' => '3210230911120003',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-11-09',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif',
        ]);
        $rombel->siswas()->attach($siswa->id, ['status' => 'aktif']);

        $admin = User::query()->where('username', 'admin')->first();

        $this->actingAs($admin)
            ->get(route('siswa.index'))
            ->assertOk()
            ->assertSee('Semua tingkat', false)
            ->assertSee('Semua rombel', false)
            ->assertSee('Adam Muhamad Albar', false)
            ->assertSee('Menampilkan', false)
            ->assertSee('Sebelumnya', false)
            ->assertSee('Selanjutnya', false)
            ->assertSee('title="Portofolio"', false)
            ->assertSee('title="Reset password"', false)
            ->assertSee(route('siswa.portofolio', $siswa), false)
            ->assertDontSee('Tambah siswa', false)
            ->assertDontSee('Periode pendataan', false);
    }

    public function test_index_filters_by_tingkat_and_rombel(): void
    {
        $this->seed();

        $tahun = TahunAjaran::aktif();
        $rombelA = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => 'A',
        ]);
        $rombelB = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VIII',
            'nama' => 'B',
        ]);

        $siswaA = Siswa::query()->create([
            'nama' => 'Siswa Tujuh A',
            'nisn' => '1111111111',
            'status_keaktifan' => 'aktif',
        ]);
        $siswaB = Siswa::query()->create([
            'nama' => 'Siswa Delapan B',
            'nisn' => '2222222222',
            'status_keaktifan' => 'aktif',
        ]);
        $rombelA->siswas()->attach($siswaA->id, ['status' => 'aktif']);
        $rombelB->siswas()->attach($siswaB->id, ['status' => 'aktif']);

        $admin = User::query()->where('username', 'admin')->first();

        $this->actingAs($admin)
            ->get(route('siswa.index', ['tingkat' => 'VII']))
            ->assertOk()
            ->assertSee('Siswa Tujuh A', false)
            ->assertDontSee('Siswa Delapan B', false);

        $this->actingAs($admin)
            ->get(route('siswa.index', ['rombel_id' => $rombelB->id]))
            ->assertOk()
            ->assertSee('Siswa Delapan B', false)
            ->assertDontSee('Siswa Tujuh A', false);
    }

    public function test_edit_page_no_longer_shows_moved_actions(): void
    {
        $this->seed();

        $siswa = Siswa::query()->create([
            'nama' => 'Siswa Edit',
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
            ->get(route('siswa.edit', $siswa))
            ->assertOk()
            ->assertSee('Detail', false)
            ->assertDontSee('>Portofolio<', false)
            ->assertDontSee('Pernyataan PDF', false)
            ->assertDontSee('Batalkan pernyataan', false)
            ->assertDontSee('>Reset password<', false);
    }
}
