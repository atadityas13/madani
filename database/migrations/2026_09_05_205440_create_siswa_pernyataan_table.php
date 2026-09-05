<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siswa_pernyataan', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->string('versi_teks', 32);
            $table->text('teks_poin_1');
            $table->text('teks_poin_2');
            $table->boolean('setuju_poin_1')->default(false);
            $table->boolean('setuju_poin_2')->default(false);
            $table->string('nama_siswa');
            $table->string('nama_wali');
            $table->string('ttd_siswa_path');
            $table->string('ttd_wali_path');
            $table->timestamp('dikonfirmasi_at');
            $table->timestamps();

            $table->unique('siswa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa_pernyataan');
    }
};
