<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('aktif')->index();
            $table->timestamps();
        });

        Schema::create('vendor_job_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_job_id')->constrained('vendor_jobs')->cascadeOnDelete();
            $table->foreignUuid('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['vendor_job_id', 'siswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_job_siswas');
        Schema::dropIfExists('vendor_jobs');
    }
};
