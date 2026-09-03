<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswa_periodiks', function (Blueprint $table) {
            $table->boolean('tidak_punya_kks')->default(false)->after('no_kks');
            $table->boolean('tidak_punya_pkh')->default(false)->after('no_pkh');
        });
    }

    public function down(): void
    {
        Schema::table('siswa_periodiks', function (Blueprint $table) {
            $table->dropColumn(['tidak_punya_kks', 'tidak_punya_pkh']);
        });
    }
};
