<?php

namespace App\Console\Commands;

use App\Models\Siswa;
use Illuminate\Console\Command;

class SetPasswordAwalSiswa extends Command
{
    protected $signature = 'siswa:set-password-awal';

    protected $description = 'Isi password awal siswa (tanggal lahir ddmmyyyy) untuk yang belum punya password';

    public function handle(): int
    {
        $jumlah = 0;
        $lewati = 0;

        Siswa::query()
            ->whereNull('password')
            ->whereNotNull('tanggal_lahir')
            ->orderBy('nama')
            ->each(function (Siswa $siswa) use (&$jumlah, &$lewati): void {
                $siswa->ensurePasswordAwal();

                if (! filled($siswa->password)) {
                    $lewati++;

                    return;
                }

                $siswa->save();
                $jumlah++;
            });

        $this->info("Password awal diisi untuk {$jumlah} siswa.");

        if ($lewati > 0) {
            $this->warn("{$lewati} siswa dilewati karena tanggal lahir tidak valid.");
        }

        return self::SUCCESS;
    }
}
