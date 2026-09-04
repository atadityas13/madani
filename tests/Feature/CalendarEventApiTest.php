<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Gtk;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CalendarEventApiTest extends TestCase
{
    use RefreshDatabase;

    private function guruUser(): User
    {
        Role::findOrCreate(Peran::GURU);
        $gtk = Gtk::query()->create([
            'nama' => 'Budi',
            'nip' => '198001012005011001',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);
        $user = User::factory()->create([
            'username' => '198001012005011001',
            'gtk_id' => $gtk->id,
            'is_aktif' => true,
        ]);
        $user->syncRoles([Peran::GURU]);

        return $user;
    }

    public function test_calendar_events_returns_active_for_year(): void
    {
        CalendarEvent::query()->create([
            'title' => 'Rapat',
            'event_date' => '2026-03-10',
            'event_time' => '08:00',
            'is_important' => true,
            'is_active' => true,
        ]);
        CalendarEvent::query()->create([
            'title' => 'Nonaktif',
            'event_date' => '2026-03-11',
            'is_active' => false,
        ]);

        Sanctum::actingAs($this->guruUser());

        $this->getJson('/api/v1/guru/calendar-events?tahun=2026')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('tahun', 2026)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Rapat');
    }
}
