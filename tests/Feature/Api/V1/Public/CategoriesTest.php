<?php

namespace Tests\Feature\Api\V1\Public;

use App\Domains\Catalog\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_active_main_categories_with_their_active_subcategories_in_the_request_locale(): void
    {
        $food = Category::factory()->food()->create(['name' => ['ar' => 'طعام', 'en' => 'Food'], 'sort_order' => 2]);
        $parcels = Category::factory()->create(['name' => ['ar' => 'طرود', 'en' => 'Parcels'], 'sort_order' => 1]);
        Category::factory()->childOf($food)->food()->create(['name' => ['ar' => 'وجبات', 'en' => 'Meals'], 'sort_order' => 2]);
        Category::factory()->childOf($food)->food()->create(['name' => ['ar' => 'حلويات', 'en' => 'Desserts'], 'sort_order' => 1]);
        Category::factory()->childOf($food)->inactive()->create(['name' => ['ar' => 'قديم', 'en' => 'Old']]);
        Category::factory()->childOf($parcels)->create(['name' => ['ar' => 'مستندات', 'en' => 'Documents']]);

        $this->getJson(route('api.v1.public.categories.index'), ['Accept-Language' => 'en'])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Parcels')
            ->assertJsonPath('data.0.is_food', false)
            ->assertJsonPath('data.0.children.0.name', 'Documents')
            ->assertJsonPath('data.1.name', 'Food')
            ->assertJsonPath('data.1.is_food', true)
            ->assertJsonCount(2, 'data.1.children')
            ->assertJsonPath('data.1.children.0.name', 'Desserts')
            ->assertJsonPath('data.1.children.1.name', 'Meals')
            ->assertJsonPath('data.1.children.1.icon_url', null);

        $this->getJson(route('api.v1.public.categories.index'), ['Accept-Language' => 'ar'])
            ->assertJsonPath('data.0.name', 'طرود');
    }

    public function test_inactive_main_categories_and_main_categories_without_an_active_subcategory_are_hidden(): void
    {
        $inactive = Category::factory()->inactive()->create();
        Category::factory()->childOf($inactive)->create();
        Category::factory()->create();
        $onlyInactiveChildren = Category::factory()->create();
        Category::factory()->childOf($onlyInactiveChildren)->inactive()->create();

        $this->getJson(route('api.v1.public.categories.index'))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_the_icon_url_is_public(): void
    {
        Storage::fake('public');
        $main = Category::factory()->create();
        Category::factory()->childOf($main)->create();
        $main->addMedia(UploadedFile::fake()->image('icon.png'))->toMediaCollection(Category::ICON_COLLECTION);

        $url = $this->getJson(route('api.v1.public.categories.index'))
            ->assertOk()
            ->json('data.0.icon_url');

        $this->assertStringContainsString('icon.png', $url);
    }

    public function test_it_is_paginated_and_needs_no_login(): void
    {
        foreach (Category::factory()->count(3)->create() as $main) {
            Category::factory()->childOf($main)->create();
        }

        $this->getJson(route('api.v1.public.categories.index', ['per_page' => 2]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3);

        $this->getJson(route('api.v1.public.categories.index', ['per_page' => 1000]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }
}
