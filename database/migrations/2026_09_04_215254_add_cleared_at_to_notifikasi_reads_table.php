<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifikasi_reads', function (Blueprint $table) {
            $table->timestamp('cleared_at')->nullable()->after('read_at');
        });
    }

    public function down(): void
    {
        Schema::table('notifikasi_reads', function (Blueprint $table) {
            $table->dropColumn('cleared_at');
        });
    }
};
