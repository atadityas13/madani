<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hadTingkatUnique = $this->dropTingkatUniqueAndForeignIfPresent();

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE rombels MODIFY tingkat VARCHAR(10) NOT NULL');
        } else {
            $this->convertTingkatViaTempColumn('tingkat_romawi', 'string');
        }

        foreach (DB::table('rombels')->get(['id', 'tingkat']) as $rombel) {
            DB::table('rombels')->where('id', $rombel->id)->update([
                'tingkat' => $this->toRoman($rombel->tingkat),
            ]);
        }

        if ($hadTingkatUnique) {
            $this->restoreTingkatUniqueAndForeign();
        }
    }

    public function down(): void
    {
        $hadTingkatUnique = $this->dropTingkatUniqueAndForeignIfPresent();

        foreach (DB::table('rombels')->get(['id', 'tingkat']) as $rombel) {
            DB::table('rombels')->where('id', $rombel->id)->update([
                'tingkat' => $this->toNumeric($rombel->tingkat),
            ]);
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE rombels MODIFY tingkat TINYINT UNSIGNED NOT NULL');
        } else {
            $this->convertTingkatViaTempColumn('tingkat_angka', 'tinyInteger');
        }

        if ($hadTingkatUnique) {
            $this->restoreTingkatUniqueAndForeign();
        }
    }

    private function toRoman(mixed $tingkat): string
    {
        return match (strtoupper(trim((string) $tingkat))) {
            '7', 'VII' => 'VII',
            '8', 'VIII' => 'VIII',
            '9', 'IX' => 'IX',
            default => 'VII',
        };
    }

    private function toNumeric(mixed $tingkat): int
    {
        return match (strtoupper(trim((string) $tingkat))) {
            'VII', '7' => 7,
            'VIII', '8' => 8,
            'IX', '9' => 9,
            default => 7,
        };
    }

    /**
     * SQLite / non-MySQL fallback: rebuild tingkat through a temp column.
     */
    private function convertTingkatViaTempColumn(string $temp, string $mode): void
    {
        if ($mode === 'string') {
            Schema::table('rombels', function (Blueprint $table) use ($temp) {
                $table->string($temp, 10)->nullable()->after('tingkat');
            });

            foreach (DB::table('rombels')->get(['id', 'tingkat']) as $rombel) {
                DB::table('rombels')->where('id', $rombel->id)->update([
                    $temp => $this->toRoman($rombel->tingkat),
                ]);
            }

            Schema::table('rombels', function (Blueprint $table) {
                $table->dropColumn('tingkat');
            });

            Schema::table('rombels', function (Blueprint $table) use ($temp) {
                $table->string('tingkat', 10)->default('VII')->after('tahun_ajaran_id');
            });

            foreach (DB::table('rombels')->get(['id', $temp]) as $rombel) {
                DB::table('rombels')->where('id', $rombel->id)->update([
                    'tingkat' => $rombel->{$temp} ?: 'VII',
                ]);
            }

            Schema::table('rombels', function (Blueprint $table) use ($temp) {
                $table->dropColumn($temp);
            });

            return;
        }

        Schema::table('rombels', function (Blueprint $table) use ($temp) {
            $table->unsignedTinyInteger($temp)->nullable()->after('tingkat');
        });

        foreach (DB::table('rombels')->get(['id', 'tingkat']) as $rombel) {
            DB::table('rombels')->where('id', $rombel->id)->update([
                $temp => $this->toNumeric($rombel->tingkat),
            ]);
        }

        Schema::table('rombels', function (Blueprint $table) {
            $table->dropColumn('tingkat');
        });

        Schema::table('rombels', function (Blueprint $table) {
            $table->unsignedTinyInteger('tingkat')->default(7)->after('tahun_ajaran_id');
        });

        foreach (DB::table('rombels')->get(['id', $temp]) as $rombel) {
            DB::table('rombels')->where('id', $rombel->id)->update([
                'tingkat' => $rombel->{$temp} ?: 7,
            ]);
        }

        Schema::table('rombels', function (Blueprint $table) use ($temp) {
            $table->dropColumn($temp);
        });
    }

    private function dropTingkatUniqueAndForeignIfPresent(): bool
    {
        $indexNames = collect(Schema::getIndexes('rombels'))->pluck('name');
        if (! $indexNames->contains('rombels_tahun_ajaran_id_tingkat_nama_unique')) {
            return false;
        }

        // MySQL needs the FK dropped before this composite unique can be removed.
        Schema::table('rombels', function (Blueprint $table) {
            $table->dropForeign(['tahun_ajaran_id']);
        });

        Schema::table('rombels', function (Blueprint $table) {
            $table->dropUnique(['tahun_ajaran_id', 'tingkat', 'nama']);
        });

        return true;
    }

    private function restoreTingkatUniqueAndForeign(): void
    {
        Schema::table('rombels', function (Blueprint $table) {
            $table->unique(['tahun_ajaran_id', 'tingkat', 'nama']);
            $table->foreign('tahun_ajaran_id')
                ->references('id')
                ->on('tahun_ajarans')
                ->cascadeOnDelete();
        });
    }
};
