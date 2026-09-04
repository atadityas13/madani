<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('notifikasis', 'deep_link')) {
            return;
        }

        Schema::table('notifikasis', function (Blueprint $table) {
            $table->dropColumn('deep_link');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('notifikasis', 'deep_link')) {
            return;
        }

        Schema::table('notifikasis', function (Blueprint $table) {
            $table->string('deep_link', 64)->nullable()->after('priority');
        });
    }
};
