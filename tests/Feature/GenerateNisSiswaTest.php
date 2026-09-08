<?php

namespace Tests\Feature;

use App\Models\Madrasah;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateNisSiswaTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_nis_alert_when_siswa_tanpa_nis(): void
    {
        $this->seed();
        Madrasah::saatIni()->update(['nsm' => '121132100013']);

        Siswa::query()->create([
            'nama' => 'Tanpa Nis',
            'nisn' => '1000000001',
            'nik' => '3210230911120001',
            'angkatan' => 'VII',
            'nis' => null,
            'status_keaktifan' => 'aktif_tanpa_rombel',
        ]);

        $admin = User::query()->where('username', 'admin')->first();

        $this->actingAs($admin)
            ->get(route('siswa.index'))
            ->assertOk()
            ->assertSee('nis-alert-badge', false)
            ->assertSee('Terdapat 1 siswa yang belum memiliki NIS', false)
            ->assertSee('Generate NIS', false);
    }

    public function test_generate_nis_mengisi_format_nsm_tahun_urutan(): void
    {
        $this->seed();
        Madrasah::saatIni()->update(['nsm' => '121132100013']);

        $tahun = TahunAjaran::aktif();
        $this->assertNotNull($tahun);
        $this->assertSame('2026/2027', $tahun->nama);

        $siswaA = Siswa::query()->create([
            'nama' => 'Ahmad',
            'nisn' => '1000000001',
            'nik' => '3210230911120001',
            'angkatan' => 'VII',
            'nis' => null,
            'status_keaktifan' => 'aktif_tanpa_rombel',
        ]);
        $siswaB = Siswa::query()->create([
            'nama' => 'Budi',
            'nisn' => '1000000002',
            'nik' => '3210230911120002',
            'angkatan' => 'VII',
            'nis' => null,
            'status_keaktifan' => 'aktif_tanpa_rombel',
        ]);
        Siswa::query()->create([
            'nama' => 'Sudah Ada',
            'nisn' => '1000000003',
            'nik' => '3210230911120003',
            'angkatan' => 'VII',
            'nis' => '121132100013260099',
            'status_keaktifan' => 'aktif_tanpa_rombel',
        ]);
        Siswa::query()->create([
            'nama' => 'Angkatan Lain',
            'nisn' => '1000000004',
            'nik' => '3210230911120004',
            'angkatan' => 'VIII',
            'nis' => null,
            'status_keaktifan' => 'aktif_tanpa_rombel',
        ]);

        $admin = User::query()->where('username', 'admin')->first();

        $this->actingAs($admin)
            ->post(route('siswa.generate-nis'), ['angkatan' => 'VII'])
            ->assertRedirect(route('siswa.index'))
            ->assertSessionHas('status');

        $this->assertSame('121132100013260100', $siswaA->fresh()->nis);
        $this->assertSame('121132100013260101', $siswaB->fresh()->nis);
        $this->assertNull(Siswa::query()->where('nisn', '1000000004')->value('nis'));
    }

    public function test_generate_nis_gagal_jika_nsm_kosong(): void
    {
        $this->seed();
        Madrasah::saatIni()->update(['nsm' => null]);

        Siswa::query()->create([
            'nama' => 'Tanpa Nis',
            'nisn' => '1000000001',
            'nik' => '3210230911120001',
            'angkatan' => 'VII',
            'status_keaktifan' => 'aktif_tanpa_rombel',
        ]);

        $admin = User::query()->where('username', 'admin')->first();

        $this->actingAs($admin)
            ->post(route('siswa.generate-nis'), ['angkatan' => 'VII'])
            ->assertSessionHasErrors('angkatan');
    }
}
