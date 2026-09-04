<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notifikasi_id')->constrained('notifikasis')->cascadeOnDelete();
            $table->string('reader_type');
            $table->string('reader_id');
            $table->timestamp('read_at');
            $table->timestamps();

            $table->index(['reader_type', 'reader_id']);
            $table->unique(['notifikasi_id', 'reader_type', 'reader_id'], 'notifikasi_reads_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_reads');
    }
};
