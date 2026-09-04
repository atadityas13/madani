<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('tokenable_type');
            $table->string('tokenable_id');
            $table->string('fcm_token', 512);
            $table->string('platform', 32)->default('android');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['tokenable_type', 'tokenable_id']);
            $table->unique('fcm_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
