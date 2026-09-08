<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rombels', function (Blueprint $table) {
            $table->dropUnique(['source_simpatisans_kelas_id']);
            $table->unique(
                ['tahun_ajaran_id', 'source_simpatisans_kelas_id'],
                'rombels_tahun_ajaran_source_kelas_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('rombels', function (Blueprint $table) {
            $table->dropUnique('rombels_tahun_ajaran_source_kelas_unique');
            $table->unique('source_simpatisans_kelas_id');
        });
    }
};
