<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perluas GTK untuk guru + tendik (field bersama + meta khusus).
 * Data Madani yang sudah ada tetap; merge Simpatisans by NIP di command terpisah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtks', function (Blueprint $table) {
            if (! Schema::hasColumn('gtks', 'jenis')) {
                $table->string('jenis', 20)->default('guru')->after('status')->index();
            }
            if (! Schema::hasColumn('gtks', 'gelar_depan')) {
                $table->string('gelar_depan', 50)->nullable()->after('nama');
            }
            if (! Schema::hasColumn('gtks', 'gelar_belakang')) {
                $table->string('gelar_belakang', 50)->nullable()->after('gelar_depan');
            }
            if (! Schema::hasColumn('gtks', 'tempat_lahir')) {
                $table->string('tempat_lahir', 100)->nullable()->after('jenis_kelamin');
            }
            if (! Schema::hasColumn('gtks', 'tanggal_lahir')) {
                $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
            }
            if (! Schema::hasColumn('gtks', 'agama')) {
                $table->string('agama', 30)->nullable()->after('tanggal_lahir');
            }
            if (! Schema::hasColumn('gtks', 'nomor_hp')) {
                $table->string('nomor_hp', 30)->nullable()->after('agama');
            }
            if (! Schema::hasColumn('gtks', 'email')) {
                $table->string('email', 120)->nullable()->after('nomor_hp');
            }
            if (! Schema::hasColumn('gtks', 'alamat')) {
                $table->text('alamat')->nullable()->after('email');
            }
            if (! Schema::hasColumn('gtks', 'jabatan')) {
                $table->string('jabatan', 100)->nullable()->after('alamat');
            }
            if (! Schema::hasColumn('gtks', 'golongan')) {
                $table->string('golongan', 50)->nullable()->after('jabatan');
            }
            if (! Schema::hasColumn('gtks', 'status_pegawai')) {
                $table->string('status_pegawai', 30)->nullable()->after('golongan');
            }
            if (! Schema::hasColumn('gtks', 'kode_internal')) {
                $table->string('kode_internal', 40)->nullable()->after('status_pegawai');
            }
            if (! Schema::hasColumn('gtks', 'duk')) {
                $table->string('duk', 40)->nullable()->after('kode_internal');
            }
            if (! Schema::hasColumn('gtks', 'foto_url')) {
                $table->string('foto_url', 500)->nullable()->after('duk');
            }
            if (! Schema::hasColumn('gtks', 'meta')) {
                // Field khusus per jenis (guru: sertifikasi/mapel; tendik: unit kerja, dll.)
                $table->json('meta')->nullable()->after('foto_url');
            }
            if (! Schema::hasColumn('gtks', 'simpatisans_guru_id')) {
                $table->unsignedBigInteger('simpatisans_guru_id')->nullable()->after('meta')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('gtks', function (Blueprint $table) {
            $columns = [
                'jenis',
                'gelar_depan',
                'gelar_belakang',
                'tempat_lahir',
                'tanggal_lahir',
                'agama',
                'nomor_hp',
                'email',
                'alamat',
                'jabatan',
                'golongan',
                'status_pegawai',
                'kode_internal',
                'duk',
                'foto_url',
                'meta',
                'simpatisans_guru_id',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('gtks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
