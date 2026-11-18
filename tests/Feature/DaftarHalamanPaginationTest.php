<?php

namespace Tests\Feature;

use App\Models\Notifikasi;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\SiswaMutasi;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\VendorJob;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DaftarHalamanPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengguna_memakai_paginasi_dan_menyimpan_pencarian(): void
    {
        $this->seed();

        for ($i = 1; $i <= 16; $i++) {
            User::factory()->create([
                'name' => sprintf('Pengguna Halaman %02d', $i),
                'username' => sprintf('pengguna%02d', $i),
            ]);
        }

        $admin = User::query()->where('username', 'admin')->first();

        $this->actingAs($admin)
            ->get(route('pengguna.index', ['q' => 'Pengguna Halaman']))
            ->assertOk()
            ->assertSee('Pengguna Halaman 01', false)
            ->assertDontSee('Pengguna Halaman 16', false)
            ->assertSee('Menampilkan', false)
            ->assertSee('Selanjutnya', false)
            ->assertSee('name="q"', false)
            ->assertDontSee('Pagination Navigation', false);

        $this->actingAs($admin)
            ->get(route('pengguna.index', ['q' => 'Pengguna Halaman', 'page' => 2]))
            ->assertOk()
            ->assertSee('Pengguna Halaman 16', false)
            ->assertDontSee('Pengguna Halaman 01', false)
            ->assertSee('Sebelumnya', false)
            ->assertSee('value="Pengguna Halaman"', false);

        $this->actingAs($admin)
            ->get(route('pengguna.index', ['per_page' => 'all']))
            ->assertOk()
            ->assertSee('Pengguna Halaman 01', false)
            ->assertSee('Pengguna Halaman 16', false);
    }

    public function test_mutasi_memakai_paginasi_dan_menyimpan_tab(): void
    {
        $this->seed();
        $tahun = TahunAjaran::aktif();
        $this->assertNotNull($tahun);

        $this->catatMasuk('Mutasi Lama', '9100000001', $tahun);

        for ($i = 1; $i <= 20; $i++) {
            $this->catatMasuk(sprintf('Mutasi Baru %02d', $i), sprintf('9100000%03d', $i + 1), $tahun);
        }

        $admin = User::query()->where('username', 'admin')->first();

        $this->actingAs($admin)
            ->get(route('mutasi.index', ['tab' => SiswaMutasi::JENIS_MASUK]))
            ->assertOk()
            ->assertSee('Mutasi Baru 20', false)
            ->assertDontSee('Mutasi Lama', false)
            ->assertSee('Menampilkan', false)
            ->assertSee('Selanjutnya', false);

        $this->actingAs($admin)
            ->get(route('mutasi.index', ['tab' => SiswaMutasi::JENIS_MASUK, 'page' => 2]))
            ->assertOk()
            ->assertSee('Mutasi Lama', false)
            ->assertDontSee('Mutasi Baru 20', false)
            ->assertSee('name="tab"', false)
            ->assertSee('value="masuk"', false);
    }

    public function test_setiap_halaman_daftar_menampilkan_kontrol_paginasi(): void
    {
        $this->seed();
        $admin = User::query()->where('username', 'admin')->first();
        $tahun = TahunAjaran::aktif();
        $rombel = Rombel::query()->create([
            'nama' => '1',
            'tingkat' => 'VII',
            'tahun_ajaran_id' => $tahun?->id,
        ]);
        $notifikasi = Notifikasi::query()->create([
            'judul' => 'Pengumuman tes',
            'isi' => 'Isi',
            'jenis' => Notifikasi::JENIS_PENGUMUMAN,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
            'is_active' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($admin);

        $halaman = [
            route('pengguna.index'),
            route('gtk.index'),
            route('gtk.monitoring'),
            route('siswa.index'),
            route('siswa.monitoring'),
            route('mutasi.index'),
            route('izin-siswa.index'),
            route('rekap-sia.index'),
            route('manajemen.vendor-jobs.index'),
            route('notifikasi.index'),
            route('notifikasi.media.index'),
            route('notifikasi.pembaca', $notifikasi),
            route('calendar-events.index'),
            route('app-menus.index'),
            route('tahun-ajaran.index'),
            route('rombel.index'),
            route('rombel.show', $rombel),
            route('tunjangan.jenis.index', 'skmt'),
            route('tunjangan.jenis.index', 'skakpt'),
        ];

        foreach ($halaman as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('Menampilkan', false)
                ->assertSee('name="per_page"', false);
        }
    }

    public function test_halaman_vendor_menampilkan_kontrol_paginasi(): void
    {
        $this->seed();
        Role::findOrCreate(Peran::VENDOR);

        $vendor = User::factory()->create([
            'username' => 'vendor-paginasi',
            'is_aktif' => true,
        ]);
        $vendor->syncRoles([Peran::VENDOR]);

        $job = VendorJob::query()->create([
            'nama' => 'Job Paginasi',
            'user_id' => $vendor->id,
            'status' => VendorJob::STATUS_AKTIF,
        ]);

        $this->actingAs($vendor)
            ->get(route('vendor.jobs.index'))
            ->assertOk()
            ->assertSee('Menampilkan', false)
            ->assertSee('name="per_page"', false);

        $this->actingAs($vendor)
            ->get(route('vendor.jobs.show', $job))
            ->assertOk()
            ->assertSee('Menampilkan', false)
            ->assertSee('name="per_page"', false);

        $this->actingAs($vendor)
            ->get(route('vendor.dashboard'))
            ->assertOk()
            ->assertSee('Menampilkan', false)
            ->assertSee('name="per_page"', false);
    }

    private function catatMasuk(string $nama, string $nisn, TahunAjaran $tahun): void
    {
        $siswa = Siswa::query()->create([
            'nama' => $nama,
            'nisn' => $nisn,
            'status_keaktifan' => 'aktif_tanpa_rombel',
            'angkatan' => 'VII',
        ]);

        SiswaMutasi::query()->create([
            'jenis' => SiswaMutasi::JENIS_MASUK,
            'siswa_id' => $siswa->id,
            'tanggal' => now()->toDateString(),
            'alasan' => 'Lainnya',
            'jenis_sekolah' => SiswaMutasi::SEKOLAH_UMUM,
            'nama_sekolah' => 'SMP Asal',
            'tahun_ajaran_id' => $tahun->id,
        ]);
    }
}
