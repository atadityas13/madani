<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tahun_ajarans', function (Blueprint $table) {
            $table->string('semester', 10)->default('ganjil')->after('nama');
        });

        Schema::create('gtks', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('nip', 30)->nullable();
            $table->string('nuptk', 20)->nullable();
            $table->string('jenis_kelamin', 1)->nullable();
            $table->string('status', 20)->default('aktif');
            $table->timestamps();
        });

        Schema::table('rombels', function (Blueprint $table) {
            $table->dropUnique(['tahun_ajaran_id', 'nama']);
            $table->foreignId('gtk_id')->nullable()->after('wali_kelas_id')->constrained('gtks')->nullOnDelete();
            $table->string('ruangan', 50)->nullable()->after('gtk_id');
            $table->string('jenis_rombel', 30)->nullable()->after('ruangan');
            $table->string('waktu_mengajar', 20)->nullable()->after('jenis_rombel');
            $table->string('kurikulum', 50)->nullable()->after('waktu_mengajar');
            $table->unique(['tahun_ajaran_id', 'tingkat', 'nama']);
        });
    }

    public function down(): void
    {
        Schema::table('rombels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gtk_id');
            $table->dropColumn(['ruangan', 'jenis_rombel', 'waktu_mengajar', 'kurikulum']);
        });

        Schema::dropIfExists('gtks');

        Schema::table('tahun_ajarans', function (Blueprint $table) {
            $table->dropColumn('semester');
        });
    }
};
