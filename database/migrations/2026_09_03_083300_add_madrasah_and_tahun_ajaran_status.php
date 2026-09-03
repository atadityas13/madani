<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('madrasahs', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('npsn', 20)->nullable();
            $table->string('nsm', 20)->nullable();
            $table->string('jenjang', 20)->nullable();
            $table->string('status', 20)->nullable();
            $table->string('akreditasi', 10)->nullable();
            $table->string('alamat')->nullable();
            $table->string('desa')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kota')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('kode_pos', 10)->nullable();
            $table->string('telepon', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('tahun_ajarans')) {
            Schema::table('tahun_ajarans', function (Blueprint $table) {
                if (! Schema::hasColumn('tahun_ajarans', 'status')) {
                    $table->string('status', 20)->default('belum_aktif')->after('is_aktif');
                }
            });

            $this->gabungkanNamaGanda();
            $this->isiStatusTahunAjaran();

            if (Schema::hasColumn('tahun_ajarans', 'semester')) {
                Schema::table('tahun_ajarans', function (Blueprint $table) {
                    $table->dropColumn('semester');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tahun_ajarans')) {
            if (! Schema::hasColumn('tahun_ajarans', 'semester')) {
                Schema::table('tahun_ajarans', function (Blueprint $table) {
                    $table->string('semester', 10)->default('ganjil')->after('nama');
                });
            }

            if (Schema::hasColumn('tahun_ajarans', 'status')) {
                Schema::table('tahun_ajarans', function (Blueprint $table) {
                    $table->dropColumn('status');
                });
            }
        }

        Schema::dropIfExists('madrasahs');
    }

    private function gabungkanNamaGanda(): void
    {
        $kelompok = DB::table('tahun_ajarans')->orderBy('id')->get()->groupBy('nama');

        foreach ($kelompok as $baris) {
            if ($baris->count() < 2) {
                continue;
            }

            $dipakai = $baris->firstWhere('is_aktif', true) ?? $baris->first();

            foreach ($baris as $item) {
                if ((int) $item->id === (int) $dipakai->id) {
                    continue;
                }

                DB::table('rombels')->where('tahun_ajaran_id', $item->id)->update(['tahun_ajaran_id' => $dipakai->id]);
                DB::table('siswa_periodiks')->where('tahun_ajaran_id', $item->id)->update(['tahun_ajaran_id' => $dipakai->id]);
                DB::table('tahun_ajarans')->where('id', $item->id)->delete();
            }
        }
    }

    private function isiStatusTahunAjaran(): void
    {
        foreach (DB::table('tahun_ajarans')->get() as $item) {
            $punyaData = DB::table('rombels')->where('tahun_ajaran_id', $item->id)->exists()
                || DB::table('siswa_periodiks')->where('tahun_ajaran_id', $item->id)->exists();

            $status = $item->is_aktif
                ? 'aktif'
                : ($punyaData ? 'arsip' : 'belum_aktif');

            DB::table('tahun_ajarans')->where('id', $item->id)->update(['status' => $status]);
        }
    }
};
