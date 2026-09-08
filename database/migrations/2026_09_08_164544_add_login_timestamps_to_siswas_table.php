<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            if (! Schema::hasColumn('siswas', 'first_login_at')) {
                $table->timestamp('first_login_at')->nullable()->after('must_change_password');
            }
            if (! Schema::hasColumn('siswas', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('first_login_at')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            if (Schema::hasColumn('siswas', 'last_login_at')) {
                $table->dropIndex(['last_login_at']);
                $table->dropColumn('last_login_at');
            }
            if (Schema::hasColumn('siswas', 'first_login_at')) {
                $table->dropColumn('first_login_at');
            }
        });
    }
};
