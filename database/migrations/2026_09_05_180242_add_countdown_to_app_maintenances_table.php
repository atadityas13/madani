<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_maintenances', function (Blueprint $table) {
            $table->boolean('show_countdown')->default(false)->after('message');
            $table->timestamp('ends_at')->nullable()->after('show_countdown');
        });
    }

    public function down(): void
    {
        Schema::table('app_maintenances', function (Blueprint $table) {
            $table->dropColumn(['show_countdown', 'ends_at']);
        });
    }
};
