<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            if (Schema::hasColumn('siswas', 'kewarganegaraan')) {
                $table->dropColumn('kewarganegaraan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            if (! Schema::hasColumn('siswas', 'kewarganegaraan')) {
                $table->string('kewarganegaraan', 20)->nullable()->after('agama');
            }
        });
    }
};
