<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeviceTokenApiTest extends TestCase
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

    public function test_guru_can_register_and_remove_device_token(): void
    {
        $guru = $this->guruUser();
        Sanctum::actingAs($guru);

        $this->postJson('/api/v1/device-token', [
            'fcm_token' => 'token-abc-123',
            'platform' => 'android',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('device_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => (string) $guru->id,
            'fcm_token' => 'token-abc-123',
        ]);

        $this->deleteJson('/api/v1/device-token?fcm_token=token-abc-123')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('device_tokens', [
            'fcm_token' => 'token-abc-123',
        ]);
    }

    public function test_device_token_rebinds_when_another_account_registers_same_token(): void
    {
        $guruA = $this->guruUser();
        $gtkB = Gtk::query()->create([
            'nama' => 'Ani',
            'nip' => '198101012006012002',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);
        $guruB = User::factory()->create([
            'username' => '198101012006012002',
            'gtk_id' => $gtkB->id,
            'is_aktif' => true,
        ]);
        $guruB->syncRoles([Peran::GURU]);

        Sanctum::actingAs($guruA);
        $this->postJson('/api/v1/device-token', ['fcm_token' => 'shared-fcm'])->assertOk();

        Sanctum::actingAs($guruB);
        $this->postJson('/api/v1/device-token', ['fcm_token' => 'shared-fcm'])->assertOk();

        $this->assertDatabaseMissing('device_tokens', [
            'tokenable_id' => (string) $guruA->id,
            'fcm_token' => 'shared-fcm',
        ]);
        $this->assertDatabaseHas('device_tokens', [
            'tokenable_id' => (string) $guruB->id,
            'fcm_token' => 'shared-fcm',
        ]);
    }
}
