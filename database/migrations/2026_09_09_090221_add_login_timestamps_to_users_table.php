<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'first_login_at')) {
                $table->timestamp('first_login_at')->nullable()->after('is_aktif');
            }
            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('first_login_at')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->dropIndex(['last_login_at']);
                $table->dropColumn('last_login_at');
            }
            if (Schema::hasColumn('users', 'first_login_at')) {
                $table->dropColumn('first_login_at');
            }
        });
    }
};
