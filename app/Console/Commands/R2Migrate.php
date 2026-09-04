<?php

namespace App\Console\Commands;

use App\Models\Beasiswa;
use App\Models\Dokumen;
use App\Models\Madrasah;
use App\Models\NotifMedia;
use App\Models\Prestasi;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class R2Migrate extends Command
{
    protected $signature = 'r2:migrate {--dry : Tampilkan file yang akan dipindahkan tanpa memindahkan}';

    protected $description = 'Migrasi file dari disk public lokal ke Cloudflare R2';

    public function handle(): int
    {
        $dry = $this->option('dry');
        $public = Storage::disk('public');
        $r2 = Storage::disk('r2');
        $moved = 0;
        $failed = 0;

        $this->info($dry ? 'Mode dry-run — tidak ada file yang dipindahkan.' : 'Memulai migrasi ke R2...');

        // 1. Foto siswa
        Siswa::query()->whereNotNull('foto')->chunkById(50, function ($siswas) use ($public, $r2, $dry, &$moved, &$failed) {
            foreach ($siswas as $siswa) {
                if ($this->migrateFile($public, $r2, $siswa->foto, $dry)) {
                    $moved++;
                } else {
                    $failed++;
                }
            }
        });

        // 2. Dokumen siswa
        Dokumen::query()->whereNotNull('path')->chunkById(50, function ($dokumens) use ($public, $r2, $dry, &$moved, &$failed) {
            foreach ($dokumens as $dokumen) {
                if ($this->migrateFile($public, $r2, $dokumen->path, $dry)) {
                    $moved++;
                } else {
                    $failed++;
                }
            }
        });

        // 3. Sertifikat prestasi
        Prestasi::query()->whereNotNull('sertifikat_path')->chunkById(50, function ($prestasis) use ($public, $r2, $dry, &$moved, &$failed) {
            foreach ($prestasis as $prestasi) {
                if ($this->migrateFile($public, $r2, $prestasi->sertifikat_path, $dry)) {
                    $moved++;
                } else {
                    $failed++;
                }
            }
        });

        // 4. Bukti beasiswa
        Beasiswa::query()->whereNotNull('bukti_path')->chunkById(50, function ($beasiswas) use ($public, $r2, $dry, &$moved, &$failed) {
            foreach ($beasiswas as $beasiswa) {
                if ($this->migrateFile($public, $r2, $beasiswa->bukti_path, $dry)) {
                    $moved++;
                } else {
                    $failed++;
                }
            }
        });

        // 5. Logo madrasah
        $madrasah = Madrasah::query()->first();
        if ($madrasah?->logo_path) {
            if ($this->migrateFile($public, $r2, $madrasah->logo_path, $dry)) {
                $moved++;
            } else {
                $failed++;
            }
        }

        // 6. Foto profil guru (user_photos)
        User::query()->whereNotNull('foto')->chunkById(50, function ($users) use ($public, $r2, $dry, &$moved, &$failed) {
            foreach ($users as $user) {
                $foto = (string) $user->foto;
                if (str_starts_with($foto, 'http://') || str_starts_with($foto, 'https://')) {
                    continue;
                }
                if ($this->migrateFile($public, $r2, $foto, $dry)) {
                    $moved++;
                } else {
                    $failed++;
                }
            }
        });

        // 7. Pustaka media notifikasi
        NotifMedia::query()->whereNotNull('path')->chunkById(50, function ($items) use ($public, $r2, $dry, &$moved, &$failed) {
            foreach ($items as $item) {
                if ($this->migrateFile($public, $r2, (string) $item->path, $dry)) {
                    $moved++;
                    if (! $dry) {
                        $item->forceFill(['url' => $r2->url($item->path)])->save();
                    }
                } else {
                    $failed++;
                }
            }
        });

        $this->newLine();
        $this->info("Selesai. Dipindahkan: {$moved}, Gagal/tidak ada: {$failed}");

        return self::SUCCESS;
    }

    private function migrateFile($public, $r2, string $path, bool $dry): bool
    {
        if (! $public->exists($path)) {
            $this->warn("  SKIP (tidak ada di lokal): {$path}");

            return false;
        }

        if ($r2->exists($path)) {
            $this->line("  SKIP (sudah ada di R2): {$path}");

            return true;
        }

        if ($dry) {
            $this->line("  AKAN dipindahkan: {$path}");

            return true;
        }

        $r2->put($path, $public->get($path));
        $this->line("  OK: {$path}");

        return true;
    }
}
