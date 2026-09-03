<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuAkademikTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_sees_emis_style_sidebar_menus(): void
    {
        $this->actingAsOperator()
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Kelembagaan')
            ->assertSee('Guru dan Tendik')
            ->assertSee('Rombongan Belajar')
            ->assertSee('Tahun ajaran');
    }

    public function test_operator_can_create_tahun_ajaran_and_rombel(): void
    {
        $this->actingAsOperator();

        $this->post('/gtk', [
            'nama' => 'Ahmad Wali',
            'status' => 'aktif',
            'jenis_kelamin' => 'L',
        ])->assertRedirect('/gtk');

        $gtk = Gtk::query()->first();
        $tahun = TahunAjaran::aktif();

        $this->assertNotNull($tahun);
        $this->assertSame('2026/2027', $tahun->nama);
        $this->assertSame('Aktif', $tahun->labelStatus());

        $this->post('/rombel', [
            'tingkat' => 7,
            'nama' => 'A',
            'gtk_id' => $gtk->id,
            'jenis_rombel' => 'Reguler',
            'waktu_mengajar' => 'Pagi',
            'kurikulum' => 'Kurikulum Merdeka',
            'ruangan' => 'VII A',
        ])->assertRedirect();

        $rombel = Rombel::query()->first();
        $this->assertSame('A', $rombel->nama);
        $this->assertSame($tahun->id, $rombel->tahun_ajaran_id);

        $siswa = Siswa::query()->create([
            'nama' => 'Siswa Rombel',
            'status_keaktifan' => 'aktif_tanpa_rombel',
        ]);

        $this->post('/rombel/'.$rombel->id.'/anggota', [
            'siswa_ids' => [$siswa->id],
        ])->assertRedirect('/rombel/'.$rombel->id);

        $this->assertTrue($siswa->fresh()->rombels()->wherePivot('status', 'aktif')->exists());
        $this->assertSame('aktif', $siswa->fresh()->status_keaktifan);
    }

    private function actingAsOperator(): static
    {
        $this->seed();

        return $this->actingAs(User::query()->where('username', 'admin')->first());
    }
}
