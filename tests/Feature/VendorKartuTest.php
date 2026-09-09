<?php

namespace Tests\Feature;

use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\VendorJob;
use App\Services\KartuEPelajarBulkPdfService;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

class VendorKartuTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_login_and_open_dashboard(): void
    {
        $this->seed();
        $vendor = $this->buatVendor();

        $this->post(route('login'), [
            'login' => 'vendor1',
            'password' => 'password123',
        ])->assertRedirect(route('vendor.dashboard'));

        $this->actingAs($vendor)
            ->get(route('vendor.dashboard'))
            ->assertOk()
            ->assertSee('Jumlah siswa')
            ->assertSee('Panduan alur');
    }

    public function test_vendor_cannot_view_other_vendor_job(): void
    {
        $this->seed();
        Storage::fake('r2');

        $vendorA = $this->buatVendor('vendor_a', 'Vendor A');
        $vendorB = $this->buatVendor('vendor_b', 'Vendor B');
        $siswa = $this->buatSiswa(['nisn' => '1234567890']);
        $job = $this->buatJob($vendorA, [$siswa->id]);

        $this->actingAs($vendorB)
            ->get(route('vendor.jobs.show', $job))
            ->assertForbidden();
    }

    public function test_admin_can_create_job_from_rombel(): void
    {
        $this->seed();
        $admin = User::query()->where('username', 'admin')->first();
        $vendor = $this->buatVendor();
        $tahun = TahunAjaran::aktif();
        $rombel = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => 'A',
        ]);
        $siswa = $this->buatSiswa(['nisn' => '1111222233']);
        $rombel->siswas()->attach($siswa->id, ['status' => 'aktif']);

        $this->actingAs($admin)
            ->post(route('manajemen.vendor-jobs.store'), [
                'nama' => 'Foto kelas VII A',
                'user_id' => $vendor->id,
                'status' => VendorJob::STATUS_AKTIF,
                'rombel_ids' => [$rombel->id],
            ])
            ->assertRedirect(route('manajemen.vendor-jobs.index'));

        $job = VendorJob::query()->where('nama', 'Foto kelas VII A')->first();
        $this->assertNotNull($job);
        $this->assertTrue($job->siswas()->where('siswas.id', $siswa->id)->exists());
    }

    public function test_vendor_can_filter_by_foto_status_and_rombel(): void
    {
        $this->seed();
        Storage::fake('r2');

        $vendor = $this->buatVendor();
        $tahun = TahunAjaran::aktif();
        $rombel = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VIII',
            'nama' => 'B',
        ]);
        $sudah = $this->buatSiswa(['nama' => 'Sudah Foto', 'nisn' => '2000000001', 'foto' => 'foto/x/profil.jpg']);
        $belum = $this->buatSiswa(['nama' => 'Belum Foto', 'nisn' => '2000000002']);
        $rombel->siswas()->attach([$sudah->id, $belum->id], ['status' => 'aktif']);
        $job = $this->buatJob($vendor, [$sudah->id, $belum->id]);

        $this->actingAs($vendor)
            ->get(route('vendor.jobs.show', ['vendorJob' => $job, 'foto' => 'belum']))
            ->assertOk()
            ->assertSee('Belum Foto')
            ->assertDontSee('Sudah Foto');

        $this->actingAs($vendor)
            ->get(route('vendor.jobs.show', ['vendorJob' => $job, 'rombel_id' => $rombel->id]))
            ->assertOk()
            ->assertSee('Sudah Foto')
            ->assertSee('Belum Foto');
    }

    public function test_vendor_upload_foto_validates_ratio_and_size(): void
    {
        $this->seed();
        Storage::fake('r2');

        $vendor = $this->buatVendor();
        $siswa = $this->buatSiswa(['nisn' => '3000000001']);
        $job = $this->buatJob($vendor, [$siswa->id]);

        $this->actingAs($vendor)
            ->post(route('vendor.jobs.foto.upload', [$job, $siswa]), [
                'foto' => UploadedFile::fake()->image('lebar.jpg', 400, 400),
            ])
            ->assertSessionHasErrors('foto');

        $this->actingAs($vendor)
            ->post(route('vendor.jobs.foto.upload', [$job, $siswa]), [
                'foto' => UploadedFile::fake()->image('ok.jpg', 300, 400),
            ])
            ->assertRedirect(route('vendor.jobs.show', $job));

        $siswa->refresh();
        $this->assertNotNull($siswa->foto);
        Storage::disk('r2')->assertExists($siswa->foto);
    }

    public function test_zip_hotfolder_maps_nisn_and_reports_missing(): void
    {
        $this->seed();
        Storage::fake('r2');

        $vendor = $this->buatVendor();
        $siswa = $this->buatSiswa(['nisn' => '4444555566']);
        $job = $this->buatJob($vendor, [$siswa->id]);

        $zipPath = sys_get_temp_dir().'/vendor-hotfolder-'.uniqid().'.zip';
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE));
        $img = imagecreatetruecolor(300, 400);
        $tmpImg = sys_get_temp_dir().'/4444555566.jpg';
        imagejpeg($img, $tmpImg, 90);
        imagedestroy($img);
        $zip->addFile($tmpImg, '4444555566.jpg');
        $asing = imagecreatetruecolor(300, 400);
        $tmpAsing = sys_get_temp_dir().'/9999999999.jpg';
        imagejpeg($asing, $tmpAsing, 90);
        imagedestroy($asing);
        $zip->addFile($tmpAsing, '9999999999.jpg');
        $zip->close();
        @unlink($tmpImg);
        @unlink($tmpAsing);

        $this->actingAs($vendor)
            ->post(route('vendor.jobs.zip', $job), [
                'zip' => new UploadedFile($zipPath, 'foto.zip', 'application/zip', null, true),
            ])
            ->assertRedirect(route('vendor.jobs.show', $job))
            ->assertSessionHas('status');

        $siswa->refresh();
        $this->assertNotNull($siswa->foto);
        @unlink($zipPath);
    }

    public function test_bulk_pdf_streams_front_and_back_rows(): void
    {
        $this->seed();
        Storage::fake('r2');

        $vendor = $this->buatVendor();
        $siswas = collect(range(1, 6))->map(function (int $i) {
            $path = "foto/s{$i}/profil.jpg";
            Storage::disk('r2')->put($path, 'fake');

            return $this->buatSiswa([
                'nama' => 'Siswa Bulk '.$i,
                'nisn' => '500000000'.$i,
                'foto' => $path,
            ]);
        });
        $job = $this->buatJob($vendor, $siswas->pluck('id')->all());

        $response = $this->actingAs($vendor)
            ->post(route('vendor.jobs.kartu.bulk', $job), [
                'semua_berfoto' => '1',
            ]);

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertSame(5, KartuEPelajarBulkPdfService::PER_HALAMAN);
    }

    private function buatVendor(string $username = 'vendor1', string $name = 'Vendor Kartu'): User
    {
        Role::findOrCreate(Peran::VENDOR);

        $user = User::factory()->create([
            'name' => $name,
            'username' => $username,
            'password' => 'password123',
            'is_aktif' => true,
        ]);
        $user->syncRoles([Peran::VENDOR]);

        return $user;
    }

    /**
     * @param  list<string>  $siswaIds
     */
    private function buatJob(User $vendor, array $siswaIds): VendorJob
    {
        $job = VendorJob::query()->create([
            'nama' => 'Job Test',
            'user_id' => $vendor->id,
            'created_by' => $vendor->id,
            'status' => VendorJob::STATUS_AKTIF,
        ]);
        $job->siswas()->sync($siswaIds);

        return $job;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function buatSiswa(array $overrides = []): Siswa
    {
        return Siswa::query()->create(array_merge([
            'nama' => 'Siswa Vendor',
            'status_keaktifan' => 'aktif',
            'jenis_kelamin' => 'L',
        ], $overrides));
    }
}
