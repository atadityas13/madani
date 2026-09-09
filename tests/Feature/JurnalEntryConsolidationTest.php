<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\JurnalPembelajaran;
use App\Models\User;
use App\Services\JurnalEntryConsolidationService;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JurnalEntryConsolidationTest extends TestCase
{
    use RefreshDatabase;

    private function buatGuru(string $nip = '198501012010011001'): User
    {
        Role::findOrCreate(Peran::GURU);
        $gtk = Gtk::query()->create([
            'nama' => 'Guru Gabung',
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

        return $user;
    }

    public function test_menggabungkan_jam_berurutan_jadi_satu_entri(): void
    {
        $user = $this->buatGuru();

        JurnalPembelajaran::query()->create([
            'user_id' => $user->id,
            'kelas_id' => 12,
            'nama_kelas' => '9A',
            'mapel_id' => 3,
            'nama_mapel' => 'Matematika',
            'tanggal' => '2026-08-01',
            'hari' => 'Sabtu',
            'jam_ke' => 1,
            'jam_list' => [1],
            'materi_pokok' => 'Aljabar',
            'ketercapaian' => 'tercapai',
            'source_simpatisans_id' => 501,
        ]);
        JurnalPembelajaran::query()->create([
            'user_id' => $user->id,
            'kelas_id' => 12,
            'nama_kelas' => '9A',
            'mapel_id' => 3,
            'nama_mapel' => 'Matematika',
            'tanggal' => '2026-08-01',
            'hari' => 'Sabtu',
            'jam_ke' => 2,
            'jam_list' => [2],
            'materi_pokok' => 'Aljabar',
            'ketercapaian' => 'tercapai',
            'source_simpatisans_id' => 502,
        ]);

        $hasil = app(JurnalEntryConsolidationService::class)->consolidate();

        $this->assertSame(1, $hasil['groups_merged']);
        $this->assertSame(1, $hasil['rows_removed']);
        $this->assertDatabaseCount('jurnal_pembelajarans', 1);

        $row = JurnalPembelajaran::query()->first();
        $this->assertSame([1, 2], $row->jam_list);
        $this->assertSame(1, $row->jam_ke);
        $this->assertSame('Aljabar', $row->materi_pokok);
    }

    public function test_tidak_menggabung_jika_materi_berbeda(): void
    {
        $user = $this->buatGuru('198501012010011002');

        JurnalPembelajaran::query()->create([
            'user_id' => $user->id,
            'kelas_id' => 1,
            'mapel_id' => 1,
            'tanggal' => '2026-08-02',
            'jam_ke' => 1,
            'materi_pokok' => 'Materi A',
            'ketercapaian' => 'tercapai',
        ]);
        JurnalPembelajaran::query()->create([
            'user_id' => $user->id,
            'kelas_id' => 1,
            'mapel_id' => 1,
            'tanggal' => '2026-08-02',
            'jam_ke' => 2,
            'materi_pokok' => 'Materi B',
            'ketercapaian' => 'tercapai',
        ]);

        $hasil = app(JurnalEntryConsolidationService::class)->consolidate();

        $this->assertSame(0, $hasil['groups_merged']);
        $this->assertDatabaseCount('jurnal_pembelajarans', 2);
    }

    public function test_import_simpatisans_otomatis_menggabung_jam_berurutan(): void
    {
        $user = $this->buatGuru('198501012010011003');

        $dump = sys_get_temp_dir().DIRECTORY_SEPARATOR.'jurnal_dump_merge.sql';
        File::put($dump, <<<SQL
INSERT INTO `users` (`id`, `username`, `password`) VALUES (21, '{$user->username}', 'hash');
INSERT INTO `jurnal_pembelajaran` (`id`, `user_id`, `kelas_id`, `nama_kelas`, `mapel_id`, `nama_mapel`, `tanggal`, `jam_ke`, `materi_pokok`, `ketercapaian`) VALUES
(701, 21, 12, '9A', 3, 'Matematika', '2026-08-05', 1, 'Aljabar', 'tercapai'),
(702, 21, 12, '9A', 3, 'Matematika', '2026-08-05', 2, 'Aljabar', 'tercapai');
SQL);

        $this->artisan('jurnal:import-from-simpatisans', [
            'simpatisans' => $dump,
        ])->assertSuccessful();

        $this->assertDatabaseCount('jurnal_pembelajarans', 1);
        $row = JurnalPembelajaran::query()->where('user_id', $user->id)->first();
        $this->assertSame([1, 2], $row->jam_list);
    }
}
