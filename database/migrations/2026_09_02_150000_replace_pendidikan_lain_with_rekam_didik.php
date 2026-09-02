<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('pendidikan_lains');

        Schema::create('rekam_didiks', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('siswa_id')->unique()->constrained('siswas')->cascadeOnDelete();

            $table->string('nik_kk', 16)->nullable();
            $table->string('nama_kk')->nullable();
            $table->string('tempat_lahir_kk')->nullable();
            $table->date('tanggal_lahir_kk')->nullable();
            $table->char('jenis_kelamin_kk', 1)->nullable();
            $table->string('nama_ibu_kk')->nullable();
            $table->string('nama_ayah_kk')->nullable();

            $table->string('nama_ijazah')->nullable();
            $table->string('tempat_lahir_ijazah')->nullable();
            $table->date('tanggal_lahir_ijazah')->nullable();
            $table->char('jenis_kelamin_ijazah', 1)->nullable();
            $table->string('nama_ayah_ijazah')->nullable();

            $table->string('nama_sd')->nullable();
            $table->string('tahun_ajaran_kelulusan', 20)->nullable();
            $table->string('nip_kepala_sekolah', 30)->nullable();
            $table->string('nama_kepala_sekolah')->nullable();
            $table->string('nomor_seri_ijazah', 50)->nullable();
            $table->date('tanggal_terbit_ijazah')->nullable();

            $table->string('status_verval', 20)->default('belum');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekam_didiks');

        Schema::create('pendidikan_lains', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->string('jenis')->nullable();
            $table->string('nama_lembaga')->nullable();
            $table->unsignedTinyInteger('lama_tahun')->nullable();
            $table->year('tahun')->nullable();
            $table->string('keterangan')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }
};
