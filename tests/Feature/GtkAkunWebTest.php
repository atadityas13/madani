<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GtkAkunWebTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        Role::findOrCreate(Peran::SUPERADMIN);

        $user = User::factory()->create(['is_aktif' => true]);
        $user->syncRoles([Peran::SUPERADMIN]);

        return $user;
    }

    public function test_superadmin_bisa_buat_dan_reset_akun_dari_gtk(): void
    {
        $admin = $this->superadmin();
        $gtk = Gtk::query()->create([
            'nama' => 'Siti Aminah',
            'nip' => '198505052010012001',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->post(route('gtk.akun.store', $gtk))
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'username' => '198505052010012001',
            'gtk_id' => $gtk->id,
            'must_change_password' => true,
        ]);

        $akun = User::query()->where('gtk_id', $gtk->id)->first();
        $this->assertTrue($akun->hasRole(Peran::GURU));

        $this->actingAs($admin)
            ->post(route('gtk.akun.reset', $gtk))
            ->assertRedirect();

        $this->assertTrue($akun->fresh()->must_change_password);
    }

    public function test_buat_akun_gagal_tanpa_nip(): void
    {
        $admin = $this->superadmin();
        $gtk = Gtk::query()->create([
            'nama' => 'Tanpa NIP',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->from(route('gtk.index'))
            ->post(route('gtk.akun.store', $gtk))
            ->assertRedirect(route('gtk.index'))
            ->assertSessionHasErrors('nip');
    }

    public function test_update_gtk_menyimpan_kolom_kompetensi(): void
    {
        $admin = $this->superadmin();
        $gtk = Gtk::query()->create([
            'nama' => 'Budi',
            'nip' => '198001012005011001',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->put(route('gtk.update', $gtk), [
                'nama' => 'Budi Santoso',
                'jenis' => 'guru',
                'status' => 'aktif',
                'mapel_ijazah' => 'Matematika',
                'mapel_sertifikasi' => 'Matematika',
                'status_sertifikasi' => '1',
                'is_bk' => '1',
            ])
            ->assertRedirect(route('gtk.index'));

        $gtk->refresh();
        $this->assertSame('Matematika', $gtk->mapel_ijazah);
        $this->assertSame('Matematika', $gtk->mapel_sertifikasi);
        $this->assertTrue($gtk->status_sertifikasi);
        $this->assertTrue($gtk->is_bk);
    }

    public function test_form_menyimpan_rumpun_ijazah_dan_kode_guru(): void
    {
        config([
            'services.simpatisans.base_url' => 'https://simpatisans.test/api',
            'services.simpatisans.secret' => 'rahasia',
        ]);
        Http::fake([
            'https://simpatisans.test/api/madani/referensi' => Http::response([
                'mapels' => ['Matematika'],
                'rumpuns' => ['IPA'],
            ]),
        ]);

        $admin = $this->superadmin();
        $gtk = Gtk::query()->create([
            'nama' => 'Budi',
            'nip' => '198001012005011001',
            'jenis' => 'guru',
            'status' => 'aktif',
            'mapel_ijazah' => 'Matematika',
            'mapel_sertifikasi' => 'Matematika',
            'status_sertifikasi' => true,
        ]);
        Gtk::query()->create([
            'nama' => 'Siti',
            'nip' => '198505052010012001',
            'jenis' => 'guru',
            'status' => 'aktif',
            'kode_internal' => 'AB',
        ]);

        $this->actingAs($admin)
            ->get(route('gtk.edit', $gtk))
            ->assertOk()
            ->assertSee('value="rumpun:IPA"', false)
            ->assertSee('value="mapel:Matematika"', false)
            ->assertSee('Tersertifikasi (TPG)');

        $this->actingAs($admin)
            ->put(route('gtk.update', $gtk), [
                'nama' => 'Budi Santoso',
                'jenis' => 'guru',
                'status' => 'aktif',
                'kode_internal' => 'bd',
                'ijazah_selection' => 'rumpun:IPA',
                'status_sertifikasi' => '0',
                'mapel_sertifikasi' => 'Matematika',
                'is_bk' => '0',
            ])
            ->assertRedirect(route('gtk.index'));

        $gtk->refresh();
        $this->assertSame('BD', $gtk->kode_internal);
        $this->assertSame('IPA', $gtk->rumpun_ijazah);
        $this->assertNull($gtk->mapel_ijazah);
        $this->assertNull($gtk->mapel_sertifikasi);
        $this->assertFalse($gtk->status_sertifikasi);

        $this->actingAs($admin)
            ->from(route('gtk.edit', $gtk))
            ->put(route('gtk.update', $gtk), [
                'nama' => 'Budi Santoso',
                'jenis' => 'guru',
                'status' => 'aktif',
                'kode_internal' => 'AB',
                'ijazah_selection' => 'mapel:Matematika',
                'status_sertifikasi' => '1',
                'mapel_sertifikasi' => 'Matematika',
            ])
            ->assertRedirect(route('gtk.edit', $gtk))
            ->assertSessionHasErrors('kode_internal');
    }
}
