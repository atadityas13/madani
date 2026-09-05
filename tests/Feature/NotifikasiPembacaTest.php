<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\Notifikasi;
use App\Models\NotifikasiRead;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotifikasiPembacaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate(Peran::SUPERADMIN);

        $user = User::factory()->create(['is_aktif' => true]);
        $user->syncRoles([Peran::SUPERADMIN]);

        return $user;
    }

    private function notifikasi(): Notifikasi
    {
        return Notifikasi::query()->create([
            'judul' => 'Pengumuman tes',
            'isi' => 'Isi',
            'jenis' => Notifikasi::JENIS_PENGUMUMAN,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
            'is_active' => true,
            'published_at' => now(),
        ]);
    }

    private function guruReader(string $nama = 'Guru Pembaca'): User
    {
        Role::findOrCreate(Peran::GURU);

        $gtk = Gtk::query()->create([
            'nama' => $nama,
            'nip' => '198001012005011001',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $user = User::factory()->create([
            'username' => '198001012005011001',
            'gtk_id' => $gtk->id,
            'is_aktif' => true,
        ]);
        $user->syncRoles([Peran::GURU]);

        return $user;
    }

    private function siswaReader(string $nama, string $nisn): Siswa
    {
        return Siswa::query()->create([
            'nama' => $nama,
            'nisn' => $nisn,
            'nik' => '321001010112'.substr($nisn, -4),
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-09-02',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif',
        ]);
    }

    public function test_pembaca_page_lists_all_readers_by_default(): void
    {
        $admin = $this->admin();
        $item = $this->notifikasi();
        $guru = $this->guruReader();
        $siswa = $this->siswaReader('Siswa Pembaca', '1111111111');

        NotifikasiRead::query()->create([
            'notifikasi_id' => $item->id,
            'reader_type' => User::class,
            'reader_id' => (string) $guru->id,
            'read_at' => now()->subMinute(),
        ]);
        NotifikasiRead::query()->create([
            'notifikasi_id' => $item->id,
            'reader_type' => Siswa::class,
            'reader_id' => (string) $siswa->id,
            'read_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('notifikasi.pembaca', $item))
            ->assertOk()
            ->assertSee('Guru Pembaca', false)
            ->assertSee('Siswa Pembaca', false)
            ->assertSee('Semua (2)', false);
    }

    public function test_filter_guru_hides_siswa_readers(): void
    {
        $admin = $this->admin();
        $item = $this->notifikasi();
        $guru = $this->guruReader();
        $siswa = $this->siswaReader('Siswa Pembaca', '1111111111');

        NotifikasiRead::query()->create([
            'notifikasi_id' => $item->id,
            'reader_type' => User::class,
            'reader_id' => (string) $guru->id,
            'read_at' => now(),
        ]);
        NotifikasiRead::query()->create([
            'notifikasi_id' => $item->id,
            'reader_type' => Siswa::class,
            'reader_id' => (string) $siswa->id,
            'read_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('notifikasi.pembaca', ['notifikasi' => $item, 'tipe' => 'guru']))
            ->assertOk()
            ->assertSee('Guru Pembaca', false)
            ->assertDontSee('Siswa Pembaca', false);
    }

    public function test_filter_siswa_by_rombel_only_shows_members(): void
    {
        $admin = $this->admin();
        $item = $this->notifikasi();

        $tahun = TahunAjaran::query()->create([
            'nama' => '2025/2026',
            'tanggal_mulai' => '2025-07-01',
            'tanggal_selesai' => '2026-06-30',
            'is_aktif' => true,
            'status' => TahunAjaran::STATUS_AKTIF,
        ]);
        $rombelA = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => 'A',
        ]);
        $rombelB = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => 'B',
        ]);

        $siswaA = $this->siswaReader('Siswa Rombel A', '1111111111');
        $siswaB = $this->siswaReader('Siswa Rombel B', '2222222222');
        $rombelA->siswas()->attach($siswaA->id, ['status' => 'aktif']);
        $rombelB->siswas()->attach($siswaB->id, ['status' => 'aktif']);

        NotifikasiRead::query()->create([
            'notifikasi_id' => $item->id,
            'reader_type' => Siswa::class,
            'reader_id' => (string) $siswaA->id,
            'read_at' => now(),
        ]);
        NotifikasiRead::query()->create([
            'notifikasi_id' => $item->id,
            'reader_type' => Siswa::class,
            'reader_id' => (string) $siswaB->id,
            'read_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('notifikasi.pembaca', [
                'notifikasi' => $item,
                'tipe' => 'siswa',
                'rombel_id' => $rombelA->id,
            ]))
            ->assertOk()
            ->assertSee('Siswa Rombel A', false)
            ->assertDontSee('Siswa Rombel B', false);
    }

    public function test_guest_is_redirected_from_pembaca_page(): void
    {
        $item = $this->notifikasi();

        $this->get(route('notifikasi.pembaca', $item))
            ->assertRedirect(route('login'));
    }
}
