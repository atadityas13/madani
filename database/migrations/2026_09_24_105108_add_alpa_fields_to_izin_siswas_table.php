<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('izin_siswas', function (Blueprint $table) {
            $table->foreignId('dilaporkan_oleh')->nullable()->after('nama_wali')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('izin_siswas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dilaporkan_oleh');
        });
    }
};
