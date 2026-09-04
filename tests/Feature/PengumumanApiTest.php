<?php

namespace Tests\Feature;

use App\Models\Notifikasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengumumanApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengumuman_api_returns_published_items(): void
    {
        Notifikasi::query()->create([
            'judul' => 'Aktif',
            'isi' => 'Isi aktif',
            'jenis' => Notifikasi::JENIS_PENGUMUMAN,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);
        Notifikasi::query()->create([
            'judul' => 'Nonaktif',
            'isi' => 'Sembunyi',
            'jenis' => Notifikasi::JENIS_PENGUMUMAN,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
            'is_active' => false,
            'published_at' => now()->subHour(),
        ]);
        Notifikasi::query()->create([
            'judul' => 'Jadwal depan',
            'isi' => 'Belum tayang',
            'jenis' => Notifikasi::JENIS_PENGUMUMAN,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
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
