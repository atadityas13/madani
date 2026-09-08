<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\Dokumen;
use App\Models\Siswa;
use App\Models\SiswaPernyataan;
use App\Models\User;
use App\Support\PernyataanSiswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiswaMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_mencatat_first_dan_last_login_at(): void
    {
        $this->seed();
        $siswa = Siswa::query()->create([
            'nama' => 'Login Tracker',
            'nisn' => '1234567890',
            'nik' => '3210010101120001',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-09-02',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif',
        ]);
        $siswa->gantiPassword('sandibaru1');

        $this->assertNull($siswa->first_login_at);
        $this->assertNull($siswa->last_login_at);

        $this->postJson('/api/v1/siswa/login', [
            'nisn' => '1234567890',
            'password' => 'sandibaru1',
        ])->assertOk();

        $siswa->refresh();
        $this->assertNotNull($siswa->first_login_at);
        $this->assertNotNull($siswa->last_login_at);
        $first = $siswa->first_login_at->copy();

        $this->travel(5)->minutes();

        $this->postJson('/api/v1/siswa/login', [
            'nisn' => '1234567890',
            'password' => 'sandibaru1',
        ])->assertOk();

        $siswa->refresh();
        $this->assertTrue($siswa->first_login_at->equalTo($first));
        $this->assertTrue($siswa->last_login_at->greaterThan($first));
    }

    public function test_monitoring_page_menampilkan_flag_login_dan_dokumen(): void
    {
        Storage::fake('r2');
        $this->seed();

        $sudah = Siswa::query()->create([
            'nama' => 'Sudah Login',
            'nisn' => '1000000001',
            'nik' => '3210230911120001',
            'angkatan' => 'VII',
            'status_keaktifan' => 'aktif_tanpa_rombel',
            'first_login_at' => now()->subDay(),
            'last_login_at' => now(),
            'foto' => 'siswa/sudah/foto.jpg',
            'must_change_password' => false,
        ]);
        Storage::disk('r2')->put('siswa/sudah/foto.jpg', 'fake');
        Dokumen::query()->create([
            'siswa_id' => $sudah->id,
            'jenis' => 'kk',
            'path' => 'siswa/sudah/kk.jpg',
            'nama_asli' => 'kk.jpg',
        ]);
        Storage::disk('r2')->put('siswa/sudah/kk.jpg', 'fake');
        DeviceToken::query()->create([
            'tokenable_type' => Siswa::class,
            'tokenable_id' => $sudah->id,
            'fcm_token' => 'token-fcm',
            'platform' => 'android',
            'last_seen_at' => now(),
        ]);

        Siswa::query()->create([
            'nama' => 'Belum Login',
            'nisn' => '1000000002',
            'nik' => '3210230911120002',
            'angkatan' => 'VII',
            'status_keaktifan' => 'aktif_tanpa_rombel',
        ]);

        $admin = User::query()->where('username', 'admin')->first();

        $this->actingAs($admin)
            ->get(route('siswa.monitoring'))
            ->assertOk()
            ->assertSee('Monitoring siswa', false)
            ->assertSee('Sudah Login', false)
            ->assertSee('Belum Login', false)
            ->assertSee('bi-person-vcard', false)
            ->assertSee('data-monitoring-preview', false)
            ->assertSee('/siswa/'.$sudah->id.'/kartu/stream', false)
            ->assertSee('target="_blank"', false)
            ->assertSee('title="Identitas"', false)
            ->assertSee('>Ident<', false)
            ->assertSee('data-monitoring-scroll', false)
            ->assertSee('monitoring-page', false)
            ->assertSee('madani-content', false)
            ->assertDontSee('>Angkatan<', false)
            ->assertDontSee('Belum lengkap semua variabel', false);

        $this->actingAs($admin)
            ->get(route('siswa.monitoring', [
                'status_lengkap' => 'belum_variabel',
                'belum' => ['login'],
            ]))
            ->assertOk()
            ->assertSee('Belum Login', false)
            ->assertDontSee('Sudah Login', false);

        $this->actingAs($admin)
            ->get(route('siswa.foto.download', $sudah))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('siswa.dokumen.download', [$sudah, 'kk']))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('siswa.kartu', $sudah))
            ->assertRedirect(route('siswa.kartu.stream', $sudah));

        $this->actingAs($admin)
            ->get(route('siswa.kartu.stream', $sudah))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_monitoring_export_excel(): void
    {
        $this->seed();
        Siswa::query()->create([
            'nama' => 'Export Siswa',
            'nisn' => '1000000003',
            'nik' => '3210230911120003',
            'angkatan' => 'VIII',
            'status_keaktifan' => 'aktif_tanpa_rombel',
        ]);

        $admin = User::query()->where('username', 'admin')->first();

        $this->actingAs($admin)
            ->get(route('siswa.monitoring.export'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_pernyataan_download_terpisah_dari_monitoring(): void
    {
        Storage::fake('r2');
        $this->seed();

        $siswa = Siswa::query()->create([
            'nama' => 'Dengan Pernyataan',
            'nisn' => '1000000004',
            'nik' => '3210230911120004',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-01-01',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'angkatan' => 'VII',
            'status_keaktifan' => 'aktif_tanpa_rombel',
        ]);

        $teks = PernyataanSiswa::teksAktif();
        SiswaPernyataan::query()->create([
            'siswa_id' => $siswa->id,
            'versi_teks' => $teks['versi'],
            'teks_poin_1' => $teks['poin_1'],
            'teks_poin_2' => $teks['poin_2'],
            'setuju_poin_1' => true,
            'setuju_poin_2' => true,
            'nama_siswa' => $siswa->nama,
            'nama_wali' => 'Wali Contoh',
            'ttd_siswa_path' => 'siswa/'.$siswa->id.'/ttd-siswa.png',
            'ttd_wali_path' => 'siswa/'.$siswa->id.'/ttd-wali.png',
            'dikonfirmasi_at' => now(),
        ]);
        Storage::disk('r2')->put('siswa/'.$siswa->id.'/ttd-siswa.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
        Storage::disk('r2')->put('siswa/'.$siswa->id.'/ttd-wali.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

        $admin = User::query()->where('username', 'admin')->first();

        $biodata = $this->actingAs($admin)
            ->get(route('siswa.pernyataan.download', [$siswa, 'biodata']))
            ->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $biodata->headers->get('content-type'));
        $this->assertStringContainsString('Pernyataan Biodata', (string) $biodata->headers->get('content-disposition'));

        $peserta = $this->actingAs($admin)
            ->get(route('siswa.pernyataan.download', [$siswa, 'peserta-didik']))
            ->assertOk();
        $this->assertStringContainsString('Peserta Didik', (string) $peserta->headers->get('content-disposition'));
    }
}
