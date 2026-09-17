<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * '/' has no page of its own by design — it redirects straight to wherever the visitor
     * actually starts (see routes/web.php).
     */
    public function test_the_root_redirects_to_login_when_signed_out(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
