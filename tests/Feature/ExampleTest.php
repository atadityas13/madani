<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('MADANI');
    }

    public function test_login_page_is_available(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_operator_can_login_with_username(): void
    {
        $this->seed();

        $this->post('/login', [
            'login' => 'admin',
            'password' => 'madani-admin',
        ])->assertRedirect('/dashboard');
    }
}
