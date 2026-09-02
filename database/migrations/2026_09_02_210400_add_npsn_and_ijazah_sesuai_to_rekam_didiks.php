<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekam_didiks', function (Blueprint $table) {
            $table->string('npsn', 16)->nullable()->after('nama_sd');
            $table->boolean('ijazah_sesuai')->default(false)->after('status_verval');
        });
    }

    public function down(): void
    {
        Schema::table('rekam_didiks', function (Blueprint $table) {
            $table->dropColumn(['npsn', 'ijazah_sesuai']);
        });
    }
};
