<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orang_tuas', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->string('peran', 10);
            $table->string('nama')->nullable();
            $table->string('nik', 16)->nullable();
            $table->string('status', 30)->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('pendidikan')->nullable();
            $table->string('pekerjaan')->nullable();
            $table->string('penghasilan')->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->string('alamat')->nullable();
            $table->string('desa')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kota')->nullable();
            $table->string('provinsi')->nullable();
            $table->boolean('sama_dengan_ayah')->default(false);
            $table->timestamps();

            $table->unique(['siswa_id', 'peran']);
        });

        Schema::create('siswa_periodiks', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->cascadeOnDelete();
            $table->string('tempat_tinggal')->nullable();
            $table->string('alamat')->nullable();
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('desa')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kota')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('kode_pos', 10)->nullable();
            $table->string('kode_wilayah', 16)->nullable();
            $table->string('koordinat')->nullable();
            $table->string('transportasi')->nullable();
            $table->string('jarak')->nullable();
            $table->string('waktu_tempuh')->nullable();
            $table->string('pembiaya')->nullable();
            $table->string('no_kk', 16)->nullable();
            $table->string('kepala_keluarga')->nullable();
            $table->string('no_kip', 30)->nullable();
            $table->string('pra_sekolah')->nullable();
            $table->json('imunisasi')->nullable();
            $table->json('kebutuhan_khusus')->nullable();
            $table->json('disabilitas')->nullable();
            $table->timestamps();

            $table->unique(['siswa_id', 'tahun_ajaran_id']);
        });

        Schema::create('rombel_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rombel_id')->constrained('rombels')->cascadeOnDelete();
            $table->foreignUuid('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->string('status', 20)->default('aktif');
            $table->timestamps();

            $table->unique(['rombel_id', 'siswa_id']);
        });

        Schema::create('dokumens', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->string('jenis', 30);
            $table->string('path');
            $table->string('nama_asli')->nullable();
            $table->timestamps();
        });

        Schema::create('beasiswas', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->year('tahun');
            $table->string('kategori');
            $table->string('nama');
            $table->string('instansi')->nullable();
            $table->string('jenis_instansi')->nullable();
            $table->unsignedSmallInteger('jangka_bulan')->nullable();
            $table->unsignedBigInteger('nominal')->nullable();
            $table->timestamps();
        });

        Schema::create('prestasis', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->string('nama');
            $table->string('tingkat')->nullable();
            $table->year('tahun')->nullable();
            $table->string('penyelenggara')->nullable();
            $table->string('sertifikat_path')->nullable();
            $table->timestamps();
        });

        Schema::create('pendidikan_lains', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->string('jenis')->nullable();
            $table->string('keterangan')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendidikan_lains');
        Schema::dropIfExists('prestasis');
        Schema::dropIfExists('beasiswas');
        Schema::dropIfExists('dokumens');
        Schema::dropIfExists('rombel_siswas');
        Schema::dropIfExists('siswa_periodiks');
        Schema::dropIfExists('orang_tuas');
    }
};
