<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('siswa_pernyataan')) {
            return;
        }

        Schema::table('siswa_pernyataan', function (Blueprint $table) {
            if (Schema::hasColumn('siswa_pernyataan', 'pdf_path')) {
                $table->dropColumn('pdf_path');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('siswa_pernyataan')) {
            return;
        }

        Schema::table('siswa_pernyataan', function (Blueprint $table) {
            if (! Schema::hasColumn('siswa_pernyataan', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('ttd_wali_path');
            }
        });
    }
};
