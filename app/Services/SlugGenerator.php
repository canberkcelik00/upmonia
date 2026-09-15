<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Str;

class SlugGenerator
{
    /**
     * Slugifies $name and, on collision, appends -2, -3, ... until unique.
     * Mirrors the source app's signup() slug-collision handling.
     */
    public static function uniqueOrganizationSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'org';
        $slug = $base;
        $suffix = 2;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
