<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\JurnalPembelajaran;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardJurnalRankingTest extends TestCase
{
    use RefreshDatabase;

    private function buatGuruDenganJurnal(string $nama, string $nip, int $jumlah): User
    {
        Role::findOrCreate(Peran::GURU);

        $gtk = Gtk::query()->create([
            'nama' => $nama,
            'nip' => $nip,
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $user = User::factory()->create([
            'name' => $nama,
            'username' => $nip,
            'gtk_id' => $gtk->id,
            'is_aktif' => true,
        ]);
        $user->syncRoles([Peran::GURU]);

        for ($i = 0; $i < $jumlah; $i++) {
            JurnalPembelajaran::query()->create([
                'user_id' => $user->id,
                'kelas_id' => 1,
                'nama_kelas' => 'VII-A',
                'mapel_id' => 1,
                'nama_mapel' => 'Matematika',
                'tanggal' => now()->subDays($i)->toDateString(),
                'hari' => 'Senin',
                'jam_ke' => 1,
                'materi_pokok' => 'Materi '.$i,
            ]);
        }

        return $user;
    }

    public function test_dashboard_admin_menampilkan_peringkat_jurnal_terbanyak(): void
    {
        $this->seed();

        $this->buatGuruDenganJurnal('Guru Sedikit', '198001012005011001', 2);
        $this->buatGuruDenganJurnal('Guru Banyak', '198001012005011002', 5);
        $this->buatGuruDenganJurnal('Guru Sedang', '198001012005011003', 3);

        $admin = User::query()->where('username', 'admin')->first()
            ?? User::factory()->create(['username' => 'admin_rank', 'is_aktif' => true]);
        Role::findOrCreate(Peran::SUPERADMIN);
        $admin->syncRoles([Peran::SUPERADMIN]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Peringkat pengisian jurnal', false)
            ->assertSee('Jumlah entri', false)
            ->assertSee('Guru Banyak', false)
            ->assertSee('Guru Sedang', false)
            ->assertSee('Guru Sedikit', false)
            ->assertSeeInOrder(['Guru Banyak', 'Guru Sedang', 'Guru Sedikit']);
    }

    public function test_dashboard_wali_tidak_menampilkan_peringkat_jurnal(): void
    {
        $this->seed();
        Role::findOrCreate(Peran::WALI_KELAS);
        Role::findOrCreate(Peran::GURU);

        $this->buatGuruDenganJurnal('Guru Rahasia', '198001012005011009', 4);

        $gtk = Gtk::query()->create([
            'nama' => 'Wali Dashboard',
            'nip' => '198001012005011010',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);
        $wali = User::factory()->create([
            'username' => '198001012005011010',
            'gtk_id' => $gtk->id,
            'is_aktif' => true,
        ]);
        $wali->syncRoles([Peran::WALI_KELAS, Peran::GURU]);

        $this->actingAs($wali)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Peringkat pengisian jurnal', false)
            ->assertDontSee('Guru Rahasia', false);
    }
}
