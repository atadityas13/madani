<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyPolicyPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_policy_page_is_public(): void
    {
        $this->get(route('privacy-policy'))
            ->assertOk()
            ->assertSee('Kebijakan Privasi', false)
            ->assertSee('MADANI', false)
            ->assertSee('siswa', false);
    }

    public function test_kebijakan_privasi_redirects_to_privacy_policy(): void
    {
        $this->get('/kebijakan-privasi')
            ->assertRedirect('/privacy-policy');
    }
}
