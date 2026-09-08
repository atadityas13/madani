<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\OrangTua;
use App\Models\RekamDidik;
use App\Models\Siswa;
use App\Models\SiswaPeriodik;
use App\Models\SiswaPernyataan;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\KartuEPelajarPdfService;
use App\Support\PernyataanSiswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SiswaKartuEPelajarTest extends TestCase
{
    use RefreshDatabase;

    public function test_siswa_cannot_fetch_kartu_when_incomplete(): void
    {
        $this->seed();
        [$token] = $this->siswaWithKartuData(denganPernyataan: false, lengkap: false);

        $this->withToken($token)
            ->getJson('/api/v1/siswa/kartu-e-pelajar')
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Lengkapi semua data wajib terlebih dahulu sebelum membuka kartu atau portofolio.');
    }

    public function test_siswa_cannot_fetch_kartu_when_lengkap_but_unconfirmed(): void
    {
        Storage::fake('r2');
        $this->seed();
        [$token] = $this->siswaWithKartuData(denganPernyataan: false, lengkap: true);

        $this->withToken($token)
            ->getJson('/api/v1/siswa/kartu-e-pelajar')
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Konfirmasi pernyataan terlebih dahulu sebelum membuka kartu atau portofolio.');
    }

    public function test_siswa_can_fetch_kartu_e_pelajar_payload(): void
    {
        Storage::fake('r2');
        $this->seed();
        [$token, $siswa] = $this->siswaWithKartuData(denganPernyataan: true, lengkap: true);

        $this->withToken($token)
            ->getJson('/api/v1/siswa/kartu-e-pelajar')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nama', $siswa->nama)
            ->assertJsonPath('data.nisn', $siswa->nisn)
            ->assertJsonPath('data.nis', $siswa->nis)
            ->assertJsonPath('data.jenis_kelamin_label', 'Laki-laki')
            ->assertJsonPath('data.alamat', 'Blok A, RT. 001 RW. 002 Desa Rawa Kec. Cingambul Kab. Majalengka, Jawa Barat, 45467')
            ->assertJsonStructure([
                'data' => [
                    'ttl',
                    'verify_url',
                    'madrasah' => ['nama', 'alamat', 'kontak', 'logo_kemenag_url'],
                ],
            ]);
    }

    public function test_signed_kartu_verification_page_shows_siswa(): void
    {
        Storage::fake('r2');
        $this->seed();
        [, $siswa] = $this->siswaWithKartuData(denganPernyataan: true, lengkap: true);

        $url = URL::signedRoute('kartu-e-pelajar.cek', ['siswa' => $siswa->id]);

        $this->get($url)
            ->assertOk()
            ->assertSee('Kartu E-Pelajar Terverifikasi')
            ->assertSee($siswa->nama)
            ->assertSee($siswa->nisn);
    }

    public function test_unsigned_kartu_verification_is_forbidden(): void
    {
        Storage::fake('r2');
        $this->seed();
        [, $siswa] = $this->siswaWithKartuData(denganPernyataan: true, lengkap: true);

        $this->get(route('kartu-e-pelajar.cek', ['siswa' => $siswa->id]))
            ->assertForbidden();
    }

    public function test_admin_kartu_preview_terpisah_dari_halaman_verifikasi_siswa(): void
    {
        Storage::fake('r2');
        $this->seed();
        [, $siswa] = $this->siswaWithKartuData(denganPernyataan: true, lengkap: true);
        $admin = User::query()->where('username', 'admin')->first();

        $this->actingAs($admin)
            ->get(route('siswa.kartu', $siswa))
            ->assertOk()
            ->assertSee('Preview kartu e-pelajar', false)
            ->assertDontSee('Kartu E-Pelajar Terverifikasi', false);

        $this->actingAs($admin)
            ->get(route('siswa.kartu.stream', $siswa))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $viewData = app(KartuEPelajarPdfService::class)->viewData($siswa);
        $this->assertNotNull($viewData['logoMadaniDataUri']);
        $this->assertNotNull($viewData['fotoPlaceholderDataUri']);
        $this->assertNotNull($viewData['bgBelakangDataUri']);
        $this->assertStringStartsWith('data:image/png;base64,', $viewData['logoMadaniDataUri']);
        $this->assertStringStartsWith('data:image/jpeg;base64,', $viewData['bgBelakangDataUri']);

        $html = view('siswa.kartu-e-pelajar-pdf', $viewData)->render();
        $this->assertStringContainsString('KARTU PELAJAR', $html);
        $this->assertStringContainsString('IKRAR PELAJAR INDONESIA', $html);
        $this->assertStringContainsString('Berlaku selama menjadi siswa', $html);
        $this->assertStringContainsString('Kami Pelajar Indonesia, berikrar untuk:', $html);
        $this->assertStringContainsString('Kartu Pelajar ini merupakan dokumen resmi yang sah', $html);
        $this->assertStringContainsString('alt="MADANI"', $html);
        $this->assertStringContainsString('class="cap-txt"', $html);
        $this->assertStringContainsString('white-space: nowrap', $html);
        $this->assertStringContainsString('242.65pt', $html);
        $this->assertStringContainsString('152.98pt', $html);
        $this->assertStringContainsString('class="card"', $html);
        $this->assertStringContainsString('ISO/IEC 7810 ID-1', $html);
        $this->assertStringContainsString('top-qr', $html);
        $this->assertStringContainsString('foto-col', $html);
        $this->assertStringContainsString('width: 42pt', $html);
        $this->assertStringContainsString('height: 56pt', $html);
        $this->assertStringContainsString('val-alamat', $html);
        $this->assertStringContainsString('ftr-cell', $html);
        $this->assertStringContainsString('font-weight: bold', $html);
        $this->assertStringContainsString('bf-cell', $html);
        $this->assertStringContainsString('Madrasah Maju, Bermutu, Mendunia.', $html);
        $this->assertStringContainsString('cap-row', $html);
        $this->assertStringContainsString('class="ftr"', $html);
        $this->assertStringContainsString('class="bf"', $html);
        $this->assertStringContainsString('letter-spacing: 0.1pt', $html);
        $this->assertStringContainsString('font-size: 5.9pt', $html);
        $this->assertStringContainsString('font-size: 6.2pt', $html);
        $this->assertStringNotContainsString('ribbon-cut', $html);
        $this->assertStringNotContainsString('ribbon-rail', $html);
        $this->assertStringContainsString('ribbon-gold-row', $html);
        $this->assertStringNotContainsString('ribbon-gold"', $html);
        $this->assertStringNotContainsString('dihasilkan oleh sistem resmi', $html);
        $this->assertStringNotContainsString('alamat-wrap', $html);
        $this->assertStringNotContainsString('ftr-abs', $html);
        $this->assertStringNotContainsString('bf-abs', $html);
        $this->assertStringNotContainsString('ftr-inner', $html);
        $this->assertStringNotContainsString('body-spacer', $html);
        $this->assertStringNotContainsString('bc-panel', $html);
        $this->assertStringNotContainsString('kop-pad', $html);
        $this->assertStringNotContainsString('Preview Kartu E-Pelajar', $html);
        $this->assertStringNotContainsString('Preview admin MADANI', $html);
        $this->assertStringNotContainsString('Depan ·', $html);
        $this->assertStringNotContainsString('Belakang ·', $html);

        $url = URL::signedRoute('kartu-e-pelajar.cek', ['siswa' => $siswa->id]);
        $this->get($url)
            ->assertOk()
            ->assertSee('Kartu E-Pelajar Terverifikasi')
            ->assertDontSee('Preview Kartu E-Pelajar');
    }

    /**
     * @return array{0: string, 1: Siswa}
     */
    private function siswaWithKartuData(bool $denganPernyataan = true, bool $lengkap = true): array
    {
        $siswa = Siswa::query()->create([
            'nama' => 'Rafa Al Maulazki',
            'nisn' => '3124989746',
            'nis' => '12345',
            'nik' => '3210230603120003',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-03-06',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif',
            'cita_cita' => $lengkap ? 'Guru' : null,
            'hobi' => $lengkap ? 'Membaca' : null,
            'anak_ke' => $lengkap ? 1 : null,
            'jumlah_saudara' => $lengkap ? 1 : null,
            'tidak_punya_hp' => $lengkap,
            'tidak_punya_email' => $lengkap,
        ]);
        $siswa->gantiPassword('sandibaru1');

        $tahun = TahunAjaran::aktif();
        $this->assertNotNull($tahun);

        SiswaPeriodik::query()->create([
            'siswa_id' => $siswa->id,
            'tahun_ajaran_id' => $tahun->id,
            'alamat' => 'Blok A, RT. 001 RW. 002 Desa Rawa Kec. Cingambul Kab. Majalengka',
            'blok' => 'A',
            'rt' => '001',
            'rw' => '002',
            'desa' => 'Rawa',
            'kecamatan' => 'Cingambul',
            'kota' => 'Majalengka',
            'provinsi' => 'Jawa Barat',
            'kode_pos' => '45467',
            'pembiaya' => $lengkap ? 'Orang Tua' : null,
            'no_kk' => $lengkap ? '3210010101120001' : null,
            'kepala_keluarga' => $lengkap ? 'Ayah Contoh' : null,
            'tidak_punya_kip' => $lengkap,
            'kebutuhan_khusus' => $lengkap ? ['Tidak Ada'] : null,
            'tidak_punya_kks' => $lengkap,
            'tidak_punya_pkh' => $lengkap,
            'penghasilan_gabungan' => $lengkap ? 'dibawah 800.000' : null,
            'tempat_tinggal' => $lengkap ? 'Bersama orang tua' : null,
        ]);

        if ($lengkap) {
            $hidup = [
                'status_hidup' => 'hidup',
                'tempat_lahir' => 'Majalengka',
                'tanggal_lahir' => '1970-01-01',
                'pendidikan' => 'SMA/Sederajat',
                'pekerjaan' => 'Wiraswasta',
                'penghasilan' => '1.000.000 - 1.999.999',
                'no_hp' => '628123456789',
                'tidak_punya_hp' => false,
            ];

            OrangTua::query()->create(array_merge($hidup, [
                'siswa_id' => $siswa->id,
                'peran' => 'ayah',
                'nama' => 'Ayah Contoh',
                'nik' => '3210010101700001',
            ]));
            OrangTua::query()->create(array_merge($hidup, [
                'siswa_id' => $siswa->id,
                'peran' => 'ibu',
                'nama' => 'Ibu Contoh',
                'nik' => '3210010101720002',
            ]));
            OrangTua::query()->create([
                'siswa_id' => $siswa->id,
                'peran' => 'wali',
                'status' => 'Sama dengan ayah kandung',
            ]);

            RekamDidik::query()->create([
                'siswa_id' => $siswa->id,
                'nama_sd' => 'SD Negeri 1',
                'npsn' => '20200001',
                'tahun_ajaran_kelulusan' => '2024/2025',
                'nip_kepala_sekolah' => '197001011990031001',
                'nama_kepala_sekolah' => 'Kepala SD',
                'nomor_seri_ijazah' => 'DN-123',
                'tanggal_terbit_ijazah' => '2025-06-15',
            ]);

            foreach (['kk', 'akta_lahir', 'ijazah_sd'] as $jenis) {
                Dokumen::query()->create([
                    'siswa_id' => $siswa->id,
                    'jenis' => $jenis,
                    'path' => 'siswa/'.$siswa->id.'/'.$jenis.'.jpg',
                    'nama_asli' => $jenis.'.jpg',
                ]);
                Storage::disk('r2')->put('siswa/'.$siswa->id.'/'.$jenis.'.jpg', 'fake');
            }
        }

        if ($denganPernyataan) {
            $teks = PernyataanSiswa::teksAktif();
            SiswaPernyataan::query()->create([
                'siswa_id' => $siswa->id,
                'versi_teks' => $teks['versi'],
                'teks_poin_1' => $teks['poin_1'],
                'teks_poin_2' => $teks['poin_2'],
                'setuju_poin_1' => true,
                'setuju_poin_2' => true,
                'nama_siswa' => $siswa->nama,
                'nama_wali' => 'Ayah Contoh',
                'ttd_siswa_path' => 'siswa/'.$siswa->id.'/pernyataan/ttd-siswa.png',
                'ttd_wali_path' => 'siswa/'.$siswa->id.'/pernyataan/ttd-wali.png',
                'dikonfirmasi_at' => now(),
            ]);
            Storage::disk('r2')->put('siswa/'.$siswa->id.'/pernyataan/ttd-siswa.png', 'fake');
            Storage::disk('r2')->put('siswa/'.$siswa->id.'/pernyataan/ttd-wali.png', 'fake');
        }

        $token = $this->postJson('/api/v1/siswa/login', [
            'nisn' => $siswa->nisn,
            'password' => 'sandibaru1',
        ])->assertOk()->json('token');

        return [$token, $siswa->fresh()];
    }
}
