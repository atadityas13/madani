<?php

namespace Tests\Feature;

use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\SiswaMutasi;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRingkasanTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_menampilkan_rombel_siswa_aktif_masuk_dan_keluar(): void
    {
        $this->seed();
        $tahun = TahunAjaran::aktif();
        $this->assertNotNull($tahun);

        $rombel = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => '1',
        ]);

        $aktif = Siswa::query()->create([
            'nama' => 'Siswa Aktif Dashboard',
            'status_keaktifan' => 'aktif',
            'angkatan' => 'VII',
        ]);
        $rombel->siswas()->attach($aktif->id, ['status' => 'aktif']);

        Siswa::query()->create([
            'nama' => 'Siswa Tanpa Rombel',
            'status_keaktifan' => 'aktif_tanpa_rombel',
            'angkatan' => 'VIII',
        ]);

        $masuk = Siswa::query()->create([
            'nama' => 'Siswa Mutasi Masuk',
            'status_keaktifan' => 'aktif_tanpa_rombel',
            'angkatan' => 'VII',
        ]);
        SiswaMutasi::query()->create([
            'jenis' => SiswaMutasi::JENIS_MASUK,
            'siswa_id' => $masuk->id,
            'tanggal' => now()->toDateString(),
            'alasan' => 'Lainnya',
            'jenis_sekolah' => SiswaMutasi::SEKOLAH_UMUM,
            'nama_sekolah' => 'SMP Asal',
            'tahun_ajaran_id' => $tahun->id,
        ]);

        $keluar = Siswa::query()->create([
            'nama' => 'Siswa Mutasi Keluar',
            'status_keaktifan' => 'nonaktif',
            'angkatan' => 'VII',
        ]);
        SiswaMutasi::query()->create([
            'jenis' => SiswaMutasi::JENIS_KELUAR,
            'siswa_id' => $keluar->id,
            'tanggal' => now()->toDateString(),
            'alasan' => 'Ikut pindah orang tua',
            'tahun_ajaran_id' => $tahun->id,
            'rombel_id' => $rombel->id,
        ]);

        $do = Siswa::query()->create([
            'nama' => 'Siswa Dropout Dashboard',
            'status_keaktifan' => 'nonaktif',
            'angkatan' => 'VII',
        ]);
        SiswaMutasi::query()->create([
            'jenis' => SiswaMutasi::JENIS_DO,
            'siswa_id' => $do->id,
            'tanggal' => now()->toDateString(),
            'alasan' => 'Kendala akademik',
            'tahun_ajaran_id' => $tahun->id,
            'rombel_id' => $rombel->id,
        ]);

        $admin = User::query()->where('username', 'admin')->first();

        $html = $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('>Tanpa rombel<', false)
            ->getContent();

        $this->assertMatchesRegularExpression('/stat-label">Rombel<\/div>\s*<div class="fs-3 fw-bold">1<\/div>/', $html);
        $this->assertMatchesRegularExpression('/stat-label">Siswa Aktif<\/div>\s*<div class="fs-3 fw-bold">1<\/div>/', $html);
        $this->assertMatchesRegularExpression('/stat-label">Aktif tanpa rombel<\/div>\s*<div class="fs-3 fw-bold">2<\/div>/', $html);
        $this->assertMatchesRegularExpression('/stat-label">Siswa Masuk<\/div>\s*<div class="fs-3 fw-bold">1<\/div>/', $html);
        $this->assertMatchesRegularExpression('/stat-label">Siswa Keluar \(mutasi\+DO\)<\/div>\s*<div class="fs-3 fw-bold">2<\/div>/', $html);
    }
}
