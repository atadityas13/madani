<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orang_tuas')
            ->where('peran', 'wali')
            ->where('status', 'Sama dengan ayah kandung')
            ->where(function ($query) {
                $query->whereNull('nama')->orWhere('nama', '');
            })
            ->update([
                'status' => null,
                'hubungan' => null,
            ]);
    }

    public function down(): void
    {
        //
    }
};
