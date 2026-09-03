<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->boolean('tidak_punya_email')->default(false)->after('email');
        });

        Schema::table('siswa_periodiks', function (Blueprint $table) {
            $table->boolean('tidak_punya_kip')->default(false)->after('no_kip');
        });

        Schema::create('pengajuan_perubahan_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->string('field', 40);
            $table->text('nilai_lama')->nullable();
            $table->text('nilai_baru');
            $table->string('alasan', 500);
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_perubahan_siswas');

        Schema::table('siswa_periodiks', function (Blueprint $table) {
            $table->dropColumn('tidak_punya_kip');
        });

        Schema::table('siswas', function (Blueprint $table) {
            $table->dropColumn('tidak_punya_email');
        });
    }
};
