<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\JurnalPembelajaran;
use App\Models\User;
use App\Support\Peran;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GtkMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private function buatAkunGuru(array $gtkAttrs = [], array $userAttrs = []): User
    {
        Role::findOrCreate(Peran::GURU);

        $gtk = Gtk::query()->create(array_merge([
            'nama' => 'Budi Santoso',
            'gelar_depan' => 'Drs.',
            'nip' => '198001012005011001',
            'jenis' => 'guru',
            'status' => 'aktif',
        ], $gtkAttrs));

        $user = User::factory()->create(array_merge([
            'name' => $gtk->nama,
            'username' => $gtk->nip,
            'password' => 'password123',
            'is_aktif' => true,
            'gtk_id' => $gtk->id,
        ], $userAttrs));

        $user->syncRoles([Peran::GURU]);

        return $user->fresh()->load('gtk');
    }

    public function test_login_api_mencatat_first_dan_last_login_at(): void
    {
        $user = $this->buatAkunGuru();

        $this->assertNull($user->first_login_at);
        $this->assertNull($user->last_login_at);

        $this->postJson('/api/v1/guru/login', [
            'username' => '198001012005011001',
            'password' => 'password123',
        ])->assertOk();

        $user->refresh();
        $this->assertNotNull($user->first_login_at);
        $this->assertNotNull($user->last_login_at);
        $first = $user->first_login_at->copy();

        $this->travel(5)->minutes();

        $this->postJson('/api/v1/guru/login', [
            'username' => '198001012005011001',
            'password' => 'password123',
        ])->assertOk();

        $user->refresh();
        $this->assertTrue($user->first_login_at->equalTo($first));
        $this->assertTrue($user->last_login_at->greaterThan($first));
    }

    public function test_halaman_monitoring_menampilkan_format_terakhir_login(): void
    {
        $this->seed();

        Carbon::setTestNow(Carbon::parse('2026-09-09 08:54:32', config('app.timezone')));

        $ani = $this->buatAkunGuru([
            'nama' => 'Ani Wijaya',
            'gelar_depan' => null,
            'nip' => '198201012006012002',
        ], [
            'username' => '198201012006012002',
            'last_login_at' => now(),
            'first_login_at' => now(),
        ]);

        $jurnal1 = JurnalPembelajaran::query()->create([
            'user_id' => $ani->id,
            'kelas_id' => 1,
            'nama_kelas' => 'VII-A',
            'mapel_id' => 1,
            'nama_mapel' => 'Matematika',
            'tanggal' => '2026-09-08',
            'materi_pokok' => 'Materi uji',
        ]);
        $jurnal1->forceFill([
            'created_at' => Carbon::parse('2026-09-08 07:00:00', config('app.timezone')),
            'updated_at' => Carbon::parse('2026-09-08 07:00:00', config('app.timezone')),
        ])->saveQuietly();

        $jurnal2 = JurnalPembelajaran::query()->create([
            'user_id' => $ani->id,
            'kelas_id' => 1,
            'nama_kelas' => 'VII-A',
            'mapel_id' => 1,
            'nama_mapel' => 'Matematika',
            'tanggal' => '2026-09-09',
            'materi_pokok' => 'Materi uji 2',
        ]);
        $jurnal2->forceFill([
            'created_at' => Carbon::parse('2026-09-09 10:15:01', config('app.timezone')),
            'updated_at' => Carbon::parse('2026-09-09 10:15:01', config('app.timezone')),
        ])->saveQuietly();

        $this->buatAkunGuru([
            'nama' => 'Belum Login',
            'gelar_depan' => null,
            'nip' => '198301012007012003',
        ], [
            'username' => '198301012007012003',
        ]);

        $admin = User::query()->where('username', 'admin')->first();

        $this->actingAs($admin)
            ->get(route('gtk.monitoring'))
            ->assertOk()
            ->assertSee('Ani Wijaya', false)
            ->assertSee('Rabu, 09-09-2026 pukul 08:54:32', false)
            ->assertSee('Belum Login', false)
            ->assertSee('Belum pernah login', false)
            ->assertSee('Nama Guru', false)
            ->assertSee('Terakhir login Aplikasi pada', false)
            ->assertSee('Jumlah Jurnal tercatat', false)
            ->assertSee('Terakhir mengisi Jurnal', false)
            ->assertSee('Rabu, 09-09-2026 pukul 10:15:01', false)
            ->assertSee('Belum mengisi jurnal', false);
    }

    public function test_monitoring_hanya_untuk_admin(): void
    {
        $this->seed();
        Role::findOrCreate(Peran::WALI_KELAS);
        $wali = User::factory()->create(['is_aktif' => true]);
        $wali->syncRoles([Peran::WALI_KELAS]);

        $this->actingAs($wali)
            ->get(route('gtk.monitoring'))
            ->assertForbidden();
    }
}
