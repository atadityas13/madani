<?php

namespace Tests\Feature;

use App\Models\Pengumuman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengumumanApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengumuman_api_returns_published_items(): void
    {
        Pengumuman::create([
            'judul' => 'Aktif',
            'isi' => 'Isi aktif',
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);
        Pengumuman::create([
            'judul' => 'Nonaktif',
            'isi' => 'Sembunyi',
            'is_active' => false,
            'published_at' => now()->subHour(),
        ]);
        Pengumuman::create([
            'judul' => 'Jadwal depan',
            'isi' => 'Belum tayang',
            'is_active' => true,
            'published_at' => now()->addDay(),
        ]);

        $this->getJson('/api/v1/pengumuman')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Aktif')
            ->assertJsonPath('data.0.isi', 'Isi aktif');
    }
}
