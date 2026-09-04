<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Support\GtkMerger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GtkMergerTest extends TestCase
{
    use RefreshDatabase;

    public function test_merge_creates_when_nip_missing_in_madani(): void
    {
        $result = app(GtkMerger::class)->mergeFromSimpatisansGuru([
            'id' => 9,
            'username' => '198001012005011001',
            'nama_guru' => 'Budi',
            'gelar_depan' => 'Drs.',
            'jenis_kelamin' => 'L',
            'nuptk' => '123',
            'jabatan' => 'Guru',
        ]);

        $this->assertSame('created', $result['action']);
        $this->assertDatabaseHas('gtks', [
            'nip' => '198001012005011001',
            'nama' => 'Budi',
            'jenis' => 'guru',
            'simpatisans_guru_id' => 9,
        ]);
    }

    public function test_merge_fills_empty_fields_without_overwrite(): void
    {
        $gtk = Gtk::query()->create([
            'nama' => 'Nama Lama',
            'nip' => '198001012005011001',
            'jenis' => 'guru',
            'status' => 'aktif',
        ]);

        $result = app(GtkMerger::class)->mergeFromSimpatisansGuru([
            'id' => 9,
            'username' => '198001012005011001',
            'nama_guru' => 'Nama Baru',
            'jabatan' => 'Guru Mapel',
            'nomor_hp' => '081234',
        ]);

        $gtk->refresh();
        $this->assertSame('updated', $result['action']);
        $this->assertSame('Nama Lama', $gtk->nama);
        $this->assertSame('Guru Mapel', $gtk->jabatan);
        $this->assertSame('081234', $gtk->nomor_hp);
    }

    public function test_merge_overwrite_replaces_filled_fields(): void
    {
        Gtk::query()->create([
            'nama' => 'Nama Lama',
            'nip' => '198001012005011001',
            'jenis' => 'guru',
            'status' => 'aktif',
            'jabatan' => 'Lama',
        ]);

        app(GtkMerger::class)->mergeFromSimpatisansGuru([
            'username' => '198001012005011001',
            'nama_guru' => 'Nama Baru',
            'jabatan' => 'Baru',
        ], overwrite: true);

        $this->assertDatabaseHas('gtks', [
            'nip' => '198001012005011001',
            'nama' => 'Nama Baru',
            'jabatan' => 'Baru',
        ]);
    }
}
