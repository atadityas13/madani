<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->boolean('punya_nisn')->default(true)->after('nisn');
            $table->boolean('punya_nik')->default(true)->after('nik');
            $table->string('kitas', 30)->nullable()->after('kewarganegaraan');
            $table->string('negara_asal', 80)->nullable()->after('kitas');
            $table->string('cita_cita_lainnya')->nullable()->after('cita_cita');
            $table->boolean('tidak_punya_hp')->default(false)->after('no_hp');
        });

        Schema::table('siswa_periodiks', function (Blueprint $table) {
            $table->boolean('pernah_tk_ra')->default(false)->after('pra_sekolah');
            $table->boolean('pernah_paud')->default(false)->after('pernah_tk_ra');
            $table->string('no_kks', 30)->nullable()->after('no_kip');
            $table->string('no_pkh', 30)->nullable()->after('no_kks');
            $table->string('kebutuhan_khusus_lainnya')->nullable()->after('kebutuhan_khusus');
            $table->string('disabilitas_lainnya')->nullable()->after('disabilitas');
            $table->date('tanggal_masuk')->nullable()->after('disabilitas_lainnya');
            $table->string('alasan_masuk', 50)->nullable()->after('tanggal_masuk');
            $table->string('npsn_asal', 16)->nullable()->after('alasan_masuk');
            $table->string('nama_sekolah_asal')->nullable()->after('npsn_asal');
        });

        Schema::table('orang_tuas', function (Blueprint $table) {
            $table->string('status_hidup', 30)->nullable()->after('status');
            $table->string('kewarganegaraan', 20)->nullable()->after('nik');
            $table->string('kitas', 30)->nullable()->after('kewarganegaraan');
            $table->string('negara_asal', 80)->nullable()->after('kitas');
            $table->boolean('tidak_punya_hp')->default(false)->after('no_hp');
            $table->string('domisili', 20)->nullable()->after('tidak_punya_hp');
            $table->string('status_tempat_tinggal')->nullable()->after('domisili');
            $table->string('kode_pos', 10)->nullable()->after('provinsi');
            $table->string('rt', 5)->nullable()->after('alamat');
            $table->string('rw', 5)->nullable()->after('rt');
        });

        Schema::table('prestasis', function (Blueprint $table) {
            $table->string('jenis', 50)->nullable()->after('nama');
        });

        Schema::table('pendidikan_lains', function (Blueprint $table) {
            $table->string('nama_lembaga')->nullable()->after('jenis');
            $table->unsignedTinyInteger('lama_tahun')->nullable()->after('nama_lembaga');
            $table->year('tahun')->nullable()->after('lama_tahun');
        });
    }

    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->dropColumn([
                'punya_nisn', 'punya_nik', 'kitas', 'negara_asal',
                'cita_cita_lainnya', 'tidak_punya_hp',
            ]);
        });

        Schema::table('siswa_periodiks', function (Blueprint $table) {
            $table->dropColumn([
                'pernah_tk_ra', 'pernah_paud', 'no_kks', 'no_pkh',
                'kebutuhan_khusus_lainnya', 'disabilitas_lainnya',
                'tanggal_masuk', 'alasan_masuk', 'npsn_asal', 'nama_sekolah_asal',
            ]);
        });

        Schema::table('orang_tuas', function (Blueprint $table) {
            $table->dropColumn([
                'status_hidup', 'kewarganegaraan', 'kitas', 'negara_asal',
                'tidak_punya_hp', 'domisili', 'status_tempat_tinggal',
                'kode_pos', 'rt', 'rw',
            ]);
        });

        Schema::table('prestasis', function (Blueprint $table) {
            $table->dropColumn('jenis');
        });

        Schema::table('pendidikan_lains', function (Blueprint $table) {
            $table->dropColumn(['nama_lembaga', 'lama_tahun', 'tahun']);
        });
    }
};
