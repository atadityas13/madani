<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siswa_mutasis', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 20); // masuk|keluar
            $table->foreignUuid('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('alasan');
            $table->string('jenis_sekolah', 20)->nullable(); // madrasah|umum; null untuk DO
            $table->string('nomor_dokumen_emis')->nullable();
            $table->string('nama_sekolah')->nullable(); // asal/tujuan; null untuk DO
            $table->foreignId('rombel_id')->nullable()->constrained('rombels')->nullOnDelete();
            $table->foreignId('tahun_ajaran_id')->nullable()->constrained('tahun_ajarans')->nullOnDelete();
            $table->string('nomor_surat')->nullable();
            $table->string('path_surat')->nullable();
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['jenis', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa_mutasis');
    }
};
