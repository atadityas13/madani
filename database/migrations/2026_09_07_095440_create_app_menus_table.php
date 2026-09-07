<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_menus', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20); // builtin|custom
            $table->string('key', 64)->nullable();
            $table->string('judul');
            $table->string('url', 500)->nullable();
            $table->string('icon_path')->nullable();
            $table->string('open_mode', 20)->nullable(); // webview|chrome_tab|app
            $table->string('package_name', 200)->nullable();
            $table->string('play_store_url', 500)->nullable();
            $table->string('audience', 20); // guru|siswa|semua
            $table->boolean('requires_auth')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['audience', 'key']);
            $table->index(['audience', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_menus');
    }
};
