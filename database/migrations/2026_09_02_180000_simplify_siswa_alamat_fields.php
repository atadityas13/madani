<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->dropColumn(['kitas', 'negara_asal', 'cita_cita_lainnya']);
        });

        Schema::table('orang_tuas', function (Blueprint $table) {
            $table->dropColumn(['kewarganegaraan', 'kitas', 'negara_asal', 'domisili']);
            $table->string('blok', 80)->nullable();
        });

        Schema::table('siswa_periodiks', function (Blueprint $table) {
            $table->string('blok', 80)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->string('kitas', 30)->nullable();
            $table->string('negara_asal', 80)->nullable();
            $table->string('cita_cita_lainnya')->nullable();
        });

        Schema::table('orang_tuas', function (Blueprint $table) {
            $table->string('kewarganegaraan', 20)->nullable();
            $table->string('kitas', 30)->nullable();
            $table->string('negara_asal', 80)->nullable();
            $table->string('domisili', 20)->nullable();
            $table->dropColumn('blok');
        });

        Schema::table('siswa_periodiks', function (Blueprint $table) {
            $table->dropColumn('blok');
        });
    }
};
