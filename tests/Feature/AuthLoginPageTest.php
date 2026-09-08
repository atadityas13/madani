<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthLoginPageTest extends TestCase
{
    public function test_admin_login_page_uses_madani_auth_layout(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk admin', false)
            ->assertSee('MADANI', false)
            ->assertSee('data-auth-form', false)
            ->assertSee('Memverifikasi…', false)
            ->assertSee('data-password-toggle', false)
            ->assertDontSee('Saya siswa', false)
            ->assertDontSee(route('siswa.masuk'), false);
    }

    public function test_siswa_masuk_redirects_to_admin_login(): void
    {
        $this->get(route('siswa.masuk'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    }
}
