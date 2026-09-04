<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\User;
use App\Support\GtkAkunImporter;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GtkAkunImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_membuat_akun_dengan_hash_password_simpatisans(): void
    {
        Role::findOrCreate(Peran::GURU);

        Gtk::query()->create([
            'nama' => 'Budi',
            'nip' => '198001012005011001',
            'jenis' => 'guru',
            'status' => 'aktif',
            'email' => 'budi@example.com',
        ]);

        $hash = Hash::make('rahasia-lama');

        $result = app(GtkAkunImporter::class)->importFromSimpatisansUser([
            'username' => '198001012005011001',
            'nama_lengkap' => 'Budi Santoso',
            'role' => 'guru',
            'password' => $hash,
            'plain_password' => null,
            'is_active' => true,
        ]);

        $this->assertSame('created', $result['action']);
        $user = User::query()->where('username', '198001012005011001')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('rahasia-lama', $user->password));
        $this->assertFalse($user->must_change_password);
        $this->assertTrue($user->hasRole(Peran::GURU));
    }

    public function test_import_skip_jika_gtk_belum_ada(): void
    {
        $result = app(GtkAkunImporter::class)->importFromSimpatisansUser([
            'username' => '198001012005011099',
            'role' => 'guru',
            'password' => Hash::make('x'),
        ]);

        $this->assertSame('skipped', $result['action']);
        $this->assertSame('gtk_missing', $result['reason']);
    }

    public function test_import_set_must_change_jika_plain_password_ada(): void
    {
        Role::findOrCreate(Peran::GURU);
        Gtk::query()->create([
            'nama' => 'Siti',
            'nip' => '198505052010012001',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        app(GtkAkunImporter::class)->importFromSimpatisansUser([
            'username' => '198505052010012001',
            'role' => 'guru',
            'password' => Hash::make('198505052010012001'),
            'plain_password' => '198505052010012001',
            'is_active' => true,
        ]);

        $this->assertTrue(
            User::query()->where('username', '198505052010012001')->first()->must_change_password
        );
    }
}
