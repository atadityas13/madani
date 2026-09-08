<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthLoginPageTest extends TestCase
{
    public function test_admin_login_page_uses_madani_auth_layout(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk Admin', false)
            ->assertSee('Administrasi tertata, Layanan cepat, Data aman', false)
            ->assertSee('Satu Data Terpadu untuk Layanan Terintegrasi', false)
            ->assertSee('data-auth-form', false)
            ->assertSee('Memverifikasi…', false)
            ->assertSee('data-password-toggle', false)
            ->assertDontSee('Hanya Super Admin', false)
            ->assertDontSee('Saya siswa', false)
            ->assertDontSee(route('siswa.masuk'), false);

        $html = $this->get(route('login'))->getContent();

        $this->assertSame(1, substr_count($html, 'madani-login__title'));
        $this->assertStringContainsString('logo-madani.png', $html);
        $this->assertStringNotContainsString('logo-madani-on-dark.png', $html);
        $this->assertStringNotContainsString('madani-login__logo-plate', $html);
        $this->assertStringNotContainsString('madani-login__headline', $html);
        $this->assertStringNotContainsString('madani-login__eyebrow', $html);
    }

    public function test_siswa_masuk_redirects_to_admin_login(): void
    {
        $this->get(route('siswa.masuk'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    }
}
