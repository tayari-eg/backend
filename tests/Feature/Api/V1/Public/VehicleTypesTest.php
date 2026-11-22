<?php

namespace Tests\Feature\Api\V1\Public;

use App\Domains\Tayars\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTypesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_active_types_in_sort_order_with_names_in_the_request_locale(): void
    {
        VehicleType::factory()->withoutLicense()->create(['name' => ['ar' => 'عجلة', 'en' => 'Bicycle'], 'sort_order' => 2]);
        VehicleType::factory()->create(['name' => ['ar' => 'سيارة', 'en' => 'Car'], 'sort_order' => 1]);
        VehicleType::factory()->inactive()->create(['name' => ['ar' => 'قديم', 'en' => 'Old'], 'sort_order' => 0]);

        $this->getJson(route('api.v1.public.vehicle-types.index'), ['Accept-Language' => 'en'])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Car')
            ->assertJsonPath('data.0.requires_license', true)
            ->assertJsonPath('data.1.name', 'Bicycle')
            ->assertJsonPath('data.1.requires_license', false)
            ->assertJsonPath('data.1.can_carry_food', true);

        $this->getJson(route('api.v1.public.vehicle-types.index'), ['Accept-Language' => 'ar'])
            ->assertJsonPath('data.0.name', 'سيارة');
    }

    public function test_it_is_paginated_and_needs_no_login(): void
    {
        VehicleType::factory()->count(3)->create();

        $this->getJson(route('api.v1.public.vehicle-types.index', ['per_page' => 2]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3);

        $this->getJson(route('api.v1.public.vehicle-types.index', ['per_page' => 1000]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }
}
