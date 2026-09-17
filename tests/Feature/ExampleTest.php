<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesOrgUser;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase, CreatesOrgUser;

    /**
     * '/' now serves the marketing home page to guests (see routes/web.php) instead of
     * redirecting straight to login.
     */
    public function test_the_root_shows_the_marketing_home_page_when_signed_out(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(route('signup'), false);
    }

    public function test_the_root_redirects_to_monitors_when_signed_in(): void
    {
        $this->setUpOrgUser();

        $response = $this->get('/');

        $response->assertRedirect(route('monitors.index'));
    }
}
