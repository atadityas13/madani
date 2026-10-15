<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\IzinSiswa;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IzinSiswaWebTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function test_admin_sees_rekap_and_surat_menus_and_all_classes(): void
    {
        Storage::fake('r2');
        $this->seed();

        [$admin] = $this->buatAdmin();
        [$wali, $rombel, $siswa] = $this->buatWaliDenganSiswa('VII', 'A', 'Siswa Kelas A');
        [, $rombelB] = $this->buatWaliDenganSiswa('VII', 'B', 'Siswa Kelas B');

        $this->buatIzin($siswa, $rombel, IzinSiswa::JENIS_SAKIT, 'Demam');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Rekap SIA')
            ->assertSee('Surat izin/sakit');

        $this->actingAs($admin)
            ->get(route('rekap-sia.index'))
            ->assertOk()
            ->assertSee($rombel->label())
            ->assertSee($rombelB->label())
            ->assertSee('Siswa Kelas A')
            ->assertSee('Sakit');

        $this->actingAs($admin)
            ->get(route('izin-siswa.index'))
            ->assertOk()
            ->assertSee('Siswa Kelas A')
            ->assertSee('Sakit')
            ->assertSee('Surat');
    }

    public function test_wali_hanya_melihat_rombel_sendiri(): void
    {
        Storage::fake('r2');
        $this->seed();

        [$wali, $rombel, $siswa] = $this->buatWaliDenganSiswa('VIII', '1', 'Ani Wali');
        [, $rombelLain, $siswaLain] = $this->buatWaliDenganSiswa('VIII', '2', 'Budi Lain');

        $izin = $this->buatIzin($siswa, $rombel, IzinSiswa::JENIS_IZIN, 'Acara keluarga');
        $izinLain = $this->buatIzin($siswaLain, $rombelLain, IzinSiswa::JENIS_SAKIT, 'Flu');

        $this->actingAs($wali)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Rekap SIA')
            ->assertSee('Surat izin/sakit');

        $this->actingAs($wali)
            ->get(route('rekap-sia.index'))
            ->assertOk()
            ->assertSee($rombel->label())
            ->assertDontSee($rombelLain->label())
            ->assertSee('Ani Wali')
            ->assertDontSee('Budi Lain');

        $this->actingAs($wali)
            ->get(route('izin-siswa.index'))
            ->assertOk()
            ->assertSee('Ani Wali')
            ->assertDontSee('Budi Lain');

        $this->actingAs($wali)
            ->get(route('izin-siswa.surat', $izin))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($wali)
            ->get(route('izin-siswa.surat', $izinLain))
            ->assertForbidden();
    }

    public function test_talim_wali_punya_rekap_dan_surat(): void
    {
        Storage::fake('r2');
        $this->seed();

        [$wali, $rombel, $siswa] = $this->buatWaliDenganSiswa('IX', '1', 'Cici Talim');
        $this->buatIzin($siswa, $rombel, IzinSiswa::JENIS_SAKIT, 'Sakit perut');

        $this->actingAs($wali)
            ->get(route('talim.wali'))
            ->assertOk()
            ->assertSee('Rekap SIA')
            ->assertSee('Surat izin/sakit');

        $this->actingAs($wali)
            ->get(route('talim.wali.rekap'))
            ->assertOk()
            ->assertSee('Cici Talim')
            ->assertSee('Sakit');

        $this->actingAs($wali)
            ->get(route('talim.wali.izin'))
            ->assertOk()
            ->assertSee('Cici Talim');
    }

    public function test_alpa_tidak_muncul_di_daftar_surat(): void
    {
        Storage::fake('r2');
        $this->seed();

        [$admin] = $this->buatAdmin();
        [$wali, $rombel, $siswa] = $this->buatWaliDenganSiswa('VII', '3', 'Dedi Alpa');

        IzinSiswa::query()->create([
            'siswa_id' => $siswa->id,
            'rombel_id' => $rombel->id,
            'jenis' => IzinSiswa::JENIS_ALPA,
            'tanggal' => now()->toDateString(),
            'alasan' => 'Alpa',
            'status' => IzinSiswa::STATUS_AKTIF,
            'dilaporkan_oleh' => $wali->id,
        ]);

        $this->actingAs($admin)
            ->get(route('izin-siswa.index'))
            ->assertOk()
            ->assertDontSee('Dedi Alpa');

        $this->actingAs($admin)
            ->get(route('rekap-sia.index'))
            ->assertOk()
            ->assertSee('Dedi Alpa')
            ->assertSee('Alpa');
    }

    /**
     * @return array{0: User}
     */
    private function buatAdmin(): array
    {
        $admin = User::query()->where('username', 'admin')->first();
        $this->assertNotNull($admin);

        return [$admin];
    }

    /**
     * @return array{0: User, 1: Rombel, 2: Siswa}
     */
    private function buatWaliDenganSiswa(string $tingkat, string $namaRombel, string $namaSiswa): array
    {
        Role::findOrCreate(Peran::WALI_KELAS);
        Role::findOrCreate(Peran::GURU);

        $gtk = Gtk::query()->create([
            'nama' => 'Wali '.$namaRombel,
            'nip' => fake()->unique()->numerify('19##########'),
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $user = User::factory()->create([
            'name' => 'Wali '.$namaRombel,
            'username' => $gtk->nip,
            'gtk_id' => $gtk->id,
            'is_aktif' => true,
        ]);
        $user->syncRoles([Peran::WALI_KELAS, Peran::GURU]);

        $tahun = TahunAjaran::aktif();
        $this->assertNotNull($tahun);

        $rombel = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => $tingkat,
            'nama' => $namaRombel,
            'gtk_id' => $gtk->id,
        ]);

        $siswa = Siswa::query()->create([
            'nama' => $namaSiswa,
            'nisn' => fake()->unique()->numerify('##########'),
            'jenis_kelamin' => 'L',
            'status_keaktifan' => 'aktif',
        ]);
        $rombel->siswas()->attach($siswa->id, ['status' => 'aktif']);

        return [$user->fresh(), $rombel, $siswa];
    }

    private function buatIzin(Siswa $siswa, Rombel $rombel, string $jenis, string $alasan): IzinSiswa
    {
        $path = 'siswa/'.$siswa->id.'/izin/ttd.png';
        Storage::disk('r2')->put($path, base64_decode(self::PNG_1X1));

        return IzinSiswa::query()->create([
            'siswa_id' => $siswa->id,
            'rombel_id' => $rombel->id,
            'jenis' => $jenis,
            'tanggal' => now()->toDateString(),
            'alasan' => $alasan,
            'pernyataan_disetujui' => true,
            'ttd_wali_path' => $path,
            'nama_wali' => 'Orang tua',
            'status' => IzinSiswa::STATUS_AKTIF,
        ]);
    }
}
