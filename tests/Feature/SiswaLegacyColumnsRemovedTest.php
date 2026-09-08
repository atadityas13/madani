<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiswaLegacyColumnsRemovedTest extends TestCase
{
    use RefreshDatabase;

    public function test_unused_rekam_didik_kk_columns_are_removed(): void
    {
        foreach ([
            'nik_kk',
            'nama_kk',
            'tempat_lahir_kk',
            'tanggal_lahir_kk',
            'jenis_kelamin_kk',
            'nama_ibu_kk',
            'nama_ayah_kk',
        ] as $column) {
            $this->assertFalse(
                Schema::hasColumn('rekam_didiks', $column),
                "Expected rekam_didiks.{$column} to be removed.",
            );
        }
    }

    public function test_unused_periodik_columns_are_removed(): void
    {
        $this->assertFalse(Schema::hasColumn('siswa_periodiks', 'pra_sekolah'));
        $this->assertFalse(Schema::hasColumn('siswa_periodiks', 'kode_wilayah'));
        $this->assertTrue(Schema::hasColumn('siswa_periodiks', 'pernah_tk_ra'));
        $this->assertTrue(Schema::hasColumn('siswa_periodiks', 'pernah_paud'));
    }
}
