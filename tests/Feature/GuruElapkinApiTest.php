<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuruElapkinApiTest extends TestCase
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
            'jabatan' => 'Guru Mapel',
        ]);
        $user = User::factory()->create([
            'username' => '198001012005011001',
            'gtk_id' => $gtk->id,
            'is_aktif' => true,
        ]);
        $user->syncRoles([Peran::GURU]);

        return $user;
    }

    public function test_elapkin_bridge_returns_cookies_when_sso_ok(): void
    {
        Http::fake([
            '*/api/auth/sso.php' => Http::response(['success' => true], 200, [
                'Set-Cookie' => 'ELAPKINSESSID=abc123; Path=/',
            ]),
        ]);

        Sanctum::actingAs($this->guruUser());

        $this->postJson('/api/v1/guru/elapkin-bridge')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('cookies', 'ELAPKINSESSID=abc123');
    }

    public function test_sso_token_payload_contains_nip(): void
    {
        Sanctum::actingAs($this->guruUser());

        $this->getJson('/api/v1/guru/elapkin-sso')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('nip', '198001012005011001')
            ->assertJsonStructure(['signature', 'profile_hash', 'profile']);
    }
}
