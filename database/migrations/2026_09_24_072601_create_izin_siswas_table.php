<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('izin_siswas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('rombel_id')->nullable()->constrained('rombels')->nullOnDelete();
            $table->string('jenis', 10);
            $table->date('tanggal');
            $table->text('alasan');
            $table->boolean('pernyataan_disetujui')->default(false);
            $table->string('ttd_wali_path')->nullable();
            $table->string('status', 20)->default('aktif');
            $table->foreignId('dibatalkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dibatalkan_at')->nullable();
            $table->string('alasan_batal', 500)->nullable();
            $table->timestamps();

            $table->index(['tanggal', 'status']);
            $table->index(['siswa_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('izin_siswas');
    }
};
