<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekam_didiks', function (Blueprint $table) {
            $columns = [
                'nik_kk',
                'nama_kk',
                'tempat_lahir_kk',
                'tanggal_lahir_kk',
                'jenis_kelamin_kk',
                'nama_ibu_kk',
                'nama_ayah_kk',
            ];

            $existing = array_values(array_filter(
                $columns,
                fn (string $column): bool => Schema::hasColumn('rekam_didiks', $column),
            ));

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });

        Schema::table('siswa_periodiks', function (Blueprint $table) {
            $columns = ['pra_sekolah', 'kode_wilayah'];

            $existing = array_values(array_filter(
                $columns,
                fn (string $column): bool => Schema::hasColumn('siswa_periodiks', $column),
            ));

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }

    public function down(): void
    {
        Schema::table('rekam_didiks', function (Blueprint $table) {
            if (! Schema::hasColumn('rekam_didiks', 'nik_kk')) {
                $table->string('nik_kk', 16)->nullable();
            }
            if (! Schema::hasColumn('rekam_didiks', 'nama_kk')) {
                $table->string('nama_kk')->nullable();
            }
            if (! Schema::hasColumn('rekam_didiks', 'tempat_lahir_kk')) {
                $table->string('tempat_lahir_kk')->nullable();
            }
            if (! Schema::hasColumn('rekam_didiks', 'tanggal_lahir_kk')) {
                $table->date('tanggal_lahir_kk')->nullable();
            }
            if (! Schema::hasColumn('rekam_didiks', 'jenis_kelamin_kk')) {
                $table->char('jenis_kelamin_kk', 1)->nullable();
            }
            if (! Schema::hasColumn('rekam_didiks', 'nama_ibu_kk')) {
                $table->string('nama_ibu_kk')->nullable();
            }
            if (! Schema::hasColumn('rekam_didiks', 'nama_ayah_kk')) {
                $table->string('nama_ayah_kk')->nullable();
            }
        });

        Schema::table('siswa_periodiks', function (Blueprint $table) {
            if (! Schema::hasColumn('siswa_periodiks', 'pra_sekolah')) {
                $table->string('pra_sekolah')->nullable();
            }
            if (! Schema::hasColumn('siswa_periodiks', 'kode_wilayah')) {
                $table->string('kode_wilayah', 16)->nullable();
            }
        });
    }
};
