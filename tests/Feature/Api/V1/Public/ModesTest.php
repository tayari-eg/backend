<?php

namespace Tests\Feature\Api\V1\Public;

use App\Domains\Catalog\Models\Mode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_active_modes_in_sort_order_with_what_go_online_needs(): void
    {
        config(['tayari.tayars.zone_min_radius_km' => 1, 'tayari.tayars.zone_max_radius_km' => 15]);
        Mode::factory()->longDistance()->create(['name' => ['ar' => 'مسافات طويلة', 'en' => 'Long distance'], 'sort_order' => 1]);
        Mode::factory()->zone()->create(['name' => ['ar' => 'حولك', 'en' => 'Zone'], 'description' => ['ar' => 'قريب', 'en' => 'Short trips'], 'sort_order' => 2]);
        Mode::factory()->area()->inactive()->create(['sort_order' => 0]);

        $this->getJson(route('api.v1.public.modes.index'), ['Accept-Language' => 'en'])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.code', 'long_distance')
            ->assertJsonPath('data.0.allows_food', false)
            ->assertJsonPath('data.0.requires_destination', true)
            ->assertJsonPath('data.0.requires_radius', false)
            ->assertJsonPath('data.0.radius_km', null)
            ->assertJsonPath('data.1.name', 'Zone')
            ->assertJsonPath('data.1.description', 'Short trips')
            ->assertJsonPath('data.1.requires_radius', true)
            ->assertJsonPath('data.1.requires_area', false)
            ->assertJsonPath('data.1.radius_km', ['min' => 1, 'max' => 15]);

        $this->getJson(route('api.v1.public.modes.index'), ['Accept-Language' => 'ar'])
            ->assertJsonPath('data.1.name', 'حولك');
    }

    public function test_it_is_paginated_and_needs_no_login(): void
    {
        Mode::factory()->zone()->create();
        Mode::factory()->area()->create();

        $this->getJson(route('api.v1.public.modes.index', ['per_page' => 1]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 2);

        $this->getJson(route('api.v1.public.modes.index', ['per_page' => 1000]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }
}
