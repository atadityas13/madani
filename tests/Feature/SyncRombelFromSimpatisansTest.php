<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\Simpatisans\RombelSyncService;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncRombelFromSimpatisansTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_gagal_jika_ada_rombel_tanpa_wali(): void
    {
        $this->actingAsOperator();
        $this->configureSimpatisans();

        Http::fake([
            '*/madani/rombels' => Http::response($this->payload([
                [
                    'kelas_id' => 12,
                    'nama_kelas' => 'Kelas VII.1',
                    'tingkat' => 'VII',
                    'nama' => '1',
                    'wali' => ['nip' => '198001012005011001', 'nama' => 'Budi'],
                ],
                [
                    'kelas_id' => 13,
                    'nama_kelas' => 'Kelas VII.2',
                    'tingkat' => 'VII',
                    'nama' => '2',
                    'wali' => null,
                ],
            ], tanpaWali: 1)),
        ]);

        $this->post(route('rombel.sync-simpatisans'))
            ->assertRedirect(route('rombel.index'))
            ->assertSessionHas('error', RombelSyncService::MESSAGE_TANPA_WALI);

        $this->assertSame(0, Rombel::query()->count());
    }

    public function test_sync_gagal_jika_tahun_ajaran_tidak_cocok(): void
    {
        $this->actingAsOperator();
        $this->configureSimpatisans();

        Http::fake([
            '*/madani/rombels' => Http::response($this->payload([
                [
                    'kelas_id' => 12,
                    'nama_kelas' => 'Kelas VII.1',
                    'tingkat' => 'VII',
                    'nama' => '1',
                    'wali' => ['nip' => '198001012005011001', 'nama' => 'Budi'],
                ],
            ], tahun: '2025/2026')),
        ]);

        $this->post(route('rombel.sync-simpatisans'))
            ->assertRedirect(route('rombel.index'))
            ->assertSessionHas('error');

        $this->assertStringContainsString(
            'Tahun ajaran tidak cocok',
            (string) session('error')
        );
        $this->assertSame(0, Rombel::query()->count());
    }

    public function test_sync_berhasil_upsert_rombel_dengan_wali(): void
    {
        $this->actingAsOperator();
        $this->configureSimpatisans();

        $gtk = Gtk::query()->create([
            'nama' => 'Budi Santoso',
            'nip' => '198001012005011001',
            'status' => 'aktif',
            'jenis' => 'guru',
            'jenis_kelamin' => 'L',
        ]);

        $tahun = TahunAjaran::aktif();
        $this->assertNotNull($tahun);

        Http::fake([
            '*/madani/rombels' => Http::response($this->payload([
                [
                    'kelas_id' => 12,
                    'nama_kelas' => 'Kelas VII.1',
                    'tingkat' => 'VII',
                    'nama' => '1',
                    'wali' => ['nip' => '198001012005011001', 'nama' => 'Budi Santoso'],
                ],
            ])),
        ]);

        $this->post(route('rombel.sync-simpatisans'))
            ->assertRedirect(route('rombel.index'))
            ->assertSessionHas('status');

        $rombel = Rombel::query()->first();
        $this->assertNotNull($rombel);
        $this->assertSame(12, $rombel->source_simpatisans_kelas_id);
        $this->assertSame('VII', $rombel->tingkat);
        $this->assertSame('1', $rombel->nama);
        $this->assertSame($gtk->id, $rombel->gtk_id);
        $this->assertSame($tahun->id, $rombel->tahun_ajaran_id);
    }

    public function test_sync_gagal_jika_nip_gtk_belum_ada(): void
    {
        $this->actingAsOperator();
        $this->configureSimpatisans();

        Http::fake([
            '*/madani/rombels' => Http::response($this->payload([
                [
                    'kelas_id' => 12,
                    'nama_kelas' => 'Kelas VII.1',
                    'tingkat' => 'VII',
                    'nama' => '1',
                    'wali' => ['nip' => '199999999999999999', 'nama' => 'Tidak Ada'],
                ],
            ])),
        ]);

        $this->post(route('rombel.sync-simpatisans'))
            ->assertRedirect(route('rombel.index'))
            ->assertSessionHas('error');

        $this->assertSame(0, Rombel::query()->count());
    }

    public function test_wali_tidak_bisa_sync(): void
    {
        $this->seed();
        $gtk = Gtk::query()->create([
            'nama' => 'Wali Only',
            'nip' => '198001012005011099',
            'status' => 'aktif',
            'jenis' => 'guru',
            'jenis_kelamin' => 'L',
        ]);

        $user = User::factory()->create([
            'gtk_id' => $gtk->id,
            'is_aktif' => true,
        ]);
        $user->syncRoles([Peran::WALI_KELAS]);

        $this->actingAs($user)
            ->post(route('rombel.sync-simpatisans'))
            ->assertForbidden();
    }

    public function test_index_hanya_ikon_kelola_siswa(): void
    {
        $this->actingAsOperator();

        $tahun = TahunAjaran::aktif();
        Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => '1',
        ]);

        $this->get(route('rombel.index'))
            ->assertOk()
            ->assertSee('bi-people', false)
            ->assertDontSee('bi-pencil', false)
            ->assertDontSee('Ubah rombel', false);
    }

    /**
     * @param  list<array<string, mixed>>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data, string $tahun = '2026/2027', int $tanpaWali = 0): array
    {
        $total = count($data);

        return [
            'tahun_ajaran' => ['nama' => $tahun],
            'semester' => [
                'id' => 1,
                'nama' => $tahun.' - Ganjil',
                'tahun' => $tahun,
                'tipe' => 'Ganjil',
            ],
            'meta' => [
                'total' => $total,
                'dengan_wali' => $total - $tanpaWali,
                'tanpa_wali' => $tanpaWali,
            ],
            'data' => $data,
        ];
    }

    private function configureSimpatisans(): void
    {
        config([
            'services.simpatisans.base_url' => 'https://simpatisans.test/api',
            'services.simpatisans.secret' => 'test-secret',
        ]);
    }

    private function actingAsOperator(): static
    {
        $this->seed();

        return $this->actingAs(User::query()->where('username', 'admin')->first());
    }
}
