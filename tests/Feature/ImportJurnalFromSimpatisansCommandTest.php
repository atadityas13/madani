<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\JurnalPembelajaran;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImportJurnalFromSimpatisansCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_is_idempotent_by_source_id(): void
    {
        Role::findOrCreate(Peran::GURU);
        $gtk = Gtk::query()->create([
            'nama' => 'Budi',
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

        $dump = sys_get_temp_dir().DIRECTORY_SEPARATOR.'jurnal_dump_test.sql';
        File::put($dump, <<<'SQL'
INSERT INTO `users` (`id`, `username`, `password`) VALUES (7, '198001012005011001', 'hash');
INSERT INTO `jurnal_pembelajaran` (`id`, `user_id`, `kelas_id`, `nama_kelas`, `mapel_id`, `nama_mapel`, `tanggal`, `jam_ke`, `jam_list`, `jadwal_ids`, `materi_pokok`, `ketercapaian`) VALUES
(55, 7, 12, '9A', 3, 'Matematika', '2026-08-01', 1, '[1,2]', '[10,11]', 'Materi A', 'tercapai');
SQL);

        $this->artisan('jurnal:import-from-simpatisans', [
            'simpatisans' => $dump,
        ])->assertSuccessful();

        $this->assertDatabaseCount('jurnal_pembelajarans', 1);
        $this->assertDatabaseHas('jurnal_pembelajarans', [
            'user_id' => $user->id,
            'source_simpatisans_id' => 55,
            'materi_pokok' => 'Materi A',
        ]);

        File::put($dump, <<<'SQL'
INSERT INTO `users` (`id`, `username`, `password`) VALUES (7, '198001012005011001', 'hash');
INSERT INTO `jurnal_pembelajaran` (`id`, `user_id`, `kelas_id`, `nama_kelas`, `mapel_id`, `nama_mapel`, `tanggal`, `jam_ke`, `jam_list`, `jadwal_ids`, `materi_pokok`, `ketercapaian`) VALUES
(55, 7, 12, '9A', 3, 'Matematika', '2026-08-01', 1, '[1,2]', '[10,11]', 'Materi A diperbarui', 'belum');
SQL);

        $this->artisan('jurnal:import-from-simpatisans', [
            'simpatisans' => $dump,
        ])->assertSuccessful();

        $this->assertDatabaseCount('jurnal_pembelajarans', 1);
        $this->assertSame('Materi A diperbarui', JurnalPembelajaran::query()->first()->materi_pokok);
    }

    public function test_import_resolves_guru_id_and_enriches_nama(): void
    {
        Role::findOrCreate(Peran::GURU);
        $gtk = Gtk::query()->create([
            'nama' => 'Siti',
            'nip' => '197901012006012001',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);
        $user = User::factory()->create([
            'username' => '197901012006012001',
            'gtk_id' => $gtk->id,
            'is_aktif' => true,
        ]);
        $user->syncRoles([Peran::GURU]);

        $dump = sys_get_temp_dir().DIRECTORY_SEPARATOR.'jurnal_dump_guru_id_test.sql';
        File::put($dump, <<<'SQL'
INSERT INTO `gurus` (`id`, `username`, `nama`) VALUES (3, '197901012006012001', 'Siti');
INSERT INTO `kelas` (`id`, `nama_kelas`) VALUES (12, '9A');
INSERT INTO `mapels` (`id`, `nama_mapel`) VALUES (3, 'Matematika');
INSERT INTO `jurnal_pembelajaran` (`id`, `guru_id`, `kelas_id`, `mapel_id`, `tanggal`, `jam_ke`, `jam_list`, `jadwal_ids`, `materi_pokok`, `ketercapaian`) VALUES
(77, 3, 12, 3, '2026-08-02', 2, '[2]', '[20]', 'Materi B', 'tercapai');
SQL);

        $this->artisan('jurnal:import-from-simpatisans', [
            'simpatisans' => $dump,
        ])->assertSuccessful();

        $this->assertDatabaseHas('jurnal_pembelajarans', [
            'user_id' => $user->id,
            'source_simpatisans_id' => 77,
            'nama_kelas' => '9A',
            'nama_mapel' => 'Matematika',
            'materi_pokok' => 'Materi B',
        ]);
    }

    public function test_dry_run_does_not_write(): void
    {
        Role::findOrCreate(Peran::GURU);
        $gtk = Gtk::query()->create([
            'nama' => 'Ani',
            'nip' => '198201012007012001',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);
        $user = User::factory()->create([
            'username' => '198201012007012001',
            'gtk_id' => $gtk->id,
            'is_aktif' => true,
        ]);
        $user->syncRoles([Peran::GURU]);

        $dump = sys_get_temp_dir().DIRECTORY_SEPARATOR.'jurnal_dump_dry_run.sql';
        File::put($dump, <<<'SQL'
INSERT INTO `users` (`id`, `username`, `password`) VALUES (9, '198201012007012001', 'hash');
INSERT INTO `jurnal_pembelajaran` (`id`, `user_id`, `kelas_id`, `nama_kelas`, `mapel_id`, `nama_mapel`, `tanggal`, `jam_ke`, `materi_pokok`, `ketercapaian`) VALUES
(88, 9, 1, '7A', 1, 'IPA', '2026-08-03', 1, 'Materi C', 'tercapai');
SQL);

        $this->artisan('jurnal:import-from-simpatisans', [
            'simpatisans' => $dump,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertDatabaseCount('jurnal_pembelajarans', 0);
    }
}
