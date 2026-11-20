<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncGtkFromSimpatisansTest extends TestCase
{
    use RefreshDatabase;

    public function test_perbandingan_menampilkan_tambahan_pembaruan_dan_calon_hapus(): void
    {
        $this->actingAsOperator();
        $this->configureSimpatisans();

        $cocok = Gtk::query()->create([
            'nama' => 'Budi Santoso',
            'nip' => '1980 0101 200501 1 001',
            'nomor_hp' => '08111',
            'duk' => '9',
            'jenis' => Gtk::JENIS_GURU,
            'status' => 'aktif',
        ]);
        $tanpaNip = Gtk::query()->create([
            'nama' => 'Siti Aminah',
            'nuptk' => '1234567890123456',
            'jenis' => Gtk::JENIS_GURU,
            'status' => 'aktif',
        ]);
        $hanyaMadani = Gtk::query()->create([
            'nama' => 'Tidak Ada di Simpatisans',
            'nip' => '197001012000011001',
            'jenis' => Gtk::JENIS_GURU,
            'status' => 'aktif',
        ]);
        $tendik = Gtk::query()->create([
            'nama' => 'Tendik Tetap Ada',
            'nip' => '196501011990011002',
            'jenis' => Gtk::JENIS_TENDIK,
            'status' => 'aktif',
        ]);

        $this->get(route('gtk.index'))
            ->assertOk()
            ->assertSee('Sync Simpatisans');

        Http::fake([
            '*/madani/gurus' => Http::response([
                'success' => true,
                'data' => [
                    $this->guruRemote([
                        'nip' => '198001012005011001',
                        'nama' => 'Nama Dari Simpatisans',
                        'duk' => 2,
                        'nomor_hp' => '08999',
                        'email' => 'budi@example.com',
                        'mapel_ijazah' => 'Matematika',
                    ]),
                    $this->guruRemote([
                        'nip' => '198502022010012002',
                        'kode_guru' => 'ST',
                        'nama' => 'Siti Aminah',
                        'nuptk' => '1234 5678 9012 3456',
                        'duk' => 4,
                    ]),
                    $this->guruRemote([
                        'nip' => '199003032015011003',
                        'kode_guru' => 'AN',
                        'nama' => 'Andi Nugroho',
                        'duk' => 11,
                        'status_sertifikasi' => true,
                    ]),
                ],
            ]),
        ]);

        $this->get(route('gtk.sync-simpatisans'))
            ->assertOk()
            ->assertSee('Andi Nugroho')
            ->assertSee('Akan diperbarui')
            ->assertSee('DUK')
            ->assertSee('9 → 2')
            ->assertSee('kosong → budi@example.com')
            ->assertSee('Tidak Ada di Simpatisans')
            ->assertSee('Hapus')
            ->assertDontSee('Nama Dari Simpatisans')
            ->assertDontSee(route('gtk.destroy', $tendik), false)
            ->assertSee(route('gtk.destroy', $hanyaMadani), false);

        $this->post(route('gtk.sync-simpatisans.terapkan'))
            ->assertRedirect(route('gtk.sync-simpatisans'));

        $cocok->refresh();
        $tanpaNip->refresh();

        $this->assertSame('Budi Santoso', $cocok->nama);
        $this->assertSame('08111', $cocok->nomor_hp);
        $this->assertSame('2', $cocok->duk);
        $this->assertSame('budi@example.com', $cocok->email);
        $this->assertSame('Matematika', $cocok->mapel_ijazah);
        $this->assertFalse($cocok->status_sertifikasi);

        $this->assertSame('198502022010012002', $tanpaNip->nip);
        $this->assertSame('4', $tanpaNip->duk);
        $this->assertSame('Siti Aminah', $tanpaNip->nama);

        $baru = Gtk::query()->where('nip', '199003032015011003')->first();
        $this->assertNotNull($baru);
        $this->assertSame('Andi Nugroho', $baru->nama);
        $this->assertSame(Gtk::JENIS_GURU, $baru->jenis);
        $this->assertSame('aktif', $baru->status);
        $this->assertTrue($baru->status_sertifikasi);
        $this->assertSame('11', $baru->duk);
        $this->assertSame('AN', $baru->kode_internal);

        $this->assertNotNull(Gtk::query()->find($hanyaMadani->id));
        $this->assertNotNull(Gtk::query()->find($tendik->id));

        Http::assertSent(function ($request): bool {
            return str_ends_with($request->url(), '/madani/gurus')
                && $request->hasHeader('X-Madani-Introspect-Secret', 'test-secret');
        });
    }

    public function test_nuptk_sama_dengan_nip_berbeda_tidak_digabung(): void
    {
        $this->actingAsOperator();
        $this->configureSimpatisans();

        Gtk::query()->create([
            'nama' => 'Guru Madani',
            'nip' => '197512122000011004',
            'nuptk' => '9988776655443322',
            'jenis' => Gtk::JENIS_GURU,
            'status' => 'aktif',
        ]);

        Http::fake([
            '*/madani/gurus' => Http::response([
                'data' => [
                    $this->guruRemote([
                        'nip' => '198811112015011005',
                        'nama' => 'Guru Simpatisans',
                        'nuptk' => '9988776655443322',
                    ]),
                ],
            ]),
        ]);

        $this->get(route('gtk.sync-simpatisans'))
            ->assertOk()
            ->assertSee('NUPTK sama, NIP berbeda')
            ->assertSee('Guru Madani')
            ->assertDontSee('Akan ditambahkan (1)');

        $this->post(route('gtk.sync-simpatisans.terapkan'))
            ->assertRedirect(route('gtk.sync-simpatisans'));

        $this->assertSame(1, Gtk::query()->count());
        $this->assertSame('Guru Madani', Gtk::query()->first()->nama);
    }

    public function test_hapus_hanya_setelah_konfirmasi_dan_wali_kelas_tidak_bisa_dihapus(): void
    {
        $this->actingAsOperator();
        $this->configureSimpatisans();

        $boleh = Gtk::query()->create([
            'nama' => 'Boleh Dihapus',
            'nip' => '197001012000011001',
            'jenis' => Gtk::JENIS_GURU,
            'status' => 'aktif',
        ]);
        $wali = Gtk::query()->create([
            'nama' => 'Masih Wali',
            'nip' => '197202022000011002',
            'jenis' => Gtk::JENIS_GURU,
            'status' => 'aktif',
        ]);
        $tahun = TahunAjaran::query()->first();
        Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => 'A',
            'gtk_id' => $wali->id,
        ]);

        Http::fake([
            '*/madani/gurus' => Http::response(['data' => []]),
        ]);

        $this->get(route('gtk.sync-simpatisans'))
            ->assertOk()
            ->assertSee('Masih wali kelas')
            ->assertSee(route('gtk.destroy', $boleh), false);

        $this->delete(route('gtk.destroy', $wali), ['kembali' => 'sync'])
            ->assertRedirect()
            ->assertSessionHas('status', 'GTK tidak dapat dihapus karena masih menjadi wali kelas.');
        $this->assertNotNull(Gtk::query()->find($wali->id));

        $this->delete(route('gtk.destroy', $boleh), ['kembali' => 'sync'])
            ->assertRedirect(route('gtk.sync-simpatisans'));
        $this->assertNull(Gtk::query()->find($boleh->id));
    }

    public function test_terapkan_tanpa_perbandingan_ditolak(): void
    {
        $this->actingAsOperator();
        $this->configureSimpatisans();

        $this->post(route('gtk.sync-simpatisans.terapkan'))
            ->assertRedirect(route('gtk.sync-simpatisans'))
            ->assertSessionHas('error', 'Bandingkan data Simpatisans terlebih dahulu.');
    }

    public function test_konfigurasi_kosong_dan_guru_tidak_punya_akses(): void
    {
        $this->actingAsOperator();

        $this->get(route('gtk.sync-simpatisans'))
            ->assertRedirect(route('gtk.index'))
            ->assertSessionHas('error', 'Konfigurasi SimpatiSans belum lengkap (SIMPATISANS_API_URL / MADANI_INTROSPECT_SECRET).');

        $guru = User::factory()->create();
        $guru->assignRole('guru');

        $this->actingAs($guru)
            ->get(route('gtk.sync-simpatisans'))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function guruRemote(array $override): array
    {
        return array_merge([
            'nip' => '198001012005011001',
            'kode_guru' => 'BD',
            'duk' => 1,
            'nama' => 'Budi Santoso',
            'gelar_depan' => '-',
            'gelar_belakang' => null,
            'jenis_kelamin' => 'Laki-laki',
            'nuptk' => null,
            'nomor_hp' => null,
            'email' => null,
            'status_sertifikasi' => false,
            'is_bk' => false,
            'mapel_ijazah' => null,
            'mapel_sertifikasi' => null,
        ], $override);
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
