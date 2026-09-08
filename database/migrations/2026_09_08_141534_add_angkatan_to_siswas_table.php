<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            if (! Schema::hasColumn('siswas', 'angkatan')) {
                $table->string('angkatan', 10)->nullable()->after('status_keaktifan')->index();
            }
        });

        $rows = DB::table('rombel_siswas')
            ->join('rombels', 'rombels.id', '=', 'rombel_siswas.rombel_id')
            ->where('rombel_siswas.status', 'aktif')
            ->whereNotNull('rombels.tingkat')
            ->orderByDesc('rombel_siswas.id')
            ->get(['rombel_siswas.siswa_id', 'rombels.tingkat']);

        $seen = [];
        foreach ($rows as $row) {
            $siswaId = (string) $row->siswa_id;
            if (isset($seen[$siswaId])) {
                continue;
            }
            $seen[$siswaId] = true;
            $tingkat = strtoupper(trim((string) $row->tingkat));
            if (! in_array($tingkat, ['VII', 'VIII', 'IX'], true)) {
                continue;
            }
            DB::table('siswas')
                ->where('id', $siswaId)
                ->whereNull('angkatan')
                ->update(['angkatan' => $tingkat]);
        }
    }

    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            if (Schema::hasColumn('siswas', 'angkatan')) {
                $table->dropIndex(['angkatan']);
                $table->dropColumn('angkatan');
            }
        });
    }
};
