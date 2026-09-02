<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_shows_login(): void
    {
        $this->get('/')->assertOk()->assertSee('Masuk operator');
    }

    public function test_legacy_login_path_redirects_home(): void
    {
        $this->get('/login')->assertRedirect('/');
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/');
    }

    public function test_operator_can_login_with_username(): void
    {
        $this->seed();

        $this->post('/', [
            'login' => 'admin',
            'password' => 'madani-admin',
        ])->assertRedirect('/dashboard');
    }
}
