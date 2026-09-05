<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\Gtk;
use App\Models\Notifikasi;
use App\Models\Siswa;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotifikasiAudienceFcmTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate(Peran::SUPERADMIN);

        $user = User::factory()->create(['is_aktif' => true]);
        $user->syncRoles([Peran::SUPERADMIN]);

        return $user;
    }

    private function gtkWithUser(string $nama, string $nip): array
    {
        Role::findOrCreate(Peran::GURU);

        $gtk = Gtk::query()->create([
            'nama' => $nama,
            'nip' => $nip,
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);
        $user = User::factory()->create([
            'username' => $nip,
            'gtk_id' => $gtk->id,
            'is_aktif' => true,
        ]);
        $user->syncRoles([Peran::GURU]);

        return [$gtk, $user];
    }

    public function test_index_lists_only_guru_and_siswa_with_fcm_token(): void
    {
        $admin = $this->admin();

        [, $userWithFcm] = $this->gtkWithUser('Guru Sudah Login', '198001012005011001');
        $this->gtkWithUser('Guru Belum Login', '198101012006012002');

        DeviceToken::query()->create([
            'tokenable_type' => User::class,
            'tokenable_id' => (string) $userWithFcm->id,
            'fcm_token' => 'fcm-guru-ok',
            'platform' => 'android',
            'last_seen_at' => now(),
        ]);

        $siswaWithFcm = Siswa::query()->create([
            'nama' => 'Siswa Sudah Login',
            'nisn' => '1111111111',
            'nik' => '3210010101120001',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-09-02',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif',
        ]);
        $siswaWithoutFcm = Siswa::query()->create([
            'nama' => 'Siswa Belum Login',
            'nisn' => '2222222222',
            'nik' => '3210010101120002',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-09-03',
            'jenis_kelamin' => 'P',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif',
        ]);

        DeviceToken::query()->create([
            'tokenable_type' => Siswa::class,
            'tokenable_id' => (string) $siswaWithFcm->id,
            'fcm_token' => 'fcm-siswa-ok',
            'platform' => 'android',
            'last_seen_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('notifikasi.index'))
            ->assertOk()
            ->assertSee('Guru Sudah Login', false)
            ->assertDontSee('Guru Belum Login', false)
            ->assertSee('Siswa Sudah Login', false)
            ->assertDontSee('Siswa Belum Login', false)
            ->assertSee('Hanya guru yang sudah pernah login Ta\'lim', false);
    }

    public function test_store_rejects_guru_tanpa_fcm_token(): void
    {
        Queue::fake();

        $admin = $this->admin();
        [$gtkWithoutFcm] = $this->gtkWithUser('Guru Belum Login', '198101012006012002');

        $this->actingAs($admin)
            ->from(route('notifikasi.index'))
            ->post(route('notifikasi.store'), [
                'judul' => 'Tes',
                'isi' => 'Isi notifikasi',
                'jenis' => Notifikasi::JENIS_NOTIFIKASI,
                'audience' => Notifikasi::AUDIENCE_GTK,
                'audience_ids' => [(string) $gtkWithoutFcm->id],
                'is_active' => '1',
            ])
            ->assertRedirect(route('notifikasi.index'))
            ->assertSessionHasErrors('audience_ids');

        $this->assertDatabaseCount('notifikasis', 0);
    }

    public function test_store_accepts_guru_dengan_fcm_token(): void
    {
        Queue::fake();

        $admin = $this->admin();
        [$gtk, $user] = $this->gtkWithUser('Guru Sudah Login', '198001012005011001');

        DeviceToken::query()->create([
            'tokenable_type' => User::class,
            'tokenable_id' => (string) $user->id,
            'fcm_token' => 'fcm-guru-ok',
            'platform' => 'android',
            'last_seen_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('notifikasi.store'), [
                'judul' => 'Tes FCM',
                'isi' => 'Isi notifikasi',
                'jenis' => Notifikasi::JENIS_NOTIFIKASI,
                'audience' => Notifikasi::AUDIENCE_GTK,
                'audience_ids' => [(string) $gtk->id],
                'is_active' => '1',
            ])
            ->assertRedirect(route('notifikasi.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('notifikasis', [
            'judul' => 'Tes FCM',
            'audience' => Notifikasi::AUDIENCE_GTK,
        ]);
    }

    public function test_store_rejects_siswa_tanpa_fcm_token(): void
    {
        Queue::fake();

        $admin = $this->admin();
        $siswa = Siswa::query()->create([
            'nama' => 'Siswa Belum Login',
            'nisn' => '3333333333',
            'nik' => '3210010101120003',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-09-04',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->from(route('notifikasi.index'))
            ->post(route('notifikasi.store'), [
                'judul' => 'Tes Siswa',
                'isi' => 'Isi notifikasi',
                'jenis' => Notifikasi::JENIS_NOTIFIKASI,
                'audience' => Notifikasi::AUDIENCE_SISWA,
                'audience_ids' => [(string) $siswa->id],
                'is_active' => '1',
            ])
            ->assertRedirect(route('notifikasi.index'))
            ->assertSessionHasErrors('audience_ids');

        $this->assertDatabaseCount('notifikasis', 0);
    }
}
