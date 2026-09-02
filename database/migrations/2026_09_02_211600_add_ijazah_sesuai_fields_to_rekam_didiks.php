<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekam_didiks', function (Blueprint $table) {
            $table->json('ijazah_sesuai_fields')->nullable()->after('ijazah_sesuai');
        });
    }

    public function down(): void
    {
        Schema::table('rekam_didiks', function (Blueprint $table) {
            $table->dropColumn('ijazah_sesuai_fields');
        });
    }
};
