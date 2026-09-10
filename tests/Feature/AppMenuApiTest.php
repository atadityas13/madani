<?php

namespace Tests\Feature;

use App\Models\AppMenu;
use App\Models\Gtk;
use App\Models\Siswa;
use App\Models\User;
use App\Support\Peran;
use Database\Seeders\AppMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppMenuApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'https://madani.mtsn11majalengka.sch.id']);
        (new AppMenuSeeder)->run();
    }

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

    private function siswaUser(): Siswa
    {
        return Siswa::query()->create([
            'nama' => 'Siswa Menu',
            'nisn' => '1122334455',
            'nik' => '3210010101120099',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-01-01',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif',
        ]);
    }

    public function test_guru_receives_ordered_menus_including_builtins(): void
    {
        Sanctum::actingAs($this->guruUser());

        $response = $this->getJson('/api/v1/menus')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('audience', 'guru');

        $keys = collect($response->json('data'))->pluck('key')->all();
        $this->assertSame('jadwal', $keys[0]);
        $this->assertContains('cbt', $keys);
        $this->assertContains('bitlearn', $keys);
        $this->assertNotContains('website', collect($response->json('data'))
            ->where('audience', 'siswa')
            ->pluck('key')
            ->all());
    }

    public function test_siswa_menus_are_custom_only(): void
    {
        Sanctum::actingAs($this->siswaUser());

        $response = $this->getJson('/api/v1/menus')
            ->assertOk()
            ->assertJsonPath('audience', 'siswa');

        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertSame(['website', 'bitlearn'], collect($data)->pluck('key')->all());
        $this->assertTrue(collect($data)->every(fn (array $row) => $row['type'] === 'custom'));
    }

    public function test_inactive_custom_hidden_but_builtin_always_shown(): void
    {
        AppMenu::query()->where('audience', 'guru')->where('key', 'cbt')->update(['is_active' => false]);

        Sanctum::actingAs($this->guruUser());

        $keys = collect($this->getJson('/api/v1/menus')->json('data'))->pluck('key');
        $this->assertFalse($keys->contains('cbt'));
        $this->assertTrue($keys->contains('jadwal'));
    }

    public function test_launch_rejects_non_madani_requires_auth(): void
    {
        $menu = AppMenu::query()->where('key', 'cbt')->where('audience', 'guru')->firstOrFail();
        $menu->update(['requires_auth' => true]);

        Sanctum::actingAs($this->guruUser());

        $this->getJson('/api/v1/menus/'.$menu->id.'/launch')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_launch_and_webview_enter_for_madani_url(): void
    {
        $menu = AppMenu::query()->create([
            'type' => AppMenu::TYPE_CUSTOM,
            'key' => 'madani_portal',
            'judul' => 'Portal Madani',
            'url' => 'https://madani.mtsn11majalengka.sch.id/dashboard',
            'open_mode' => AppMenu::OPEN_WEBVIEW,
            'audience' => AppMenu::AUDIENCE_GURU,
            'requires_auth' => true,
            'sort_order' => 999,
            'is_active' => true,
        ]);

        $guru = $this->guruUser();
        Sanctum::actingAs($guru);

        $launch = $this->getJson('/api/v1/menus/'.$menu->id.'/launch')
            ->assertOk()
            ->assertJsonPath('success', true);

        $url = $launch->json('url');
        $this->assertStringContainsString('/webview/enter?ticket=', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $ticket = $query['ticket'] ?? null;
        $this->assertNotEmpty($ticket);
        $this->assertTrue(Cache::has('app_menu_webview_ticket:'.$ticket));

        $this->get('/webview/enter?ticket='.$ticket)
            ->assertRedirect('https://madani.mtsn11majalengka.sch.id/dashboard');

        $this->assertAuthenticatedAs($guru, 'web');
        $this->assertFalse(Cache::has('app_menu_webview_ticket:'.$ticket));
    }

    public function test_menu_payload_includes_icon_url_for_stored_path(): void
    {
        $menu = AppMenu::query()->where('key', 'website')->where('audience', 'guru')->firstOrFail();
        $menu->update(['icon_path' => 'https://cdn.example.test/app-menus/demo-icon.png']);

        Sanctum::actingAs($this->guruUser());

        $response = $this->getJson('/api/v1/menus')->assertOk();
        $website = collect($response->json('data'))->firstWhere('key', 'website');
        $this->assertNotNull($website);
        $this->assertSame('https://cdn.example.test/app-menus/demo-icon.png', $website['icon_url']);
    }

    public function test_admin_can_reorder_builtin_menu(): void
    {
        Role::findOrCreate(Peran::ADMIN);
        $admin = User::factory()->create(['is_aktif' => true]);
        $admin->syncRoles([Peran::ADMIN]);

        $jadwal = AppMenu::query()->where('key', 'jadwal')->where('audience', 'guru')->firstOrFail();
        $pembagian = AppMenu::query()->where('key', 'pembagian')->where('audience', 'guru')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('app-menus.move-down', $jadwal))
            ->assertRedirect(route('app-menus.index', ['audience' => 'guru']));

        $jadwal->refresh();
        $pembagian->refresh();
        $this->assertTrue($jadwal->sort_order > $pembagian->sort_order);
    }

    public function test_admin_rejects_requires_auth_for_external_url(): void
    {
        Role::findOrCreate(Peran::ADMIN);
        $admin = User::factory()->create(['is_aktif' => true]);
        $admin->syncRoles([Peran::ADMIN]);

        $this->actingAs($admin)
            ->post(route('app-menus.store'), [
                'audience' => 'guru',
                'judul' => 'Luar',
                'open_mode' => 'webview',
                'url' => 'https://example.com',
                'requires_auth' => '1',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('requires_auth');
    }

    public function test_seeder_tidak_menimpa_menu_yang_sudah_ada(): void
    {
        $website = AppMenu::query()->where('key', 'website')->where('audience', 'guru')->firstOrFail();
        $website->update([
            'judul' => 'Website Kustom',
            'sort_order' => 999,
            'is_active' => false,
        ]);

        (new AppMenuSeeder)->run();

        $website->refresh();
        $this->assertSame('Website Kustom', $website->judul);
        $this->assertSame(999, $website->sort_order);
        $this->assertFalse($website->is_active);

        $this->assertTrue(
            AppMenu::query()->where('key', AppMenu::KEY_TUNJANGAN)->where('audience', 'guru')->exists()
        );
    }

    public function test_seeder_memindahkan_tunjangan_ke_chrome_tab_untuk_file_picker(): void
    {
        $menu = AppMenu::query()
            ->where('key', AppMenu::KEY_TUNJANGAN)
            ->where('audience', AppMenu::AUDIENCE_GURU)
            ->firstOrFail();
        $menu->update(['open_mode' => AppMenu::OPEN_WEBVIEW]);

        (new AppMenuSeeder)->run();

        $menu->refresh();
        $this->assertSame(AppMenu::OPEN_CHROME_TAB, $menu->open_mode);
    }
}
