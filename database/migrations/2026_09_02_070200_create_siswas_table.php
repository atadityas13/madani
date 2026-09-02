<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siswas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nisn', 10)->nullable()->unique();
            $table->string('nik', 16)->nullable()->unique();
            $table->string('nism', 18)->nullable();
            $table->string('nis', 20)->nullable();
            $table->string('nama');
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->char('jenis_kelamin', 1)->nullable();
            $table->string('agama', 30)->nullable();
            $table->string('kewarganegaraan', 20)->nullable();
            $table->unsignedTinyInteger('anak_ke')->nullable();
            $table->unsignedTinyInteger('jumlah_saudara')->nullable();
            $table->string('cita_cita')->nullable();
            $table->string('hobi')->nullable();
            $table->string('email')->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->string('foto')->nullable();
            $table->string('status_keaktifan', 30)->default('aktif_tanpa_rombel');
            $table->date('tanggal_nonaktif')->nullable();
            $table->string('alasan_nonaktif')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswas');
    }
};
