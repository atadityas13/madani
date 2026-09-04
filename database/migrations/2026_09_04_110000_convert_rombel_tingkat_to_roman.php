<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $map = [
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            '7' => 'VII',
            '8' => 'VIII',
            '9' => 'IX',
        ];

        $hadTingkatUnique = $this->dropTingkatUniqueIfPresent();

        Schema::table('rombels', function (Blueprint $table) {
            $table->string('tingkat_romawi', 10)->nullable()->after('tingkat');
        });

        foreach (DB::table('rombels')->get(['id', 'tingkat']) as $rombel) {
            $key = $rombel->tingkat;
            $value = $map[$key] ?? $map[(int) $key] ?? match (strtoupper((string) $key)) {
                'VII', 'VIII', 'IX' => strtoupper((string) $key),
                default => 'VII',
            };

            DB::table('rombels')->where('id', $rombel->id)->update([
                'tingkat_romawi' => $value,
            ]);
        }

        Schema::table('rombels', function (Blueprint $table) {
            $table->dropColumn('tingkat');
        });

        Schema::table('rombels', function (Blueprint $table) {
            $table->string('tingkat', 10)->default('VII')->after('tahun_ajaran_id');
        });

        foreach (DB::table('rombels')->get(['id', 'tingkat_romawi']) as $rombel) {
            DB::table('rombels')->where('id', $rombel->id)->update([
                'tingkat' => $rombel->tingkat_romawi ?: 'VII',
            ]);
        }

        Schema::table('rombels', function (Blueprint $table) {
            $table->dropColumn('tingkat_romawi');
        });

        if ($hadTingkatUnique) {
            Schema::table('rombels', function (Blueprint $table) {
                $table->unique(['tahun_ajaran_id', 'tingkat', 'nama']);
            });
        }
    }

    public function down(): void
    {
        $map = [
            'VII' => 7,
            'VIII' => 8,
            'IX' => 9,
        ];

        $hadTingkatUnique = $this->dropTingkatUniqueIfPresent();

        Schema::table('rombels', function (Blueprint $table) {
            $table->unsignedTinyInteger('tingkat_angka')->nullable()->after('tingkat');
        });

        foreach (DB::table('rombels')->get(['id', 'tingkat']) as $rombel) {
            DB::table('rombels')->where('id', $rombel->id)->update([
                'tingkat_angka' => $map[strtoupper((string) $rombel->tingkat)] ?? 7,
            ]);
        }

        Schema::table('rombels', function (Blueprint $table) {
            $table->dropColumn('tingkat');
        });

        Schema::table('rombels', function (Blueprint $table) {
            $table->unsignedTinyInteger('tingkat')->default(7)->after('tahun_ajaran_id');
        });

        foreach (DB::table('rombels')->get(['id', 'tingkat_angka']) as $rombel) {
            DB::table('rombels')->where('id', $rombel->id)->update([
                'tingkat' => $rombel->tingkat_angka ?: 7,
            ]);
        }

        Schema::table('rombels', function (Blueprint $table) {
            $table->dropColumn('tingkat_angka');
        });

        if ($hadTingkatUnique) {
            Schema::table('rombels', function (Blueprint $table) {
                $table->unique(['tahun_ajaran_id', 'tingkat', 'nama']);
            });
        }
    }

    private function dropTingkatUniqueIfPresent(): bool
    {
        $indexNames = collect(Schema::getIndexes('rombels'))->pluck('name');
        if (! $indexNames->contains('rombels_tahun_ajaran_id_tingkat_nama_unique')) {
            return false;
        }

        Schema::table('rombels', function (Blueprint $table) {
            $table->dropUnique(['tahun_ajaran_id', 'tingkat', 'nama']);
        });

        return true;
    }
};
