<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GtkSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_gtks_tidak_menyimpan_jejak_simpatisans(): void
    {
        $this->assertTrue(Schema::hasTable('gtks'));
        $this->assertFalse(Schema::hasColumn('gtks', 'simpatisans_guru_id'));
    }
}
