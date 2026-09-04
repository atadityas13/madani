<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifikasis', function (Blueprint $table) {
            $table->string('gambar_url', 500)->nullable()->after('isi');
            $table->string('link', 500)->nullable()->after('gambar_url');
            $table->boolean('use_periode')->default(false)->after('audience_ids');
        });

        DB::table('notifikasis')
            ->where('jenis', 'periode')
            ->update([
                'jenis' => 'pengingat',
                'use_periode' => true,
            ]);
    }

    public function down(): void
    {
        DB::table('notifikasis')
            ->where('jenis', 'pengingat')
            ->where('use_periode', true)
            ->update(['jenis' => 'periode']);

        Schema::table('notifikasis', function (Blueprint $table) {
            $table->dropColumn(['gambar_url', 'link', 'use_periode']);
        });
    }
};
