<?php

namespace Tests\Feature;

use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\PortofolioPdfService;
use App\Support\SiswaPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiswaPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_siswa_can_login_with_nisn_and_birthdate_password(): void
    {
        $this->seed();
        $siswa = $this->buatSiswa();

        $this->post('/siswa/masuk', [
            'nisn' => '1234567890',
            'password' => '02092012',
        ])->assertRedirect('/siswa/password');

        $this->assertAuthenticatedAs($siswa, 'siswa');
    }

    public function test_siswa_login_fails_with_wrong_password(): void
    {
        $this->seed();
        $this->buatSiswa();

        $this->from('/siswa/masuk')
            ->post('/siswa/masuk', [
                'nisn' => '1234567890',
                'password' => 'salah',
            ])
            ->assertRedirect('/siswa/masuk')
            ->assertSessionHasErrors('nisn');
    }

    public function test_inactive_siswa_cannot_login(): void
    {
        $this->seed();
        $this->buatSiswa(['status_keaktifan' => 'nonaktif']);

        $this->from('/siswa/masuk')
            ->post('/siswa/masuk', [
                'nisn' => '1234567890',
                'password' => '02092012',
            ])
            ->assertRedirect('/siswa/masuk')
            ->assertSessionHasErrors('nisn');
    }

    public function test_siswa_must_change_password_before_using_portal(): void
    {
        $this->seed();
        $siswa = $this->buatSiswa();

        $this->actingAs($siswa, 'siswa')
            ->get('/siswa/portal')
            ->assertRedirect('/siswa/password');

        $this->actingAs($siswa, 'siswa')
            ->put('/siswa/password', [
                'current_password' => '02092012',
                'password' => 'sandibaru1',
                'password_confirmation' => 'sandibaru1',
            ])
            ->assertRedirect('/siswa/portal');

        $siswa->refresh();
        $this->assertFalse($siswa->must_change_password);
        $this->assertTrue(Hash::check('sandibaru1', $siswa->getAuthPassword()));

        $this->actingAs($siswa, 'siswa')
            ->get('/siswa/portal')
            ->assertOk()
            ->assertSee('Data saya');
    }

    public function test_portal_requires_tabs_in_order_until_required_ones_are_complete(): void
    {
        Storage::fake('r2');
        $this->seed();
        $siswa = $this->buatSiswa();
        $siswa->gantiPassword('sandibaru1');

        $this->actingAs($siswa, 'siswa')
            ->get('/siswa/portal?tab=orang-tua')
            ->assertRedirect(route('siswa.portal', ['tab' => 'data-siswa']));

        $this->actingAs($siswa, 'siswa')
            ->get('/siswa/portal?tab=prestasi')
            ->assertRedirect(route('siswa.portal', ['tab' => 'data-siswa']));

        $this->actingAs($siswa, 'siswa')
            ->get('/siswa/portal')
            ->assertOk()
            ->assertSee('Identitas')
            ->assertSee('biodata-path', false)
            ->assertSee('bi-arrow-right', false)
            ->assertDontSee('tab=orang-tua', false)
            ->assertDontSee('tab=alamat', false)
            ->assertDontSee('tab=prestasi', false);

        $token = $this->tokenSiswa($siswa);
        $this->withToken($token)
            ->getJson('/api/v1/siswa/me')
            ->assertOk()
            ->assertJsonPath('data.kelengkapan.wajib_total', 4)
            ->assertJsonPath('data.kelengkapan.tab.0.terbuka', true)
            ->assertJsonPath('data.kelengkapan.tab.1.terbuka', false)
            ->assertJsonPath('data.kelengkapan.tab.4.wajib', false);

        $this->unggahKk($token);
        $this->withToken($token)
            ->putJson('/api/v1/siswa/data-siswa', $this->payloadDataSiswa())
            ->assertOk();

        $siswa->refresh();

        $this->actingAs($siswa, 'siswa')
            ->get('/siswa/portal?tab=orang-tua')
            ->assertOk()
            ->assertSee('Data orang tua');

        $this->actingAs($siswa, 'siswa')
            ->get('/siswa/portal?tab=prestasi')
            ->assertRedirect(route('siswa.portal', ['tab' => 'orang-tua']));
    }

    public function test_gtk_can_open_any_siswa_tab_without_sequential_lock(): void
    {
        $this->seed();
        $siswa = $this->buatSiswa();
        $admin = User::query()->where('username', 'admin')->first();

        $this->actingAs($admin)
            ->get('/siswa/'.$siswa->id.'?tab=orang-tua')
            ->assertOk()
            ->assertSee('Data orang tua');

        $this->actingAs($admin)
            ->get('/siswa/'.$siswa->id.'?tab=prestasi')
            ->assertOk()
            ->assertSee('Prestasi');

        $this->actingAs($admin)
            ->get('/siswa/'.$siswa->id)
            ->assertOk()
            ->assertSee('tab=orang-tua', false)
            ->assertSee('tab=prestasi', false)
            ->assertSee('bi-arrow-right', false);
    }

    public function test_gtk_can_reset_siswa_password(): void
    {
        $this->seed();
        $siswa = $this->buatSiswa();
        $siswa->gantiPassword('sandibaru1');
        $admin = User::query()->where('username', 'admin')->first();

        $this->actingAs($admin)
            ->post('/siswa/'.$siswa->id.'/reset-password')
            ->assertRedirect();

        $siswa->refresh();
        $this->assertTrue($siswa->must_change_password);
        $this->assertTrue(Hash::check('02092012', $siswa->getAuthPassword()));
    }

    public function test_gtk_can_delete_siswa_dokumen_from_database_and_r2(): void
    {
        Storage::fake('r2');
        $this->seed();
        $siswa = $this->buatSiswa();
        $admin = User::query()->where('username', 'admin')->first();

        $path = "dokumen/{$siswa->id}/kk.jpg";
        Storage::disk('r2')->put($path, 'fake-kk');
        $siswa->dokumens()->create([
            'jenis' => 'kk',
            'path' => $path,
            'nama_asli' => 'kk.jpg',
        ]);

        $this->actingAs($admin)
            ->delete(route('siswa.dokumen.destroy', [$siswa, 'kk']))
            ->assertRedirect(route('siswa.show', ['siswa' => $siswa, 'tab' => 'data-siswa']));

        $this->assertDatabaseMissing('dokumens', [
            'siswa_id' => $siswa->id,
            'jenis' => 'kk',
        ]);
        Storage::disk('r2')->assertMissing($path);
    }

    public function test_api_login_me_and_password_change(): void
    {
        Storage::fake('r2');
        $this->seed();
        $siswa = $this->buatSiswa();

        $this->postJson('/api/v1/siswa/login', [
            'nisn' => '1234567890',
            'password' => 'salah',
        ])->assertUnprocessable();

        $login = $this->postJson('/api/v1/siswa/login', [
            'nisn' => '1234567890',
            'password' => '02092012',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('must_change_password', true);

        $token = $login->json('token');
        $this->assertNotEmpty($token);

        $this->withToken($token)
            ->getJson('/api/v1/siswa/me')
            ->assertOk()
            ->assertJsonPath('data.nisn', '1234567890')
            ->assertJsonPath('data.nama', 'Siswa Contoh');

        $this->withToken($token)
            ->putJson('/api/v1/siswa/data-siswa', [
                'nama' => 'Siswa Contoh',
                'nisn' => '1234567890',
                'nik' => '3210010101120001',
                'tempat_lahir' => 'Majalengka',
                'tanggal_lahir' => '2012-09-02',
                'jenis_kelamin' => 'L',
                'jumlah_saudara' => 1,
                'anak_ke' => 1,
                'agama' => 'Islam',
                'cita_cita' => 'Guru',
                'hobi' => 'Membaca',
                'pembiaya' => 'Orang Tua',
                'tidak_punya_hp' => true,
                'tidak_punya_email' => true,
                'tidak_punya_kip' => true,
                'no_kk' => '3210010101120001',
                'kepala_keluarga' => 'Ayah Contoh',
                'kebutuhan_khusus' => 'Tidak Ada',
            ])
            ->assertForbidden()
            ->assertJsonPath('must_change_password', true);

        $this->withToken($token)
            ->putJson('/api/v1/siswa/password', [
                'current_password' => '02092012',
                'password' => 'sandibaru1',
                'password_confirmation' => 'sandibaru1',
            ])
            ->assertOk()
            ->assertJsonPath('must_change_password', false);

        $this->withToken($token)
            ->putJson('/api/v1/siswa/data-siswa', $this->payloadDataSiswa())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file_kk');

        $this->unggahKk($token);

        $this->withToken($token)
            ->putJson('/api/v1/siswa/data-siswa', $this->payloadDataSiswa([
                'nama' => 'Nama Tidak Boleh Berubah',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('Siswa Contoh', $siswa->fresh()->nama);

        $this->withToken($token)
            ->getJson('/api/v1/siswa/me')
            ->assertOk()
            ->assertJsonPath('data.id', $siswa->id)
            ->assertJsonPath('data.nisn', '1234567890')
            ->assertJsonPath('data.tidak_punya_email', true);
    }

    public function test_api_orang_tua_requires_complete_fields_and_kks_pkh_rules(): void
    {
        $this->seed();
        $siswa = $this->buatSiswa();
        $siswa->gantiPassword('sandibaru1');

        $token = $this->postJson('/api/v1/siswa/login', [
            'nisn' => '1234567890',
            'password' => 'sandibaru1',
        ])->assertOk()->json('token');

        $this->withToken($token)
            ->putJson('/api/v1/siswa/orang-tua', [])
            ->assertUnprocessable();

        $payload = $this->payloadOrangTua();

        $this->withToken($token)
            ->putJson('/api/v1/siswa/orang-tua', array_merge($payload, [
                'tidak_punya_kks' => false,
                'no_kks' => '1234567890',
                'tidak_punya_pkh' => true,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file_kks');

        $this->withToken($token)
            ->putJson('/api/v1/siswa/orang-tua', array_merge($payload, [
                'tidak_punya_kks' => true,
                'tidak_punya_pkh' => true,
            ]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue((bool) $siswa->fresh()->periodikAktif()?->tidak_punya_kks);
        $this->assertTrue((bool) $siswa->fresh()->periodikAktif()?->tidak_punya_pkh);
    }

    public function test_api_orang_tua_forces_wali_lainnya_when_both_parents_deceased(): void
    {
        $this->seed();
        $siswa = $this->buatSiswa();
        $token = $this->tokenSiswa($siswa);
        $payload = $this->payloadOrangTua();
        $payload['ortu']['ayah']['status_hidup'] = 'meninggal';
        $payload['ortu']['ibu']['status_hidup'] = 'meninggal';
        $payload['tidak_punya_kks'] = true;
        $payload['tidak_punya_pkh'] = true;

        $this->withToken($token)
            ->putJson('/api/v1/siswa/orang-tua', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ortu.wali.nama', 'ortu.wali.hubungan']);

        $payload['ortu']['wali'] = [
            'status' => 'Sama dengan ayah kandung',
            'hubungan' => 'Kakek',
            'nama' => 'Wali Contoh',
            'status_hidup' => 'hidup',
            'nik' => '3210010101600003',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '1960-01-01',
            'pendidikan' => 'SMA/Sederajat',
            'pekerjaan' => 'Wiraswasta',
            'penghasilan' => '1.000.000 - 1.999.999',
            'no_hp' => '628123456780',
            'tidak_punya_hp' => false,
        ];

        $this->withToken($token)
            ->putJson('/api/v1/siswa/orang-tua', $payload)
            ->assertOk();

        $this->assertSame('Lainnya', $siswa->fresh()->orangTuas->firstWhere('peran', 'wali')?->status);
    }

    public function test_api_orang_tua_rejects_wali_matching_deceased_parent(): void
    {
        $this->seed();
        $siswa = $this->buatSiswa();
        $token = $this->tokenSiswa($siswa);
        $payload = $this->payloadOrangTua();
        $payload['tidak_punya_kks'] = true;
        $payload['tidak_punya_pkh'] = true;

        $payload['ortu']['ayah']['status_hidup'] = 'meninggal';
        $payload['ortu']['wali']['status'] = 'Sama dengan ayah kandung';

        $this->withToken($token)
            ->putJson('/api/v1/siswa/orang-tua', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ortu.wali.status');

        $payload['ortu']['ayah']['status_hidup'] = 'hidup';
        $payload['ortu']['ibu']['status_hidup'] = 'meninggal';
        $payload['ortu']['wali']['status'] = 'Sama dengan ibu kandung';

        $this->withToken($token)
            ->putJson('/api/v1/siswa/orang-tua', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ortu.wali.status');

        $payload['ortu']['ayah']['status_hidup'] = 'meninggal';
        $payload['ortu']['ibu']['status_hidup'] = 'hidup';
        $payload['ortu']['wali']['status'] = 'Sama dengan ibu kandung';

        $this->withToken($token)
            ->putJson('/api/v1/siswa/orang-tua', $payload)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('Sama dengan ibu kandung', $siswa->fresh()->orangTuas->firstWhere('peran', 'wali')?->status);
    }

    public function test_api_data_siswa_requires_kk_and_gates_pengajuan(): void
    {
        Storage::fake('r2');
        $this->seed();
        $siswa = $this->buatSiswa();
        $token = $this->tokenSiswa($siswa);

        $this->withToken($token)
            ->postJson('/api/v1/siswa/pengajuan-perubahan', [
                'field' => 'nama',
                'nilai_baru' => 'Siswa Baru',
                'alasan' => 'Nama di ijazah berbeda dengan data madrasah.',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('kelengkapan');

        $this->unggahKk($token);

        $this->withToken($token)
            ->putJson('/api/v1/siswa/data-siswa', $this->payloadDataSiswa([
                'tidak_punya_kip' => false,
                'no_kip' => 'KIP123',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file_kip');

        $this->withToken($token)
            ->putJson('/api/v1/siswa/data-siswa', $this->payloadDataSiswa())
            ->assertOk();

        $this->withToken($token)
            ->postJson('/api/v1/siswa/pengajuan-perubahan', [
                'field' => 'nama',
                'nilai_baru' => 'Siswa Baru',
                'alasan' => 'Nama di ijazah berbeda dengan data madrasah.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('pending', $siswa->fresh()->pengajuanPerubahans()->first()?->status);
        $this->assertSame('Siswa Contoh', $siswa->fresh()->nama);
    }

    public function test_api_alamat_copies_parent_and_asrama(): void
    {
        $this->seed();
        $siswa = $this->buatSiswa();
        $token = $this->tokenSiswa($siswa);

        $this->withToken($token)
            ->putJson('/api/v1/siswa/orang-tua', array_merge($this->payloadOrangTua(), [
                'tidak_punya_kks' => true,
                'tidak_punya_pkh' => true,
            ]))
            ->assertOk();

        $this->withToken($token)
            ->putJson('/api/v1/siswa/alamat', [
                'ortu' => [
                    'ayah' => [
                        'status_tempat_tinggal' => 'Milik sendiri',
                        'provinsi' => 'Jawa Barat',
                        'kota' => 'Majalengka',
                        'kecamatan' => 'Cingambul',
                        'desa' => 'Maniis',
                        'blok' => 'Sindanghurip',
                        'rt' => '001',
                        'rw' => '002',
                    ],
                    'ibu' => ['sama_dengan_ayah' => true],
                    'wali' => [],
                ],
                'tempat_tinggal' => 'Tinggal dengan Orang Tua/Wali',
                'jarak' => '< 1 km',
                'waktu_tempuh' => '< 15 menit',
                'transportasi' => 'Jalan kaki',
            ])
            ->assertOk();

        $siswa->refresh();
        $this->assertSame('Maniis', $siswa->periodikAktif()?->desa);
        $this->assertSame('Maniis', $siswa->orangTuas->firstWhere('peran', 'ibu')?->desa);
        $this->assertTrue((bool) $siswa->orangTuas->firstWhere('peran', 'ibu')?->sama_dengan_ayah);

        $this->withToken($token)
            ->putJson('/api/v1/siswa/alamat', [
                'tempat_tinggal' => 'Asrama Madrasah',
            ])
            ->assertOk();

        $periodik = $siswa->fresh()->periodikAktif();
        $this->assertSame('Maniis', $periodik?->desa);
        $this->assertSame('Sindanghurip', $periodik?->blok);
        $this->assertSame('-7.043314, 108.353711', $periodik?->koordinat);
    }

    public function test_api_rekam_didik_and_prestasi(): void
    {
        Storage::fake('r2');
        $this->seed();
        $siswa = $this->buatSiswa();
        $token = $this->tokenSiswa($siswa);

        $this->withToken($token)
            ->putJson('/api/v1/siswa/rekam-didik', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nama_sd', 'file_ijazah']);

        $payload = [
            'nama_sd' => 'SD Negeri 1 Maniis',
            'npsn' => '20241234',
            'tahun_ajaran_kelulusan' => '2023/2024',
            'nip_kepala_sekolah' => '197001011990031001',
            'nama_kepala_sekolah' => 'Kepala Sekolah',
            'nomor_seri_ijazah' => 'DN-123456',
            'tanggal_terbit_ijazah' => '2024-06-15',
            'ijazah_sesuai' => ['nama', 'nisn'],
        ];

        $this->withToken($token)
            ->putJson('/api/v1/siswa/rekam-didik', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file_ijazah');

        $this->withToken($token)
            ->put('/api/v1/siswa/rekam-didik', array_merge($payload, [
                'file_ijazah' => UploadedFile::fake()->image('ijazah.jpg'),
            ]), ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertSame('SD Negeri 1 Maniis', $siswa->fresh()->rekamDidik?->nama_sd);
        $this->assertFalse((bool) $siswa->fresh()->rekamDidik?->ijazah_sesuai);

        $this->withToken($token)
            ->postJson('/api/v1/siswa/prestasi', [
                'nama' => 'Juara 1 MTQ',
                'jenis' => 'Keagamaan',
                'tahun' => 2026,
                'tingkat' => 'Kabupaten/Kota',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, $siswa->fresh()->prestasis()->count());
    }

    public function test_hp_leading_zero_becomes_country_code_and_formats_are_enforced(): void
    {
        Storage::fake('r2');
        $this->seed();
        $siswa = $this->buatSiswa();
        $token = $this->tokenSiswa($siswa);
        $this->unggahKk($token);

        $this->withToken($token)
            ->putJson('/api/v1/siswa/data-siswa', $this->payloadDataSiswa([
                'tidak_punya_hp' => false,
                'no_hp' => '081234567890',
            ]))
            ->assertOk();

        $this->assertSame('6281234567890', $siswa->fresh()->no_hp);

        $this->withToken($token)
            ->putJson('/api/v1/siswa/rekam-didik', [
                'nama_sd' => 'SD Negeri 1 Maniis',
                'npsn' => '20241234',
                'tahun_ajaran_kelulusan' => '2023/2024',
                'nip_kepala_sekolah' => '19700101',
                'nama_kepala_sekolah' => 'Kepala Sekolah',
                'nomor_seri_ijazah' => 'DN-123456',
                'tanggal_terbit_ijazah' => '2024-06-15',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nip_kepala_sekolah');

        $this->withToken($token)
            ->postJson('/api/v1/siswa/beasiswa', [
                'tahun' => 2026,
                'kategori' => 'PIP',
                'nomor_rekening' => 'abc',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nomor_rekening');
    }

    public function test_wilayah_returns_kode_pos(): void
    {
        $this->seed();
        $siswa = $this->buatSiswa();
        $token = $this->tokenSiswa($siswa);

        $this->withToken($token)
            ->getJson('/api/v1/wilayah?provinsi=Jawa Barat')
            ->assertOk()
            ->assertJsonFragment(['Majalengka']);

        $this->withToken($token)
            ->getJson('/api/v1/wilayah?provinsi=Jawa Barat&kota=Majalengka&kecamatan=Cingambul&desa=Maniis')
            ->assertOk()
            ->assertJsonPath('kode_pos', '45467');
    }

    public function test_referensi_includes_wilayah_tree(): void
    {
        $this->seed();
        $siswa = $this->buatSiswa();
        $token = $this->tokenSiswa($siswa);

        $data = $this->withToken($token)
            ->getJson('/api/v1/referensi')
            ->assertOk()
            ->json('data');

        $this->assertSame('45467', data_get($data, 'wilayah.Jawa Barat.Majalengka.Cingambul.Maniis'));
        $this->assertArrayNotHasKey('tidak_diketahui', $data['emis']['status_hidup'] ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadOrangTua(): array
    {
        $hidup = [
            'nama' => 'Orang Tua Contoh',
            'status_hidup' => 'hidup',
            'nik' => '3210010101700001',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '1970-01-01',
            'pendidikan' => 'SMA/Sederajat',
            'pekerjaan' => 'Wiraswasta',
            'penghasilan' => '1.000.000 - 1.999.999',
            'no_hp' => '628123456789',
            'tidak_punya_hp' => false,
        ];

        return [
            'ortu' => [
                'ayah' => array_merge($hidup, ['nama' => 'Ayah Contoh']),
                'ibu' => array_merge($hidup, ['nama' => 'Ibu Contoh', 'nik' => '3210010101720002']),
                'wali' => ['status' => 'Sama dengan ayah kandung'],
            ],
            'penghasilan_gabungan' => 'dibawah 800.000',
        ];
    }

    public function test_password_awal_command_fills_missing_passwords(): void
    {
        $this->seed();
        $siswa = Siswa::query()->create([
            'nama' => 'Tanpa Password',
            'nisn' => '1111222233',
            'tanggal_lahir' => '2011-01-15',
            'status_keaktifan' => 'aktif_tanpa_rombel',
        ]);
        $siswa->forceFill(['password' => null])->save();

        $this->artisan('siswa:set-password-awal')->assertSuccessful();

        $siswa->refresh();
        $this->assertTrue(Hash::check(SiswaPassword::dariTanggalLahir('2011-01-15'), $siswa->getAuthPassword()));
    }

    public function test_gtk_can_preview_and_download_siswa_portofolio_pdf(): void
    {
        Storage::fake('r2');
        $this->seed();
        $siswa = $this->buatSiswa(['nis' => '2026001']);
        $admin = User::query()->where('username', 'admin')->first();

        $this->actingAs($admin)
            ->get(route('siswa.portofolio', $siswa))
            ->assertOk()
            ->assertSee('Preview portofolio')
            ->assertSee('Unduh');

        $stream = $this->actingAs($admin)
            ->get(route('siswa.portofolio.stream', $siswa));
        $stream->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $stream->headers->get('content-type'));

        $download = $this->actingAs($admin)
            ->get(route('siswa.portofolio.download', $siswa));
        $download->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $download->headers->get('content-type'));
        $this->assertStringContainsString('attachment', (string) $download->headers->get('content-disposition'));
        $this->assertGreaterThan(1000, strlen($download->getContent()));
    }

    public function test_siswa_api_can_download_own_portofolio_pdf(): void
    {
        Storage::fake('r2');
        $this->seed();
        $siswa = $this->buatSiswa(['nis' => '2026002']);
        $token = $this->tokenSiswa($siswa);

        $response = $this->withToken($token)
            ->get('/api/v1/siswa/portofolio.pdf');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertGreaterThan(1000, strlen($response->getContent()));
    }

    public function test_portofolio_signed_verification_page_works(): void
    {
        $this->seed();
        $siswa = $this->buatSiswa(['nis' => '2026003']);

        $url = \Illuminate\Support\Facades\URL::signedRoute(
            'portofolio.cek',
            ['siswa' => $siswa->id],
        );

        $this->get($url)
            ->assertOk()
            ->assertSee($siswa->nama)
            ->assertSee('2026003')
            ->assertSee('Data siswa Terverifikasi')
            ->assertSee('MADANI');

        $this->get(route('portofolio.cek', $siswa))
            ->assertForbidden();
    }

    public function test_portofolio_aktivitas_keterangan_follows_rombel_progression(): void
    {
        $this->seed();
        $siswa = $this->buatSiswa(['nis' => '2026004']);

        $tahunLama = TahunAjaran::query()->create([
            'nama' => '2024/2025',
            'tanggal_mulai' => '2024-07-01',
            'tanggal_selesai' => '2025-06-30',
            'is_aktif' => false,
            'status' => TahunAjaran::STATUS_ARSIP,
        ]);
        $tahunBaru = TahunAjaran::aktif();
        $this->assertNotNull($tahunBaru);

        $rombel7 = Rombel::query()->create([
            'tahun_ajaran_id' => $tahunLama->id,
            'tingkat' => 7,
            'nama' => 'A',
        ]);
        $rombel8 = Rombel::query()->create([
            'tahun_ajaran_id' => $tahunBaru->id,
            'tingkat' => 8,
            'nama' => 'B',
        ]);

        $siswa->rombels()->attach([
            $rombel7->id => ['status' => 'nonaktif'],
            $rombel8->id => ['status' => 'aktif'],
        ]);

        $aktivitas = app(PortofolioPdfService::class)->viewData($siswa->fresh())['aktivitas'];

        $this->assertCount(2, $aktivitas);
        $this->assertSame('Naik kelas — kelas 7', $aktivitas[0]['keterangan']);
        $this->assertSame('Kelas 8', $aktivitas[0]['tingkat']);
        $this->assertSame('Baru', $aktivitas[1]['keterangan']);
        $this->assertSame('Kelas 7', $aktivitas[1]['tingkat']);
    }

    private function tokenSiswa(Siswa $siswa): string
    {
        $siswa->gantiPassword('sandibaru1');

        return $this->postJson('/api/v1/siswa/login', [
            'nisn' => $siswa->nisn,
            'password' => 'sandibaru1',
        ])->assertOk()->json('token');
    }

    private function unggahKk(string $token): void
    {
        $this->withToken($token)
            ->post('/api/v1/siswa/dokumen/kk', [
                'file_kk' => UploadedFile::fake()->image('kk.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->withToken($token)
            ->post('/api/v1/siswa/dokumen/akta_lahir', [
                'file_akta' => UploadedFile::fake()->image('akta.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertOk();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payloadDataSiswa(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'Siswa Contoh',
            'nisn' => '1234567890',
            'nik' => '3210010101120001',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-09-02',
            'jenis_kelamin' => 'L',
            'jumlah_saudara' => 1,
            'anak_ke' => 1,
            'agama' => 'Islam',
            'cita_cita' => 'Guru',
            'hobi' => 'Membaca',
            'pembiaya' => 'Orang Tua',
            'tidak_punya_hp' => true,
            'tidak_punya_email' => true,
            'tidak_punya_kip' => true,
            'no_kk' => '3210010101120001',
            'kepala_keluarga' => 'Ayah Contoh',
            'kebutuhan_khusus' => 'Tidak Ada',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function buatSiswa(array $overrides = []): Siswa
    {
        return Siswa::query()->create(array_merge([
            'nama' => 'Siswa Contoh',
            'nisn' => '1234567890',
            'nik' => '3210010101120001',
            'tempat_lahir' => 'Majalengka',
            'tanggal_lahir' => '2012-09-02',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'status_keaktifan' => 'aktif',
        ], $overrides));
    }
}
