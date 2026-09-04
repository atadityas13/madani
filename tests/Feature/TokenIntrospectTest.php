<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TokenIntrospectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.madani_introspect.secret' => 'test-introspect-secret']);
    }

    private function buatGuruToken(): array
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
            'password' => 'password123',
        ]);
        $user->syncRoles([Peran::GURU]);
        $token = $user->createToken('talim-guru')->plainTextToken;

        return compact('user', 'token');
    }

    public function test_introspect_aktif_dengan_secret_valid(): void
    {
        ['token' => $token] = $this->buatGuruToken();

        $this->postJson('/api/v1/token/introspect', ['token' => $token], [
            'X-Madani-Introspect-Secret' => 'test-introspect-secret',
        ])
            ->assertOk()
            ->assertJsonPath('active', true)
            ->assertJsonPath('username', '198001012005011001')
            ->assertJsonPath('nip', '198001012005011001');
    }

    public function test_introspect_ditolak_tanpa_secret(): void
    {
        ['token' => $token] = $this->buatGuruToken();

        $this->postJson('/api/v1/token/introspect', ['token' => $token])
            ->assertUnauthorized()
            ->assertJsonPath('active', false);
    }

    public function test_introspect_token_tidak_dikenal(): void
    {
        $this->postJson('/api/v1/token/introspect', ['token' => '1|bukan-token'], [
            'X-Madani-Introspect-Secret' => 'test-introspect-secret',
        ])
            ->assertOk()
            ->assertJsonPath('active', false);
    }
}
