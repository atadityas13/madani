<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beasiswas', function (Blueprint $table) {
            $table->string('nomor_rekening', 50)->nullable()->after('nominal');
            $table->string('bukti_path')->nullable()->after('nomor_rekening');
        });
    }

    public function down(): void
    {
        Schema::table('beasiswas', function (Blueprint $table) {
            $table->dropColumn(['nomor_rekening', 'bukti_path']);
        });
    }
};
