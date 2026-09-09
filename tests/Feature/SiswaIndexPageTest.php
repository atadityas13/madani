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
            ->assertSee('>Foto</th>', false)
            ->assertSee('siswa-index-foto', false)
            ->assertSee('Menampilkan', false)
            ->assertSee('Sebelumnya', false)
            ->assertSee('Selanjutnya', false)
            ->assertSee('title="Portofolio"', false)
            ->assertSee('title="Reset password"', false)
            ->assertSee(route('siswa.portofolio', $siswa), false)
            ->assertDontSee('Tambah siswa', false);
    }

    public function test_index_urut_angkatan_lalu_rombel_lalu_nama(): void
    {
        $this->seed();

        $tahun = TahunAjaran::aktif();
        $vii2 = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => '2',
        ]);
        $vii1 = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => '1',
        ]);
        $viii1 = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VIII',
            'nama' => '1',
        ]);

        $siswaViii = Siswa::query()->create([
            'nama' => 'Aaaa Siswa Delapan',
            'nisn' => '1000000001',
            'angkatan' => 'VIII',
            'status_keaktifan' => 'aktif',
        ]);
        $siswaViiZ = Siswa::query()->create([
            'nama' => 'Zainab Tujuh Satu',
            'nisn' => '1000000002',
            'angkatan' => 'VII',
            'status_keaktifan' => 'aktif',
        ]);
        $siswaViiA = Siswa::query()->create([
            'nama' => 'Ahmad Tujuh Satu',
            'nisn' => '1000000003',
            'angkatan' => 'VII',
            'status_keaktifan' => 'aktif',
        ]);
        $siswaViiDua = Siswa::query()->create([
            'nama' => 'Budi Tujuh Dua',
            'nisn' => '1000000004',
            'angkatan' => 'VII',
            'status_keaktifan' => 'aktif',
        ]);

        $viii1->siswas()->attach($siswaViii->id, ['status' => 'aktif']);
        $vii1->siswas()->attach($siswaViiZ->id, ['status' => 'aktif']);
        $vii1->siswas()->attach($siswaViiA->id, ['status' => 'aktif']);
        $vii2->siswas()->attach($siswaViiDua->id, ['status' => 'aktif']);

        $admin = User::query()->where('username', 'admin')->first();
        $content = $this->actingAs($admin)
            ->get(route('siswa.index', ['per_page' => 'all']))
            ->assertOk()
            ->getContent();

        $posAhmad = strpos($content, 'Ahmad Tujuh Satu');
        $posZainab = strpos($content, 'Zainab Tujuh Satu');
        $posBudi = strpos($content, 'Budi Tujuh Dua');
        $posDelapan = strpos($content, 'Aaaa Siswa Delapan');

        $this->assertNotFalse($posAhmad);
        $this->assertNotFalse($posZainab);
        $this->assertNotFalse($posBudi);
        $this->assertNotFalse($posDelapan);
        $this->assertTrue($posAhmad < $posZainab, 'Dalam rombel sama, nama alfabetis');
        $this->assertTrue($posZainab < $posBudi, 'VII-1 sebelum VII-2');
        $this->assertTrue($posBudi < $posDelapan, 'Angkatan VII sebelum VIII');
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
            ->assertDontSee('Siswa Delapan B', false)
            ->assertSee('>VII-A<', false)
            ->assertDontSee('>VIII-B<', false);

        $this->actingAs($admin)
            ->get(route('siswa.index'))
            ->assertOk()
            ->assertSee('>VII-A<', false)
            ->assertSee('>VIII-B<', false);

        $this->actingAs($admin)
            ->get(route('siswa.index', ['rombel_id' => $rombelB->id]))
            ->assertOk()
            ->assertSee('Siswa Delapan B', false)
            ->assertDontSee('Siswa Tujuh A', false);
    }

    public function test_index_shows_red_cancel_icon_only_when_pernyataan_confirmed(): void
    {
        $this->seed();

        $siswa = Siswa::query()->create([
            'nama' => 'Siswa Konfirmasi',
            'nisn' => '3333333333',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-01-01',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif',
        ]);

        $admin = User::query()->where('username', 'admin')->first();

        $this->actingAs($admin)
            ->get(route('siswa.index'))
            ->assertOk()
            ->assertDontSee('emis-aksi-btn--danger', false)
            ->assertDontSee('bi-x-lg', false);

        $siswa->pernyataan()->create([
            'versi_teks' => 'v1',
            'teks_poin_1' => 'Poin 1',
            'teks_poin_2' => 'Poin 2',
            'setuju_poin_1' => true,
            'setuju_poin_2' => true,
            'nama_siswa' => $siswa->nama,
            'nama_wali' => 'Wali Siswa',
            'ttd_siswa_path' => 'ttd/siswa.png',
            'ttd_wali_path' => 'ttd/wali.png',
            'dikonfirmasi_at' => now(),
        ]);

        $html = $this->actingAs($admin)
            ->get(route('siswa.index'))
            ->assertOk()
            ->assertSee('emis-aksi-btn--danger', false)
            ->assertSee('bi-x-lg', false)
            ->assertSee('title="Batalkan pernyataan"', false)
            ->getContent();

        $detailPos = strpos($html, 'title="Detail"');
        $portoPos = strpos($html, 'title="Portofolio"');
        $editPos = strpos($html, 'title="Edit"');
        $resetPos = strpos($html, 'title="Reset password"');
        $batalPos = strpos($html, 'title="Batalkan pernyataan"');

        $this->assertNotFalse($detailPos);
        $this->assertNotFalse($portoPos);
        $this->assertNotFalse($editPos);
        $this->assertNotFalse($resetPos);
        $this->assertNotFalse($batalPos);
        $this->assertTrue($detailPos < $portoPos);
        $this->assertTrue($portoPos < $editPos);
        $this->assertTrue($editPos < $resetPos);
        $this->assertTrue($resetPos < $batalPos);
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
