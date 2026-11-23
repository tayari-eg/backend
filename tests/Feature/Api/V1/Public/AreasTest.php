<?php

namespace Tests\Feature\Api\V1\Public;

use App\Domains\Catalog\Models\Area;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreasTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_active_areas_in_sort_order_with_names_in_the_request_locale(): void
    {
        Area::factory()->create([
            'name' => ['ar' => 'مدينة نصر', 'en' => 'Nasr City'],
            'city' => ['ar' => 'القاهرة', 'en' => 'Cairo'],
            'center_lat' => '30.0566000',
            'center_lng' => '31.3301000',
            'radius_km' => '6.50',
            'sort_order' => 1,
        ]);
        Area::factory()->create(['name' => ['ar' => 'المعادي', 'en' => 'Maadi'], 'sort_order' => 2]);
        Area::factory()->inactive()->create(['sort_order' => 0]);

        $this->getJson(route('api.v1.public.areas.index'), ['Accept-Language' => 'en'])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Nasr City')
            ->assertJsonPath('data.0.city', 'Cairo')
            ->assertJsonPath('data.0.center', ['lat' => 30.0566, 'lng' => 31.3301])
            ->assertJsonPath('data.0.radius_km', 6.5)
            ->assertJsonPath('data.1.name', 'Maadi');

        $this->getJson(route('api.v1.public.areas.index'), ['Accept-Language' => 'ar'])
            ->assertJsonPath('data.0.name', 'مدينة نصر')
            ->assertJsonPath('data.0.city', 'القاهرة');
    }

    public function test_it_is_paginated_and_needs_no_login(): void
    {
        Area::factory()->count(3)->create();

        $this->getJson(route('api.v1.public.areas.index', ['per_page' => 2]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3);

        $this->getJson(route('api.v1.public.areas.index', ['per_page' => 1000]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }
}
