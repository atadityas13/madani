<?php

namespace Tests\Feature;

use App\Models\AppMenu;
use App\Models\Gtk;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\Tunjangan\TunjanganDokumenService;
use App\Support\Peran;
use Database\Seeders\AppMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TalimTunjanganMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'https://madani.mtsn11majalengka.sch.id']);
        (new AppMenuSeeder)->run();
    }

    public function test_menu_tunjangan_hanya_untuk_guru_tersertifikasi(): void
    {
        Sanctum::actingAs($this->buatGuru(nrg: null));
        $keysTanpa = collect($this->getJson('/api/v1/menus')->json('data'))->pluck('key');
        $this->assertFalse($keysTanpa->contains(AppMenu::KEY_TUNJANGAN));

        Sanctum::actingAs($this->buatGuru(nrg: 'NRG-99'));
        $keysAda = collect($this->getJson('/api/v1/menus')->json('data'))->pluck('key');
        $this->assertTrue($keysAda->contains(AppMenu::KEY_TUNJANGAN));
    }

    public function test_launch_dan_webview_enter_ke_talim_tunjangan(): void
    {
        $guru = $this->buatGuru(nrg: 'NRG-88');
        Sanctum::actingAs($guru);

        $menu = AppMenu::query()
            ->where('key', AppMenu::KEY_TUNJANGAN)
            ->where('audience', AppMenu::AUDIENCE_GURU)
            ->firstOrFail();

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
            ->assertRedirect('https://madani.mtsn11majalengka.sch.id/talim/tunjangan');

        $this->assertAuthenticatedAs($guru, 'web');
        $this->assertFalse(Cache::has('app_menu_webview_ticket:'.$ticket));
    }

    public function test_guru_tersertifikasi_bisa_buka_hub_talim(): void
    {
        $guru = $this->buatGuru(nrg: 'NRG-77');

        $this->actingAs($guru)
            ->get(route('talim.tunjangan.index'))
            ->assertOk()
            ->assertSee('SKMT', false)
            ->assertSee('SKAKPT', false);
    }

    public function test_guru_tanpa_nrg_ditolak_di_halaman_talim(): void
    {
        $guru = $this->buatGuru(nrg: null);

        $this->actingAs($guru)
            ->get(route('talim.tunjangan.index'))
            ->assertOk()
            ->assertSee('Anda tidak memiliki akses Tunjangan', false);
    }

    public function test_upload_skakpt_bulan_terkunci_di_talim(): void
    {
        Storage::fake('r2');
        $this->seed();
        $this->travelTo(now()->setDate(2027, 3, 15));
        $ta = TahunAjaran::aktif();
        $guru = $this->buatGuru(nrg: 'NRG-55');

        $this->actingAs($guru)
            ->post(route('talim.tunjangan.upload', 'skakpt'), [
                'periode' => 3,
                'tahun_ajaran_id' => $ta->id,
                'file' => UploadedFile::fake()->create('x.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');

        $this->actingAs($guru)
            ->post(route('talim.tunjangan.upload', 'skakpt'), [
                'periode' => 2,
                'tahun_ajaran_id' => $ta->id,
                'file' => UploadedFile::fake()->create('ok.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tunjangan_dokumens', [
            'gtk_id' => $guru->gtk_id,
            'jenis' => 'skakpt',
            'periode' => 2,
            'tahun_ajaran_id' => $ta->id,
        ]);
    }

    public function test_preview_halaman_talim_membuka_viewer(): void
    {
        Storage::fake('r2');
        $this->seed();
        $this->travelTo(now()->setDate(2027, 3, 15));
        $ta = TahunAjaran::aktif();
        $guru = $this->buatGuru(nrg: 'NRG-33');

        $dokumen = app(TunjanganDokumenService::class)->simpanPdf(
            $guru->gtk,
            'skakpt',
            2,
            UploadedFile::fake()->create('lihat.pdf', 100, 'application/pdf'),
            null,
            $ta,
        );

        $response = $this->actingAs($guru)
            ->get(route('talim.tunjangan.preview', ['jenis' => 'skakpt', 'dokumen' => $dokumen]))
            ->assertOk()
            ->assertSee('Memuat PDF', false);

        $this->assertStringContainsString(
            'dokumen\\/'.$dokumen->id.'\\/stream',
            $response->getContent()
        );
    }

    public function test_sptjm_unduh_dari_talim(): void
    {
        $this->seed();
        $guru = $this->buatGuru(nrg: 'NRG-44');

        $response = $this->actingAs($guru)
            ->post(route('talim.tunjangan.sptjm'), [
                'tanggal_surat' => '2026-08-15',
                'mode' => 'download',
            ]);

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    private function buatGuru(?string $nrg): User
    {
        Role::findOrCreate(Peran::GURU);
        $gtk = Gtk::query()->create([
            'nama' => 'Guru Talim Tunjangan',
            'nip' => (string) fake()->unique()->numerify('##################'),
            'nuptk' => (string) fake()->unique()->numerify('##############'),
            'nrg' => $nrg,
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $user = User::factory()->create([
            'username' => $gtk->nip,
            'gtk_id' => $gtk->id,
            'is_aktif' => true,
        ]);
        $user->syncRoles([Peran::GURU]);

        return $user->fresh(['gtk']);
    }
}
