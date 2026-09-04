<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifikasis', function (Blueprint $table) {
            $table->string('audio_url', 500)->nullable()->after('link');
            $table->string('sound_key', 32)->default('default')->after('audio_url');
            $table->string('priority', 16)->default('normal')->after('sound_key');
            $table->string('deep_link', 64)->nullable()->after('priority');
            $table->timestamp('scheduled_at')->nullable()->after('published_at');
            $table->timestamp('sent_at')->nullable()->after('scheduled_at');

            $table->index(['scheduled_at', 'sent_at', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('notifikasis', function (Blueprint $table) {
            $table->dropIndex(['scheduled_at', 'sent_at', 'is_active']);
            $table->dropColumn([
                'audio_url',
                'sound_key',
                'priority',
                'deep_link',
                'scheduled_at',
                'sent_at',
            ]);
        });
    }
};
