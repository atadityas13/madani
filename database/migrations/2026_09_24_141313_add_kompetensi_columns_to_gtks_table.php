<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtks', function (Blueprint $table) {
            $table->string('mapel_ijazah')->nullable()->after('duk');
            $table->string('mapel_sertifikasi')->nullable()->after('mapel_ijazah');
            $table->boolean('status_sertifikasi')->default(false)->after('mapel_sertifikasi');
            $table->boolean('is_bk')->default(false)->after('status_sertifikasi');
        });

        if (Schema::hasColumn('gtks', 'meta')) {
            $normalize = static function (mixed $value): ?string {
                if ($value === null) {
                    return null;
                }

                $trimmed = trim((string) $value);

                return $trimmed === '' ? null : $trimmed;
            };

            DB::table('gtks')->orderBy('id')->chunkById(100, function ($rows) use ($normalize): void {
                foreach ($rows as $row) {
                    $meta = json_decode((string) ($row->meta ?? ''), true);
                    if (! is_array($meta)) {
                        continue;
                    }

                    DB::table('gtks')->where('id', $row->id)->update([
                        'mapel_ijazah' => $normalize($meta['mapel_ijazah'] ?? null),
                        'mapel_sertifikasi' => $normalize($meta['mapel_sertifikasi'] ?? null),
                        'status_sertifikasi' => (bool) ($meta['status_sertifikasi'] ?? false),
                        'is_bk' => (bool) ($meta['is_bk'] ?? false),
                    ]);
                }
            });

            Schema::table('gtks', function (Blueprint $table) {
                $table->dropColumn('meta');
            });
        }
    }

    public function down(): void
    {
        Schema::table('gtks', function (Blueprint $table) {
            $table->json('meta')->nullable()->after('foto_url');
        });

        DB::table('gtks')->orderBy('id')->chunkById(100, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('gtks')->where('id', $row->id)->update([
                    'meta' => json_encode([
                        'mapel_ijazah' => $row->mapel_ijazah,
                        'mapel_sertifikasi' => $row->mapel_sertifikasi,
                        'status_sertifikasi' => (bool) $row->status_sertifikasi,
                        'is_bk' => (bool) $row->is_bk,
                    ], JSON_UNESCAPED_UNICODE),
                ]);
            }
        });

        Schema::table('gtks', function (Blueprint $table) {
            $table->dropColumn(['mapel_ijazah', 'mapel_sertifikasi', 'status_sertifikasi', 'is_bk']);
        });
    }
};
