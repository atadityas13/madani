<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\User;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
