<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rombels', function (Blueprint $table) {
            if (Schema::hasColumn('rombels', 'ruangan')) {
                $table->dropColumn(['ruangan', 'jenis_rombel', 'waktu_mengajar', 'kurikulum']);
            }

            if (! Schema::hasColumn('rombels', 'source_simpatisans_kelas_id')) {
                $table->unsignedBigInteger('source_simpatisans_kelas_id')->nullable()->unique()->after('gtk_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rombels', function (Blueprint $table) {
            if (Schema::hasColumn('rombels', 'source_simpatisans_kelas_id')) {
                $table->dropUnique(['source_simpatisans_kelas_id']);
                $table->dropColumn('source_simpatisans_kelas_id');
            }

            if (! Schema::hasColumn('rombels', 'ruangan')) {
                $table->string('ruangan', 50)->nullable()->after('gtk_id');
                $table->string('jenis_rombel', 30)->nullable()->after('ruangan');
                $table->string('waktu_mengajar', 20)->nullable()->after('jenis_rombel');
                $table->string('kurikulum', 50)->nullable()->after('waktu_mengajar');
            }
        });
    }
};
