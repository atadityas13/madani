<?php

namespace Tests\Feature;

use App\Jobs\SendNotifikasiFcmJob;
use App\Models\Gtk;
use App\Models\IzinSiswa;
use App\Models\JurnalPembelajaran;
use App\Models\Notifikasi;
use App\Models\OrangTua;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\Peran;
use App\Support\SuratIzinSiswa;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IzinSiswaApiTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function test_siswa_can_create_izin_with_ttd_and_notifies_wali_and_mapel_guru(): void
    {
        Storage::fake('r2');
        Queue::fake();
        $this->seed();

        ['siswa' => $siswa, 'token' => $token, 'rombel' => $rombel, 'wali' => $wali] = $this->buatSiswaDenganRombel();
        $guruMapel = $this->buatAkunGuru('198801012010011099', 'Guru Mapel Hari Ini');
        $rombel->update(['source_simpatisans_kelas_id' => 4242]);

        $hari = match (now()->dayOfWeek) {
            Carbon::MONDAY => 'Senin',
            Carbon::TUESDAY => 'Selasa',
            Carbon::WEDNESDAY => 'Rabu',
            Carbon::THURSDAY => 'Kamis',
            Carbon::FRIDAY => 'Jumat',
            Carbon::SATURDAY => 'Sabtu',
            default => 'Minggu',
        };

        JurnalPembelajaran::query()->create([
            'user_id' => $guruMapel->id,
            'kelas_id' => 4242,
            'nama_kelas' => $rombel->label(),
            'mapel_id' => 1,
            'nama_mapel' => 'Matematika',
            'tanggal' => now()->toDateString(),
            'hari' => $hari,
            'jam_ke' => 1,
            'materi_pokok' => 'Materi uji',
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/siswa/izin/meta')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['teks_pernyataan_izin' => ['teks', 'versi', 'template']]])
            ->assertJsonPath('data.teks_pernyataan_izin.versi', 4)
            ->assertJsonPath(
                'data.teks_pernyataan_izin.template',
                'Saya selaku orang tua/wali menyatakan bahwa anak saya tidak dapat hadir ke madrasah pada tanggal {tanggal} karena {jenis}{alasan}, dan saya bertanggungjawab atas kebenaran laporan ketidakhadiran ini.'
            );

        $tanggal = now()->toDateString();
        $this->withToken($token)
            ->postJson('/api/v1/siswa/izin', [
                'jenis' => 'sakit',
                'tanggal' => $tanggal,
                'alasan' => 'Demam tinggi sejak malam',
                'pernyataan_disetujui' => true,
                'ttd_wali' => self::PNG_1X1,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.jenis', 'sakit')
            ->assertJsonPath('data.status', 'aktif')
            ->assertJsonPath('data.rombel', $rombel->label());

        $this->assertDatabaseHas('izin_siswas', [
            'siswa_id' => $siswa->id,
            'jenis' => 'sakit',
            'status' => 'aktif',
            'rombel_id' => $rombel->id,
        ]);

        $notifikasi = Notifikasi::query()
            ->where('audience', Notifikasi::AUDIENCE_GTK)
            ->where('jenis', Notifikasi::JENIS_NOTIFIKASI)
            ->latest('id')
            ->first();
        $this->assertNotNull($notifikasi);
        $this->assertEqualsCanonicalizing(
            [(int) $wali->gtk_id, (int) $guruMapel->gtk_id],
            array_map('intval', $notifikasi->audience_ids ?? [])
        );

        Queue::assertPushed(SendNotifikasiFcmJob::class);

        $this->withToken($token)
            ->getJson('/api/v1/siswa/izin')
            ->assertOk()
            ->assertJsonPath('data.0.jenis', 'sakit')
            ->assertJsonPath('data.0.punya_surat', true);

        $izinId = IzinSiswa::query()->where('siswa_id', $siswa->id)->value('id');
        $pdf = $this->withToken($token)
            ->get("/api/v1/siswa/izin/{$izinId}/surat.pdf")
            ->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_siswa_can_upload_lampiran_and_view_surat_with_bukti(): void
    {
        Storage::fake('r2');
        Queue::fake();
        $this->seed();

        ['siswa' => $siswa, 'token' => $token, 'rombel' => $rombel, 'wali' => $wali] = $this->buatSiswaDenganRombel();

        $this->withToken($token)
            ->postJson('/api/v1/siswa/izin', [
                'jenis' => 'sakit',
                'tanggal' => now()->toDateString(),
                'alasan' => 'Demam dan batuk',
                'pernyataan_disetujui' => true,
                'ttd_wali' => self::PNG_1X1,
                'lampiran' => self::PNG_1X1,
                'jenis_bukti' => 'surat keterangan sakit dari dokter',
            ])
            ->assertCreated()
            ->assertJsonPath('data.punya_lampiran', true)
            ->assertJsonPath('data.jenis_bukti', 'surat keterangan sakit dari dokter');

        $izin = IzinSiswa::query()->where('siswa_id', $siswa->id)->first();
        $this->assertNotNull($izin);
        $this->assertNotNull($izin->lampiran_path);
        $this->assertTrue(Storage::disk('r2')->exists($izin->lampiran_path));

        $pdf = $this->withToken($token)
            ->get("/api/v1/siswa/izin/{$izin->id}/surat.pdf")
            ->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
        $content = $pdf->getContent();
        $this->assertStringStartsWith('%PDF', $content);

        Sanctum::actingAs($wali);
        $guruPdf = $this->get("/api/v1/guru/izin/{$izin->id}/surat.pdf")->assertOk();
        $this->assertSame('application/pdf', $guruPdf->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $guruPdf->getContent());
    }

    public function test_surat_izin_uses_nama_orang_tua_from_siswa_data(): void
    {
        Storage::fake('r2');
        Queue::fake();
        $this->seed();

        ['siswa' => $siswa, 'token' => $token] = $this->buatSiswaDenganRombel();
        OrangTua::query()->create([
            'siswa_id' => $siswa->id,
            'peran' => 'ayah',
            'nama' => 'Ahmad Fulan',
            'nik' => '3210010101700099',
            'status_hidup' => 'hidup',
        ]);
        OrangTua::query()->create([
            'siswa_id' => $siswa->id,
            'peran' => 'ibu',
            'nama' => 'Siti Aminah',
            'nik' => '3210010101720099',
            'status_hidup' => 'hidup',
        ]);
        OrangTua::query()->create([
            'siswa_id' => $siswa->id,
            'peran' => 'wali',
            'status' => 'Sama dengan ayah kandung',
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/siswa/izin', $this->payload([
                'jenis' => 'izin',
                'alasan' => 'Keperluan keluarga',
            ]))
            ->assertCreated()
            ->assertJsonPath('data.nama_wali', 'Ahmad Fulan');

        $izin = IzinSiswa::query()->where('siswa_id', $siswa->id)->first();
        $this->assertNotNull($izin);
        $this->assertSame('Ahmad Fulan', $izin->nama_wali);
        $this->assertSame('Ahmad Fulan', SuratIzinSiswa::payload($izin)['nama_wali']);
    }

    public function test_lampiran_requires_jenis_bukti(): void
    {
        Storage::fake('r2');
        $this->seed();
        ['token' => $token] = $this->buatSiswaDenganRombel();

        $this->withToken($token)
            ->postJson('/api/v1/siswa/izin', [
                'jenis' => 'izin',
                'tanggal' => now()->toDateString(),
                'alasan' => 'Ada keperluan keluarga',
                'pernyataan_disetujui' => true,
                'ttd_wali' => self::PNG_1X1,
                'lampiran' => self::PNG_1X1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('jenis_bukti');
    }

    public function test_siswa_resubmit_same_day_replaces_active_izin(): void
    {
        Storage::fake('r2');
        Queue::fake();
        $this->seed();

        ['token' => $token] = $this->buatSiswaDenganRombel();
        $tanggal = now()->toDateString();

        $this->withToken($token)
            ->postJson('/api/v1/siswa/izin', $this->payload(['jenis' => 'izin', 'tanggal' => $tanggal]))
            ->assertCreated();

        $this->withToken($token)
            ->postJson('/api/v1/siswa/izin', $this->payload(['jenis' => 'sakit', 'tanggal' => $tanggal, 'alasan' => 'Sakit perut mendadak']))
            ->assertCreated()
            ->assertJsonPath('data.jenis', 'sakit')
            ->assertJsonPath('data.alasan', 'Sakit perut mendadak');

        $this->assertSame(1, IzinSiswa::query()->where('status', 'aktif')->count());
    }

    public function test_create_rejected_without_pernyataan_or_ttd(): void
    {
        Storage::fake('r2');
        $this->seed();
        ['token' => $token] = $this->buatSiswaDenganRombel();

        $this->withToken($token)
            ->postJson('/api/v1/siswa/izin', [
                'jenis' => 'izin',
                'tanggal' => now()->toDateString(),
                'alasan' => 'Ada keperluan keluarga',
                'pernyataan_disetujui' => false,
                'ttd_wali' => self::PNG_1X1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('pernyataan_disetujui');

        $this->withToken($token)
            ->postJson('/api/v1/siswa/izin', [
                'jenis' => 'izin',
                'tanggal' => now()->toDateString(),
                'alasan' => 'Ada keperluan keluarga',
                'pernyataan_disetujui' => true,
                'ttd_wali' => 'bukan-gambar',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('ttd_wali');
    }

    public function test_guru_sees_rekap_hari_ini_and_only_wali_can_batalkan(): void
    {
        Storage::fake('r2');
        Queue::fake();
        $this->seed();

        ['siswa' => $siswa, 'token' => $tokenSiswa, 'rombel' => $rombel, 'wali' => $wali] = $this->buatSiswaDenganRombel();
        $guruLain = $this->buatAkunGuru('198801012010011002', 'Guru Lain');

        $this->withToken($tokenSiswa)
            ->postJson('/api/v1/siswa/izin', $this->payload([
                'jenis' => 'izin',
                'tanggal' => now()->toDateString(),
                'alasan' => 'Urusan keluarga di luar kota',
            ]))
            ->assertCreated();

        $izinId = IzinSiswa::query()->where('siswa_id', $siswa->id)->value('id');

        Sanctum::actingAs($guruLain);
        $this->getJson('/api/v1/guru/izin/hari-ini')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.izin', 1)
            ->assertJsonPath('data.sakit', 0)
            ->assertJsonPath('data.alpa', 0)
            ->assertJsonPath('data.items.0.bisa_batalkan', false)
            ->assertJsonPath('data.items.0.nama', $siswa->nama);

        $this->postJson("/api/v1/guru/izin/{$izinId}/batalkan", ['alasan_batal' => 'Tidak valid'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('izin');

        Sanctum::actingAs($wali);
        $this->getJson('/api/v1/guru/izin/hari-ini')
            ->assertOk()
            ->assertJsonPath('data.items.0.bisa_batalkan', true)
            ->assertJsonPath('data.items.0.rombel', $rombel->label());

        $this->postJson("/api/v1/guru/izin/{$izinId}/batalkan", ['alasan_batal' => 'Siswa ternyata hadir'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Laporan ketidakhadiran dihapus.');

        $this->assertDatabaseMissing('izin_siswas', [
            'id' => $izinId,
        ]);

        $this->assertDatabaseHas('notifikasis', [
            'audience' => Notifikasi::AUDIENCE_SISWA,
        ]);

        $this->getJson('/api/v1/guru/izin/hari-ini')
            ->assertOk()
            ->assertJsonPath('data.total', 0);
    }

    public function test_guru_can_laporkan_alpa_and_see_rekap_sia(): void
    {
        Storage::fake('r2');
        Queue::fake();
        $this->seed();

        ['siswa' => $siswa, 'rombel' => $rombel, 'wali' => $wali] = $this->buatSiswaDenganRombel();
        $siswa2 = Siswa::query()->create([
            'nama' => 'Siswa Alpa Dua',
            'nisn' => '9988776656',
            'nik' => '3210010101120098',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-06-01',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif',
        ]);
        $siswa2->rombels()->attach($rombel->id, ['status' => 'aktif']);

        Sanctum::actingAs($wali);
        $this->getJson('/api/v1/guru/izin/rombels')
            ->assertOk()
            ->assertJsonPath('data.0.id', $rombel->id);

        $this->getJson("/api/v1/guru/izin/rombels/{$rombel->id}/siswa")
            ->assertOk()
            ->assertJsonPath('data.siswa.0.sudah_lapor', false);

        $this->postJson('/api/v1/guru/izin/alpa', [
            'rombel_id' => $rombel->id,
            'siswa_ids' => [$siswa->id, $siswa2->id],
            'tanggal' => now()->toDateString(),
        ])
            ->assertCreated()
            ->assertJsonPath('data.created', 2)
            ->assertJsonPath('data.updated', 0);

        $this->assertDatabaseHas('izin_siswas', [
            'siswa_id' => $siswa->id,
            'jenis' => 'alpa',
            'status' => 'aktif',
            'dilaporkan_oleh' => $wali->id,
        ]);

        $this->getJson('/api/v1/guru/izin/hari-ini')
            ->assertOk()
            ->assertJsonPath('data.alpa', 2)
            ->assertJsonPath('data.total', 2);

        $this->getJson('/api/v1/guru/izin/rekap-sia')
            ->assertOk()
            ->assertJsonPath('data.totals.alpa', 2)
            ->assertJsonPath('data.totals.sakit', 0)
            ->assertJsonPath('data.totals.izin', 0)
            ->assertJsonPath('data.totals.total', 2)
            ->assertJsonPath('data.rows.0.rombel', $rombel->label())
            ->assertJsonPath('data.rows.0.alpa', 2);
    }

    public function test_rombels_and_rekap_sia_are_ordered_by_tingkat_then_numeric_nama(): void
    {
        Queue::fake();
        $this->seed();

        $wali = $this->buatAkunGuru('197901012005011099', 'Wali Urut');
        $tahun = TahunAjaran::aktif();
        $this->assertNotNull($tahun);

        $delapanSatu = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VIII',
            'nama' => '1',
            'program' => 'Reguler',
            'gtk_id' => $wali->gtk_id,
        ]);
        $tujuhSepuluh = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => '10',
            'program' => 'Reguler',
            'gtk_id' => $wali->gtk_id,
        ]);
        $tujuhDua = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => '2',
            'program' => 'Reguler',
            'gtk_id' => $wali->gtk_id,
        ]);

        Sanctum::actingAs($wali);

        $rombels = $this->getJson('/api/v1/guru/izin/rombels')
            ->assertOk()
            ->json('data');

        $this->assertSame(
            [$tujuhDua->id, $tujuhSepuluh->id, $delapanSatu->id],
            array_column($rombels, 'id'),
        );
        $this->assertSame(0, $rombels[0]['jumlah_siswa']);

        $rows = $this->getJson('/api/v1/guru/izin/rekap-sia')
            ->assertOk()
            ->json('data.rows');

        $this->assertSame(
            [$tujuhDua->label(), $tujuhSepuluh->label(), $delapanSatu->label()],
            array_column($rows, 'rombel'),
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'jenis' => 'izin',
            'tanggal' => now()->toDateString(),
            'alasan' => 'Keperluan keluarga penting',
            'pernyataan_disetujui' => true,
            'ttd_wali' => self::PNG_1X1,
        ], $overrides);
    }

    /**
     * @return array{siswa: Siswa, token: string, rombel: Rombel, wali: User}
     */
    private function buatSiswaDenganRombel(): array
    {
        $wali = $this->buatAkunGuru('197901012005011001', 'Wali Kelas');
        $tahun = TahunAjaran::aktif();
        $this->assertNotNull($tahun);

        $rombel = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VIII',
            'nama' => '2',
            'program' => 'Reguler',
            'gtk_id' => $wali->gtk_id,
        ]);

        $siswa = Siswa::query()->create([
            'nama' => 'Siswa Izin',
            'nisn' => '9988776655',
            'nik' => '3210010101120099',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-05-01',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif',
        ]);
        $siswa->rombels()->attach($rombel->id, ['status' => 'aktif']);
        $siswa->gantiPassword('sandibaru1');

        $token = $this->postJson('/api/v1/siswa/login', [
            'nisn' => $siswa->nisn,
            'password' => 'sandibaru1',
        ])->assertOk()->json('token');

        return compact('siswa', 'token', 'rombel', 'wali');
    }

    private function buatAkunGuru(string $nip, string $nama): User
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
            'password' => 'password123',
            'is_aktif' => true,
            'gtk_id' => $gtk->id,
        ]);
        $user->syncRoles([Peran::GURU]);

        return $user->fresh()->load('gtk');
    }
}
