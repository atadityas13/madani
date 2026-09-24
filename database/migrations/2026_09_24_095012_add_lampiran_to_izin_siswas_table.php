<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('izin_siswas', function (Blueprint $table) {
            $table->string('lampiran_path')->nullable()->after('ttd_wali_path');
            $table->string('jenis_bukti', 200)->nullable()->after('lampiran_path');
            $table->string('nama_wali')->nullable()->after('jenis_bukti');
        });
    }

    public function down(): void
    {
        Schema::table('izin_siswas', function (Blueprint $table) {
            $table->dropColumn(['lampiran_path', 'jenis_bukti', 'nama_wali']);
        });
    }
};
