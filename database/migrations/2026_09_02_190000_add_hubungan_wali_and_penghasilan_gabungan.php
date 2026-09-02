<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orang_tuas', function (Blueprint $table) {
            $table->string('hubungan', 80)->nullable();
        });

        Schema::table('siswa_periodiks', function (Blueprint $table) {
            $table->string('penghasilan_gabungan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orang_tuas', function (Blueprint $table) {
            $table->dropColumn('hubungan');
        });

        Schema::table('siswa_periodiks', function (Blueprint $table) {
            $table->dropColumn('penghasilan_gabungan');
        });
    }
};
