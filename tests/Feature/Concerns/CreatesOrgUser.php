<?php

namespace Tests\Feature\Concerns;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;

trait CreatesOrgUser
{
    protected Organization $org;

    protected User $user;

    protected function setUpOrgUser(): void
    {
        $this->org = Organization::create(['name' => 'Test Org', 'slug' => 'test-org-'.uniqid()]);
        $this->user = User::factory()->create(['locale' => 'tr']);
        Membership::create(['user_id' => $this->user->id, 'organization_id' => $this->org->id, 'role' => 'owner']);
        $this->actingAs($this->user);
    }
}
