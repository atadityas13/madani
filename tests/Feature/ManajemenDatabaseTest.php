<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\JurnalPembelajaran;
use App\Models\Madrasah;
use App\Models\Notifikasi;
use App\Models\PeriodePendataan;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ManajemenDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_superadmin_sees_manajemen_menu(): void
    {
        $this->seed();

        $superadmin = User::query()->where('username', 'admin')->first();

        $this->actingAs($superadmin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Manajemen', false)
            ->assertSee('Database', false)
            ->assertSee(route('manajemen.database'), false);

        Role::findOrCreate(Peran::ADMIN);
        $admin = User::factory()->create(['is_aktif' => true]);
        $admin->syncRoles([Peran::ADMIN]);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('>Manajemen<', false)
            ->assertDontSee(route('manajemen.database'), false);
    }

    public function test_database_page_is_superadmin_only(): void
    {
        $this->seed();

        $superadmin = User::query()->where('username', 'admin')->first();

        $this->actingAs($superadmin)
            ->get(route('manajemen.database'))
            ->assertOk()
            ->assertSee('Data siswa', false)
            ->assertSee('GTK', false)
            ->assertSee('Menu / app settings', false);

        Role::findOrCreate(Peran::ADMIN);
        $admin = User::factory()->create(['is_aktif' => true]);
        $admin->syncRoles([Peran::ADMIN]);

        $this->actingAs($admin)
            ->get(route('manajemen.database'))
            ->assertForbidden();
    }

    public function test_kosongkan_siswa_removes_siswa_but_keeps_superadmin(): void
    {
        Storage::fake('r2');
        $this->seed();

        $siswa = Siswa::query()->create([
            'nama' => 'Siswa Wipe',
            'nisn' => '1234567890',
            'status_keaktifan' => 'aktif',
        ]);
        $siswa->createToken('talim');

        $superadmin = User::query()->where('username', 'admin')->first();

        $this->actingAs($superadmin)
            ->post(route('manajemen.database.kosongkan', 'siswa'))
            ->assertRedirect(route('manajemen.database'));

        $this->assertSame(0, Siswa::withTrashed()->count());
        $this->assertDatabaseHas('users', ['id' => $superadmin->id]);
    }

    public function test_kosongkan_rombel_and_periode(): void
    {
        $this->seed();

        $tahun = TahunAjaran::aktif();
        $rombel = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => 'A',
        ]);
        PeriodePendataan::query()->create([
            'judul' => 'Uji',
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $superadmin = User::query()->where('username', 'admin')->first();

        $this->actingAs($superadmin)
            ->post(route('manajemen.database.kosongkan', 'rombel'))
            ->assertRedirect(route('manajemen.database'));

        $this->assertDatabaseMissing('rombels', ['id' => $rombel->id]);
        $this->assertNotNull(TahunAjaran::aktif());

        $this->actingAs($superadmin)
            ->post(route('manajemen.database.kosongkan', 'periode-pendataan'))
            ->assertRedirect(route('manajemen.database'));

        $this->assertSame(0, PeriodePendataan::query()->count());
    }

    public function test_kosongkan_identitas_and_notifikasi(): void
    {
        Storage::fake('r2');
        $this->seed();

        $madrasah = Madrasah::saatIni();
        $madrasah->update(['nama' => 'Nama Lama', 'email' => 'lama@example.com']);

        Notifikasi::query()->create([
            'judul' => 'Tes',
            'isi' => 'Isi',
            'audience' => 'semua',
            'is_active' => true,
            'created_by' => User::query()->where('username', 'admin')->value('id'),
        ]);

        $superadmin = User::query()->where('username', 'admin')->first();

        $this->actingAs($superadmin)
            ->post(route('manajemen.database.kosongkan', 'identitas'))
            ->assertRedirect(route('manajemen.database'));

        $this->assertSame('', $madrasah->fresh()->nama);
        $this->assertNull($madrasah->fresh()->email);

        $this->actingAs($superadmin)
            ->post(route('manajemen.database.kosongkan', 'notifikasi'))
            ->assertRedirect(route('manajemen.database'));

        $this->assertSame(0, Notifikasi::query()->count());
    }

    public function test_kosongkan_tahun_ajaran_cascades_rombel(): void
    {
        $this->seed();

        $tahun = TahunAjaran::aktif();
        Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => 'Z',
        ]);

        $superadmin = User::query()->where('username', 'admin')->first();

        $this->actingAs($superadmin)
            ->post(route('manajemen.database.kosongkan', 'tahun-ajaran'))
            ->assertRedirect(route('manajemen.database'));

        $this->assertSame(0, TahunAjaran::query()->count());
        $this->assertSame(0, Rombel::query()->count());
    }

    public function test_kosongkan_gtk_keeps_superadmin_user(): void
    {
        $this->seed();

        $gtk = Gtk::query()->create([
            'nama' => 'Guru Wipe',
            'status' => 'aktif',
        ]);

        $superadmin = User::query()->where('username', 'admin')->first();

        $this->actingAs($superadmin)
            ->post(route('manajemen.database.kosongkan', 'gtk'))
            ->assertRedirect(route('manajemen.database'));

        $this->assertSame(0, Gtk::query()->count());
        $this->assertDatabaseHas('users', ['id' => $superadmin->id]);
        $this->assertNull($gtk->fresh());
    }

    public function test_kosongkan_jurnal(): void
    {
        $this->seed();

        $user = User::query()->where('username', 'admin')->first();
        JurnalPembelajaran::query()->create([
            'user_id' => $user->id,
            'kelas_id' => 1,
            'nama_kelas' => 'VII-A',
            'mapel_id' => 1,
            'nama_mapel' => 'Matematika',
            'tanggal' => now()->toDateString(),
            'materi_pokok' => 'Materi uji',
        ]);

        $this->actingAs($user)
            ->post(route('manajemen.database.kosongkan', 'jurnal'))
            ->assertRedirect(route('manajemen.database'));

        $this->assertSame(0, JurnalPembelajaran::query()->count());
    }
}
