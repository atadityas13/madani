<?php

namespace Tests\Feature;

use App\Models\AppUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppUpdateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_update_android_returns_active_policy(): void
    {
        AppUpdate::create([
            'platform' => 'android',
            'latest_version_code' => 12,
            'latest_version_name' => '1.2.0',
            'minimum_version_code' => 10,
            'title' => 'Update Ta\'lim tersedia',
            'message' => 'Ada versi baru.',
            'changelog' => '- Perbaikan',
            'play_store_url' => 'https://play.google.com/store/apps/details?id=com.atadevlabs.talim',
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/app-update/android')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.platform', 'android')
            ->assertJsonPath('data.latest_version_code', 12)
            ->assertJsonPath('data.minimum_version_code', 10)
            ->assertJsonPath('data.latest_version_name', '1.2.0');
    }

    public function test_app_update_inactive_returns_null_data(): void
    {
        AppUpdate::create([
            'platform' => 'android',
            'latest_version_code' => 12,
            'latest_version_name' => '1.2.0',
            'minimum_version_code' => 10,
            'title' => 'Update',
            'is_active' => false,
        ]);

        $this->getJson('/api/v1/app-update/android')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', null);
    }

    public function test_app_update_rejects_unknown_platform(): void
    {
        $this->getJson('/api/v1/app-update/ios')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }
}
