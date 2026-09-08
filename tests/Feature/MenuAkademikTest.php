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
            ->assertSee('Manajemen')
            ->assertSee('Guru dan Tendik')
            ->assertSee('Rombongan Belajar')
            ->assertSee('Tahun ajaran')
            ->assertSee('Database');
    }

    public function test_operator_can_manage_rombel_anggota(): void
    {
        $this->actingAsOperator();

        $this->post('/gtk', [
            'nama' => 'Ahmad Wali',
            'status' => 'aktif',
            'jenis' => 'guru',
            'jenis_kelamin' => 'L',
        ])->assertRedirect('/gtk');

        $gtk = Gtk::query()->first();
        $tahun = TahunAjaran::aktif();

        $this->assertNotNull($tahun);
        $this->assertSame('2026/2027', $tahun->nama);
        $this->assertSame('Aktif', $tahun->labelStatus());

        $rombel = Rombel::query()->create([
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 'VII',
            'nama' => 'A',
            'gtk_id' => $gtk->id,
        ]);

        $siswa = Siswa::query()->create([
            'nama' => 'Siswa Rombel',
            'status_keaktifan' => 'aktif_tanpa_rombel',
        ]);

        $this->post('/rombel/'.$rombel->id.'/anggota', [
            'siswa_ids' => [$siswa->id],
        ])->assertRedirect('/rombel/'.$rombel->id);

        $this->assertTrue($siswa->fresh()->rombels()->wherePivot('status', 'aktif')->exists());
        $this->assertSame('aktif', $siswa->fresh()->status_keaktifan);

        $this->post(route('rombel.anggota.kosongkan', $rombel))
            ->assertRedirect(route('rombel.show', $rombel));

        $this->assertFalse($siswa->fresh()->rombels()->wherePivot('status', 'aktif')->exists());
        $this->assertSame('aktif_tanpa_rombel', $siswa->fresh()->status_keaktifan);
    }

    public function test_rombel_index_urut_tingkat_lalu_nama_numerik(): void
    {
        $this->actingAsOperator();
        $tahun = TahunAjaran::aktif();

        Rombel::query()->create(['tahun_ajaran_id' => $tahun->id, 'tingkat' => 'IX', 'nama' => '2']);
        Rombel::query()->create(['tahun_ajaran_id' => $tahun->id, 'tingkat' => 'VII', 'nama' => '6']);
        Rombel::query()->create(['tahun_ajaran_id' => $tahun->id, 'tingkat' => 'VII', 'nama' => '1']);
        Rombel::query()->create(['tahun_ajaran_id' => $tahun->id, 'tingkat' => 'VIII', 'nama' => '3']);

        $response = $this->get(route('rombel.index'))->assertOk();
        $content = $response->getContent();

        $posVii1 = strpos($content, '>VII</td>');
        $posViii = strpos($content, '>VIII</td>');
        $posIx = strpos($content, '>IX</td>');

        $this->assertNotFalse($posVii1);
        $this->assertNotFalse($posViii);
        $this->assertNotFalse($posIx);
        $this->assertTrue($posVii1 < $posViii && $posViii < $posIx);

        $firstNama = strpos($content, '>1</td>');
        $sixthNama = strpos($content, '>6</td>');
        $this->assertNotFalse($firstNama);
        $this->assertNotFalse($sixthNama);
        $this->assertTrue($firstNama < $sixthNama);
    }

    public function test_nama_gtk_dan_pengguna_tanpa_backslash(): void
    {
        $this->actingAsOperator();

        $gtk = Gtk::query()->create([
            'nama' => "Endang Ma\\'sum",
            'status' => 'aktif',
            'jenis' => 'guru',
            'jenis_kelamin' => 'L',
        ]);

        $user = User::factory()->create([
            'name' => "Endang Ma\\'sum",
            'is_aktif' => true,
            'gtk_id' => $gtk->id,
        ]);

        $this->assertSame("Endang Ma'sum", $gtk->fresh()->nama);
        $this->assertSame("Endang Ma'sum", $user->fresh()->name);

        $this->get(route('gtk.index'))
            ->assertOk()
            ->assertSee("Endang Ma'sum")
            ->assertDontSee("Ma\\'sum", false);
    }

    private function actingAsOperator(): static
    {
        $this->seed();

        return $this->actingAs(User::query()->where('username', 'admin')->first());
    }
}
