<?php

namespace Tests\Feature\Api\V1\Public;

use App\Domains\Accounts\Models\BusinessType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessTypesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_active_types_in_sort_order_with_names_in_the_request_locale(): void
    {
        BusinessType::factory()->create(['name' => ['ar' => 'صيدلية', 'en' => 'Pharmacy'], 'sort_order' => 2]);
        BusinessType::factory()->create(['name' => ['ar' => 'مطعم', 'en' => 'Restaurant'], 'sort_order' => 1]);
        BusinessType::factory()->inactive()->create(['name' => ['ar' => 'قديم', 'en' => 'Old'], 'sort_order' => 0]);

        $this->getJson(route('api.v1.public.business-types.index'), ['Accept-Language' => 'en'])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Restaurant')
            ->assertJsonPath('data.1.name', 'Pharmacy');

        $this->getJson(route('api.v1.public.business-types.index'), ['Accept-Language' => 'ar'])
            ->assertJsonPath('data.0.name', 'مطعم');
    }

    public function test_it_is_paginated(): void
    {
        BusinessType::factory()->count(3)->create();

        $this->getJson(route('api.v1.public.business-types.index', ['per_page' => 2]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);

        $this->getJson(route('api.v1.public.business-types.index', ['per_page' => 1000]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }
}
