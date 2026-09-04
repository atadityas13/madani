<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hapus jejak merge sekali-pakai Simpatisans (sudah tidak dipakai aplikasi).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('gtks', 'simpatisans_guru_id')) {
            return;
        }

        Schema::table('gtks', function (Blueprint $table) {
            foreach (Schema::getIndexes('gtks') as $index) {
                if (in_array('simpatisans_guru_id', $index['columns'], true) && ! ($index['primary'] ?? false)) {
                    $table->dropIndex($index['name']);
                }
            }
        });

        Schema::table('gtks', function (Blueprint $table) {
            $table->dropColumn('simpatisans_guru_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('gtks', 'simpatisans_guru_id')) {
            return;
        }

        Schema::table('gtks', function (Blueprint $table) {
            $table->unsignedBigInteger('simpatisans_guru_id')->nullable()->after('meta')->index();
        });
    }
};
