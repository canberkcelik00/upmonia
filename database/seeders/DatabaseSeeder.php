<?php

namespace Database\Seeders;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Services\SlugGenerator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $organization = Organization::create([
            'name' => 'Test Organization',
            'slug' => SlugGenerator::uniqueOrganizationSlug('Test Organization'),
        ]);

        Membership::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'owner',
        ]);
    }
}
