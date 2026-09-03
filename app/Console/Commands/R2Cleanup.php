<?php

namespace App\Console\Commands;

use App\Models\Siswa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class R2Cleanup extends Command
{
    protected $signature = 'r2:cleanup {--dry : Tampilkan file yang akan dihapus tanpa menghapus}';

    protected $description = 'Hapus file R2 siswa nonaktif lebih dari 3 tahun';

    public function handle(): int
    {
        $dry = $this->option('dry');
        $batas = now()->subYears(3);
        $r2 = Storage::disk('r2');
        $cleaned = 0;

        $this->info($dry ? 'Mode dry-run — tidak ada file yang dihapus.' : 'Memulai cleanup R2...');
        $this->info("Batas tanggal nonaktif: {$batas->toDateString()}");

        Siswa::query()
            ->where('status_keaktifan', '!=', 'aktif')
            ->where('status_keaktifan', '!=', 'aktif_tanpa_rombel')
            ->whereNotNull('tanggal_nonaktif')
            ->where('tanggal_nonaktif', '<', $batas)
            ->with(['dokumens', 'prestasis', 'beasiswas'])
            ->chunkById(50, function ($siswas) use ($r2, $dry, &$cleaned) {
                foreach ($siswas as $siswa) {
                    $files = collect();

                    if ($siswa->foto) {
                        $files->push($siswa->foto);
                    }

                    foreach ($siswa->dokumens as $dokumen) {
                        if ($dokumen->path) {
                            $files->push($dokumen->path);
                        }
                    }

                    foreach ($siswa->prestasis as $prestasi) {
                        if ($prestasi->sertifikat_path) {
                            $files->push($prestasi->sertifikat_path);
                        }
                    }

                    foreach ($siswa->beasiswas as $beasiswa) {
                        if ($beasiswa->bukti_path) {
                            $files->push($beasiswa->bukti_path);
                        }
                    }

                    if ($files->isEmpty()) {
                        continue;
                    }

                    $this->line("Siswa {$siswa->nama} (nonaktif {$siswa->tanggal_nonaktif->toDateString()}): {$files->count()} file");

                    if ($dry) {
                        $files->each(fn ($f) => $this->line("  AKAN dihapus: {$f}"));
                        $cleaned += $files->count();

                        continue;
                    }

                    foreach ($files as $path) {
                        $r2->delete($path);
                        $this->line("  Dihapus: {$path}");
                    }

                    $siswa->update(['foto' => null]);
                    $siswa->dokumens()->update(['path' => null]);
                    $siswa->prestasis()->whereNotNull('sertifikat_path')->update(['sertifikat_path' => null]);
                    $siswa->beasiswas()->whereNotNull('bukti_path')->update(['bukti_path' => null]);

                    $cleaned += $files->count();
                }
            });

        $this->newLine();
        $this->info("Selesai. File dibersihkan: {$cleaned}");

        return self::SUCCESS;
    }
}
