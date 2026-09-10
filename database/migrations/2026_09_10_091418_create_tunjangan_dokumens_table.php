<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tunjangan_dokumens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gtk_id')->constrained('gtks')->cascadeOnDelete();
            $table->string('jenis', 20);
            $table->string('slot_key', 64);
            $table->unsignedSmallInteger('tahun_anggaran')->nullable();
            $table->foreignId('tahun_ajaran_id')->nullable()->constrained('tahun_ajarans')->nullOnDelete();
            $table->unsignedTinyInteger('periode');
            $table->string('path');
            $table->string('nama_asli')->nullable();
            $table->timestamps();

            $table->unique(['gtk_id', 'slot_key']);
            $table->index(['jenis', 'tahun_anggaran']);
            $table->index(['jenis', 'tahun_ajaran_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tunjangan_dokumens');
    }
};
