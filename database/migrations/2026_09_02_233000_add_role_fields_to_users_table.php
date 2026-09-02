<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_aktif')->default(true)->after('password');
            $table->foreignId('gtk_id')->nullable()->after('is_aktif')->unique()->constrained('gtks')->nullOnDelete();
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['superadmin', 'admin', 'wali_kelas', 'operator', 'kamad', 'guru'] as $role) {
            Role::findOrCreate($role);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gtk_id');
            $table->dropColumn('is_aktif');
        });
    }
};
