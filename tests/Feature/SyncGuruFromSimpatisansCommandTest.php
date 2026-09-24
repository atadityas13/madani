<?php

namespace Tests\Feature;

use App\Models\Gtk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SyncGuruFromSimpatisansCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_mengisi_kolom_kompetensi_dari_mapel_id(): void
    {
        $dump = sys_get_temp_dir().DIRECTORY_SEPARATOR.'guru_sync_mapel_ijazah.sql';
        File::put($dump, <<<'SQL'
INSERT INTO `mapels` (`id`, `nama_mapel`) VALUES
(3, 'Matematika'),
(9, 'Bahasa Arab');
INSERT INTO `gurus` (`id`, `username`, `nama_guru`, `mapel_ijazah_id`, `mapel_sertifikasi_id`, `status_sertifikasi`, `is_bk`) VALUES
(12, '198001012005011001', 'Budi Santoso', 3, 9, 1, 0);
INSERT INTO `users` (`id`, `username`, `password`, `is_active`) VALUES
(1, '198001012005011001', '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUV', 1);
SQL);

        $this->artisan('guru:sync-from-simpatisans', [
            'simpatisans' => $dump,
            '--skip-passwords' => true,
        ])->assertSuccessful();

        $gtk = Gtk::query()->where('nip', '198001012005011001')->first();
        $this->assertNotNull($gtk);
        $this->assertSame('Matematika', $gtk->mapel_ijazah);
        $this->assertSame('Bahasa Arab', $gtk->mapel_sertifikasi);
        $this->assertTrue($gtk->status_sertifikasi);
        $this->assertFalse($gtk->is_bk);
    }
}
