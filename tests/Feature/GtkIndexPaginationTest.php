<?php

namespace Tests\Feature;

use App\Models\Gtk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GtkIndexPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_gtk_memakai_paginasi_dan_menyimpan_filter(): void
    {
        $this->seed();

        for ($i = 1; $i <= 16; $i++) {
            Gtk::query()->create([
                'nama' => sprintf('Guru Halaman %02d', $i),
                'jenis' => Gtk::JENIS_GURU,
                'status' => 'aktif',
            ]);
        }

        $admin = User::query()->where('username', 'admin')->first();

        $this->actingAs($admin)
            ->get(route('gtk.index', ['jenis' => Gtk::JENIS_GURU]))
            ->assertOk()
            ->assertSee('Guru Halaman 01', false)
            ->assertDontSee('Guru Halaman 16', false)
            ->assertSee('Menampilkan', false)
            ->assertSee('Selanjutnya', false)
            ->assertSee('name="jenis"', false);

        $this->actingAs($admin)
            ->get(route('gtk.index', ['jenis' => Gtk::JENIS_GURU, 'page' => 2]))
            ->assertOk()
            ->assertSee('Guru Halaman 16', false)
            ->assertDontSee('Guru Halaman 01', false)
            ->assertSee('Sebelumnya', false);

        $this->actingAs($admin)
            ->get(route('gtk.index', ['per_page' => 'all']))
            ->assertOk()
            ->assertSee('Guru Halaman 01', false)
            ->assertSee('Guru Halaman 16', false);
    }
}
