<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Siswa;
use App\Support\ElapkinBridge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SiswaCalendarApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_siswa_can_list_calendar_events(): void
    {
        $this->seed();
        CalendarEvent::query()->create([
            'title' => 'Upacara',
            'event_date' => '2026-08-17',
            'event_time' => '07:00',
            'is_important' => true,
            'is_active' => true,
        ]);

        $token = $this->tokenSiswa();

        $this->withToken($token)
            ->getJson('/api/v1/siswa/calendar-events?tahun=2026')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('tahun', 2026)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Upacara');
    }

    public function test_siswa_hari_libur_uses_madrasah_elapkin_bridge(): void
    {
        $this->seed();

        $bridge = Mockery::mock(ElapkinBridge::class);
        $bridge->shouldReceive('hariLiburMadrasah')
            ->once()
            ->with(2026)
            ->andReturn([
                'success' => true,
                'tahun' => 2026,
                'data' => [
                    ['tanggal' => '2026-01-01', 'keterangan' => 'Tahun Baru'],
                ],
                'count' => 1,
            ]);
        $this->app->instance(ElapkinBridge::class, $bridge);

        $token = $this->tokenSiswa();

        $this->withToken($token)
            ->getJson('/api/v1/siswa/hari-libur?tahun=2026')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('tahun', 2026)
            ->assertJsonCount(1, 'data');
    }

    public function test_siswa_hari_libur_rejects_invalid_year(): void
    {
        $this->seed();
        $token = $this->tokenSiswa();

        $this->withToken($token)
            ->getJson('/api/v1/siswa/hari-libur?tahun=1900')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    private function tokenSiswa(): string
    {
        $siswa = Siswa::query()->create([
            'nama' => 'Siswa Kalender',
            'nisn' => '5566778899',
            'nik' => '3210010101120077',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-09-02',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif',
        ]);
        $siswa->gantiPassword('sandibaru1');

        return $this->postJson('/api/v1/siswa/login', [
            'nisn' => $siswa->nisn,
            'password' => 'sandibaru1',
        ])->assertOk()->json('token');
    }
}
