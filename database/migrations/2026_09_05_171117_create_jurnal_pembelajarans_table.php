<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_pembelajarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('kelas_id');
            $table->string('nama_kelas')->nullable();
            $table->unsignedBigInteger('mapel_id');
            $table->string('nama_mapel')->nullable();
            $table->date('tanggal');
            $table->string('hari', 20)->nullable();
            $table->unsignedSmallInteger('jam_ke')->default(0);
            $table->json('jam_list')->nullable();
            $table->unsignedBigInteger('jadwal_id')->nullable();
            $table->json('jadwal_ids')->nullable();
            $table->text('materi_pokok');
            $table->string('ketercapaian', 20)->default('tercapai');
            $table->text('penugasan_siswa')->nullable();
            $table->text('catatan_guru')->nullable();
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->string('semester_tipe', 20)->nullable();
            $table->string('semester_nama_tahun', 50)->nullable();
            $table->unsignedBigInteger('source_simpatisans_id')->nullable()->unique();
            $table->timestamps();

            $table->index(['user_id', 'tanggal']);
            $table->index(['user_id', 'kelas_id']);
            $table->index(['user_id', 'kelas_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_pembelajarans');
    }
};
