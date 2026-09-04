<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\Madrasah;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KelembagaanTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_update_identitas_and_logo_but_admin_cannot(): void
    {
        Storage::fake('r2');
        $this->seed();
        $superadmin = User::query()->where('username', 'admin')->first();

        $this->actingAs($superadmin)
            ->get('/kelembagaan/identitas')
            ->assertOk()
            ->assertSee('Logo madrasah')
            ->assertSee('Simpan');

        $this->actingAs($superadmin)
            ->put('/kelembagaan/identitas', [
                'nama' => 'MTsN 11 Majalengka',
                'npsn' => '12345678',
                'logo' => UploadedFile::fake()->image('logo.png', 80, 80),
            ])
            ->assertRedirect('/kelembagaan/identitas');

        $madrasah = Madrasah::saatIni();
        $this->assertSame('12345678', $madrasah->npsn);
        $this->assertNotNull($madrasah->logo_path);
        Storage::disk('r2')->assertExists($madrasah->logo_path);

        $this->actingAs($superadmin)
            ->post('/pengguna', [
                'name' => 'Admin Biasa',
                'username' => 'adminbiasa',
                'email' => 'adminbiasa@mtsn11majalengka.sch.id',
                'password' => 'password123',
                'role' => 'admin',
                'is_aktif' => '1',
            ])
            ->assertRedirect('/pengguna');

        $admin = User::query()->where('username', 'adminbiasa')->first();

        $this->actingAs($admin)
            ->get('/kelembagaan/identitas')
            ->assertOk()
            ->assertDontSee('Simpan');

        $this->actingAs($admin)
            ->put('/kelembagaan/identitas', [
                'nama' => 'Tidak Boleh',
            ])
            ->assertForbidden();
    }

    public function test_tahun_ajaran_status_and_delete_rules(): void
    {
        $this->seed();
        $superadmin = User::query()->where('username', 'admin')->first();
        $aktif = TahunAjaran::aktif();

        $this->actingAs($superadmin)
            ->get('/tahun-ajaran')
            ->assertOk()
            ->assertSee('Aktif')
            ->assertDontSee('Semester')
            ->assertDontSee('Periode');

        $this->actingAs($superadmin)
            ->post('/tahun-ajaran', [
                'nama' => '2027/2028',
                'tanggal_mulai' => '2027-07-13',
                'tanggal_selesai' => '2028-06-12',
            ])
            ->assertRedirect('/tahun-ajaran');

        $baru = TahunAjaran::query()->where('nama', '2027/2028')->first();
        $this->assertSame('Belum Aktif', $baru->labelStatus());

        $this->actingAs($superadmin)
            ->get('/tahun-ajaran')
            ->assertSee('Belum Aktif')
            ->assertSee('Aktifkan');

        $gtk = Gtk::query()->create(['nama' => 'Wali', 'status' => 'aktif']);
        Rombel::query()->create([
            'tahun_ajaran_id' => $aktif->id,
            'tingkat' => 'VII',
            'nama' => 'A',
            'gtk_id' => $gtk->id,
        ]);

        $this->assertFalse($aktif->fresh()->bisaDihapus());
        $this->actingAs($superadmin)
            ->delete('/tahun-ajaran/'.$aktif->id)
            ->assertRedirect();
        $this->assertNotNull($aktif->fresh());

        $this->actingAs($superadmin)
            ->post('/tahun-ajaran/'.$baru->id.'/aktifkan')
            ->assertRedirect('/tahun-ajaran');

        $this->assertSame('Aktif', $baru->fresh()->labelStatus());
        $this->assertSame('Arsip', $aktif->fresh()->labelStatus());

        $this->actingAs($superadmin)
            ->delete('/tahun-ajaran/'.$baru->id)
            ->assertRedirect();
        $this->assertFalse($baru->fresh()->bisaDihapus());
    }
}
