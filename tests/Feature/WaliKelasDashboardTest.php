<?php

namespace Tests\Feature;

use App\Models\AppMenu;
use App\Models\Gtk;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\Peran;
use Carbon\Carbon;
use Database\Seeders\AppMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WaliKelasDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function buatWaliDenganRombel(): array
    {
        $this->seed();
        Role::findOrCreate(Peran::WALI_KELAS);
        Role::findOrCreate(Peran::GURU);

        $gtk = Gtk::query()->create([
            'nama' => 'Wali Satu',
            'nip' => '198801012010011001',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $user = User::factory()->create([
            'name' => 'Wali Satu',
            'username' => '198801012010011001',
            'gtk_id' => $gtk->id,
            'is_aktif' => true,
        ]);
        $user->syncRoles([Peran::WALI_KELAS, Peran::GURU]);

        $tahun = TahunAjaran::aktif();
        $rombel = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => 'A',
            'gtk_id' => $gtk->id,
        ]);

        return [$user, $rombel];
    }

    private function tambahSiswa(Rombel $rombel, array $attrs = []): Siswa
    {
        $siswa = Siswa::query()->create(array_merge([
            'nama' => 'Siswa Uji',
            'nisn' => fake()->unique()->numerify('##########'),
            'jenis_kelamin' => 'L',
            'status_keaktifan' => 'aktif',
        ], $attrs));

        $rombel->siswas()->attach($siswa->id, ['status' => 'aktif']);

        return $siswa;
    }

    public function test_dashboard_hanya_siswa_rombel_wali(): void
    {
        [$wali, $rombel] = $this->buatWaliDenganRombel();

        Carbon::setTestNow(Carbon::parse('2026-09-09 08:54:32', config('app.timezone')));

        $this->tambahSiswa($rombel, [
            'nama' => 'Ani Kelas',
            'jenis_kelamin' => 'P',
            'first_login_at' => now()->subHour(),
            'last_login_at' => now()->subHour(),
        ]);
        $this->tambahSiswa($rombel, [
            'nama' => 'Budi Kelas',
            'jenis_kelamin' => 'L',
        ]);

        $tahun = TahunAjaran::aktif();
        $rombelLain = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => 'B',
        ]);
        $this->tambahSiswa($rombelLain, ['nama' => 'Citra Lain']);

        $this->actingAs($wali)
            ->get(route('talim.wali'))
            ->assertOk()
            ->assertSee('VII-A', false)
            ->assertSee('Ani Kelas', false)
            ->assertSee('Budi Kelas', false)
            ->assertDontSee('Citra Lain', false)
            ->assertSee('Jumlah siswa', false)
            ->assertSee('Laki-laki', false)
            ->assertSee('Perempuan', false)
            ->assertSee('Belum pernah login', false)
            ->assertSee('5 login siswa terakhir', false)
            ->assertSee('Rabu, 09-09-2026 pukul 07:54:32', false)
            ->assertSee('Siswa belum lengkap', false)
            ->assertSee('status_lengkap=belum_lengkap', false)
            ->assertSee('jenis_kelamin=L', false)
            ->assertSee('belum%5B0%5D=login', false)
            ->assertSee('status_lengkap=sudah_lengkap', false);
    }

    public function test_guru_bukan_wali_melihat_pesan_akses_ditolak(): void
    {
        $this->seed();
        Role::findOrCreate(Peran::GURU);
        $gtk = Gtk::query()->create([
            'nama' => 'Guru Biasa',
            'nip' => '198901012010011002',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);
        $guru = User::factory()->create([
            'username' => '198901012010011002',
            'gtk_id' => $gtk->id,
            'is_aktif' => true,
        ]);
        $guru->syncRoles([Peran::GURU]);

        $this->actingAs($guru)
            ->get(route('talim.wali'))
            ->assertOk()
            ->assertSee('Anda tidak memiliki akses wali kelas', false)
            ->assertSee('Silahkan menghubungi Admin jika anda adalah wali kelas', false);
    }

    public function test_menu_wali_tampil_untuk_semua_guru(): void
    {
        config(['app.url' => 'https://madani.mtsn11majalengka.sch.id']);
        (new AppMenuSeeder)->run();

        [$wali] = $this->buatWaliDenganRombel();
        Sanctum::actingAs($wali);
        $keysWali = collect($this->getJson('/api/v1/menus')->json('data'))->pluck('key');
        $this->assertTrue($keysWali->contains(AppMenu::KEY_WALI_KELAS));

        Role::findOrCreate(Peran::GURU);
        $gtk = Gtk::query()->create([
            'nama' => 'Guru Menu',
            'nip' => '199001012010011003',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);
        $guru = User::factory()->create([
            'username' => '199001012010011003',
            'gtk_id' => $gtk->id,
            'is_aktif' => true,
        ]);
        $guru->syncRoles([Peran::GURU]);

        Sanctum::actingAs($guru);
        $keysGuru = collect($this->getJson('/api/v1/menus')->json('data'))->pluck('key');
        $this->assertTrue($keysGuru->contains(AppMenu::KEY_WALI_KELAS));
    }

    public function test_guru_ditugaskan_di_rombel_boleh_akses_meski_tanpa_peran_wali(): void
    {
        $this->seed();
        Role::findOrCreate(Peran::GURU);

        $gtk = Gtk::query()->create([
            'nama' => 'Guru Wali Rombel',
            'nip' => '199201012010011005',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);
        $guru = User::factory()->create([
            'username' => '199201012010011005',
            'gtk_id' => $gtk->id,
            'is_aktif' => true,
        ]);
        $guru->syncRoles([Peran::GURU]);

        $tahun = TahunAjaran::aktif();
        $rombel = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VIII',
            'nama' => 'C',
            'gtk_id' => $gtk->id,
        ]);
        $this->tambahSiswa($rombel, ['nama' => 'Siswa Rombel C']);

        $this->actingAs($guru)
            ->get(route('talim.wali'))
            ->assertOk()
            ->assertSee('VIII-C', false)
            ->assertSee('Siswa Rombel C', false)
            ->assertDontSee('Anda tidak memiliki akses wali kelas', false);
    }

    public function test_empty_state_jika_belum_punya_rombel(): void
    {
        $this->seed();
        Role::findOrCreate(Peran::WALI_KELAS);
        $gtk = Gtk::query()->create([
            'nama' => 'Wali Kosong',
            'nip' => '199101012010011004',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);
        $user = User::factory()->create([
            'username' => '199101012010011004',
            'gtk_id' => $gtk->id,
            'is_aktif' => true,
        ]);
        $user->syncRoles([Peran::WALI_KELAS]);

        $this->actingAs($user)
            ->get(route('talim.wali'))
            ->assertOk()
            ->assertSee('Belum ada rombel', false);
    }
}
