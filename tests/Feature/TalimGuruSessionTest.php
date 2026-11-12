<?php

namespace Tests\Feature;

use App\Models\AppMenu;
use App\Models\Gtk;
use App\Models\Siswa;
use App\Models\User;
use App\Support\Peran;
use App\Support\TalimSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TalimGuruSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_guru_idle_ditolak_sementara_guru_lain_yang_baru_dipakai_tetap_masuk(): void
    {
        $idle = $this->buatGuru('Ani Idle', '198201012006012002');
        $aktif = $this->buatGuru('Budi Aktif', '198001012005011001');

        $stale = $idle->createToken(TalimSession::GURU_TOKEN);
        $stale->accessToken->forceFill([
            'created_at' => now()->subDays(2),
            'last_used_at' => now()->subMinutes(121),
        ])->save();

        $masihDipakai = $aktif->createToken(TalimSession::GURU_TOKEN);
        $masihDipakai->accessToken->forceFill([
            'created_at' => now()->subDays(30),
            'last_used_at' => now()->subMinutes(10),
        ])->save();

        $this->withToken($stale->plainTextToken)
            ->getJson('/api/v1/guru/me')
            ->assertUnauthorized();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $stale->accessToken->id,
        ]);

        $this->withToken($masihDipakai->plainTextToken)
            ->getJson('/api/v1/guru/me')
            ->assertOk()
            ->assertJsonPath('user.username', '198001012005011001');

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $masihDipakai->accessToken->id,
        ]);
    }

    public function test_token_guru_tanpa_aktivitas_sejak_login_ikut_idle(): void
    {
        $user = $this->buatGuru('Cici', '198301012007012003');
        $token = $user->createToken(TalimSession::GURU_TOKEN);
        $token->accessToken->forceFill([
            'created_at' => now()->subMinutes(TalimSession::idleMinutes() + 1),
            'last_used_at' => null,
        ])->save();

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/guru/me')
            ->assertUnauthorized();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_token_siswa_lama_tidak_dipotong_aturan_idle_guru(): void
    {
        $siswa = Siswa::query()->create([
            'nama' => 'Siswa Lama',
            'nisn' => '3344556678',
            'nik' => '3210010101120089',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-09-02',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif',
        ]);
        $token = $siswa->createToken('talim');
        $token->accessToken->forceFill([
            'created_at' => now()->subDays(40),
            'last_used_at' => now()->subDays(10),
        ])->save();

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/siswa/me')
            ->assertOk();

        $this->artisan('talim:prune-sessions')->assertSuccessful();

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $token->accessToken->id,
            'name' => 'talim',
        ]);
    }

    public function test_perintah_prune_hanya_menghapus_token_guru_yang_idle_dan_sesi_web_kedaluwarsa(): void
    {
        config([
            'session.driver' => 'database',
            'session.lifetime' => 120,
        ]);

        $idle = $this->buatGuru('Dedi Idle', '198401012008011004');
        $aktif = $this->buatGuru('Eka Aktif', '198501012009011005');

        $stale = $idle->createToken(TalimSession::GURU_TOKEN);
        $stale->accessToken->forceFill(['last_used_at' => now()->subMinutes(121)])->save();

        $hidup = $aktif->createToken(TalimSession::GURU_TOKEN);
        $hidup->accessToken->forceFill(['last_used_at' => now()->subMinutes(5)])->save();

        DB::table('sessions')->insert([
            [
                'id' => 'sesi-kedaluwarsa',
                'user_id' => $idle->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test',
                'payload' => 'x',
                'last_activity' => now()->subMinutes(121)->getTimestamp(),
            ],
            [
                'id' => 'sesi-masih-hidup',
                'user_id' => $aktif->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test',
                'payload' => 'x',
                'last_activity' => now()->subMinutes(5)->getTimestamp(),
            ],
        ]);

        $this->artisan('talim:prune-sessions')->assertSuccessful();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $stale->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $hidup->accessToken->id]);
        $this->assertDatabaseMissing('sessions', ['id' => 'sesi-kedaluwarsa']);
        $this->assertDatabaseHas('sessions', ['id' => 'sesi-masih-hidup']);
    }

    public function test_webview_enter_menyisakan_satu_sesi_lama_yang_sudah_idle(): void
    {
        config([
            'session.driver' => 'database',
            'app.url' => 'https://madani.mtsn11majalengka.sch.id',
        ]);

        $guru = $this->buatGuru('Fajar', '198601012010011006');
        $lain = $this->buatGuru('Gita', '198701012011012007');

        $menu = AppMenu::query()->create([
            'type' => AppMenu::TYPE_CUSTOM,
            'key' => 'madani_portal_sesi',
            'judul' => 'Portal Madani',
            'url' => 'https://madani.mtsn11majalengka.sch.id/dashboard',
            'open_mode' => AppMenu::OPEN_WEBVIEW,
            'audience' => AppMenu::AUDIENCE_GURU,
            'requires_auth' => true,
            'sort_order' => 999,
            'is_active' => true,
        ]);

        $ticket = 'tiket-sesi-guru';
        Cache::put('app_menu_webview_ticket:'.$ticket, [
            'menu_id' => $menu->id,
            'user_id' => $guru->id,
            'guard' => 'web',
            'url' => $menu->url,
        ], now()->addMinutes(2));

        DB::table('sessions')->insert([
            [
                'id' => 'sesi-lama-guru',
                'user_id' => $guru->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test',
                'payload' => 'x',
                'last_activity' => now()->subMinutes(TalimSession::idleMinutes() + 1)->getTimestamp(),
            ],
            [
                'id' => 'sesi-double-hit',
                'user_id' => $guru->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test',
                'payload' => 'x',
                'last_activity' => now()->subSeconds(5)->getTimestamp(),
            ],
            [
                'id' => 'sesi-guru-lain',
                'user_id' => $lain->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test',
                'payload' => 'x',
                'last_activity' => now()->subMinutes(TalimSession::idleMinutes() + 5)->getTimestamp(),
            ],
        ]);

        $this->get('/webview/enter?ticket='.$ticket)
            ->assertRedirect($menu->url);

        $this->assertAuthenticatedAs($guru, 'web');
        $this->assertDatabaseMissing('sessions', ['id' => 'sesi-lama-guru']);
        $this->assertDatabaseHas('sessions', ['id' => 'sesi-double-hit']);
        $this->assertDatabaseHas('sessions', ['id' => 'sesi-guru-lain']);
        $this->assertSame(2, DB::table('sessions')->where('user_id', $guru->id)->count());
    }

    private function buatGuru(string $nama, string $nip): User
    {
        Role::findOrCreate(Peran::GURU);

        $gtk = Gtk::query()->create([
            'nama' => $nama,
            'nip' => $nip,
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $user = User::factory()->create([
            'name' => $nama,
            'username' => $nip,
            'gtk_id' => $gtk->id,
            'is_aktif' => true,
            'password' => 'password123',
        ]);
        $user->syncRoles([Peran::GURU]);

        return $user;
    }
}
