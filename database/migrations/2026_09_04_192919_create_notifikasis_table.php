<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasis', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 200);
            $table->text('isi');
            $table->string('jenis', 32)->default('pengumuman');
            $table->string('audience', 32)->default('semua_guru');
            $table->json('audience_ids')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['jenis', 'is_active', 'published_at']);
            $table->index(['audience', 'is_active']);
        });

        if (Schema::hasTable('pengumumans')) {
            $rows = DB::table('pengumumans')->orderBy('id')->get();
            foreach ($rows as $row) {
                DB::table('notifikasis')->insert([
                    'judul' => $row->judul,
                    'isi' => $row->isi,
                    'jenis' => 'pengumuman',
                    'audience' => 'semua_guru',
                    'audience_ids' => null,
                    'starts_at' => null,
                    'ends_at' => null,
                    'is_active' => (bool) $row->is_active,
                    'published_at' => $row->published_at,
                    'created_by' => $row->created_by,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasis');
    }
};
