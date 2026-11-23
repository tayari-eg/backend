<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Catalog\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs($this->createStaffUser(StaffRole::Admin));
    }

    public function test_the_list_shows_active_and_inactive_categories_in_sort_order_with_parent_and_counts(): void
    {
        $second = Category::factory()->inactive()->create(['sort_order' => 2, 'name' => ['ar' => 'طرود', 'en' => 'Parcels']]);
        $first = Category::factory()->create(['sort_order' => 1]);
        $child = Category::factory()->childOf($first)->create(['sort_order' => 3]);

        $this->getJson(route('api.v1.admin.categories.index'))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.0.children_count', 1)
            ->assertJsonPath('data.0.parent', null)
            ->assertJsonPath('data.1.id', $second->id)
            ->assertJsonPath('data.1.name', ['ar' => 'طرود', 'en' => 'Parcels'])
            ->assertJsonPath('data.1.is_active', false)
            ->assertJsonPath('data.2.id', $child->id)
            ->assertJsonPath('data.2.parent_id', $first->id)
            ->assertJsonPath('data.2.parent.id', $first->id)
            ->assertJsonPath('data.2.icon_url', null);
    }

    public function test_the_list_filters_by_level_parent_food_active_and_search(): void
    {
        $food = Category::factory()->food()->create(['name' => ['ar' => 'طعام', 'en' => 'Food']]);
        $meals = Category::factory()->childOf($food)->food()->create(['name' => ['ar' => 'وجبات', 'en' => 'Meals']]);
        $parcels = Category::factory()->inactive()->create(['name' => ['ar' => 'طرود', 'en' => 'Parcels']]);

        $this->assertEqualsCanonicalizing([$food->id, $parcels->id], $this->getJson(route('api.v1.admin.categories.index', ['main_only' => 'true']))->json('data.*.id'));
        $this->assertSame([$meals->id], $this->getJson(route('api.v1.admin.categories.index', ['parent_id' => $food->id]))->json('data.*.id'));
        $this->assertEqualsCanonicalizing([$food->id, $meals->id], $this->getJson(route('api.v1.admin.categories.index', ['is_food' => 'true']))->json('data.*.id'));
        $this->assertSame([$parcels->id], $this->getJson(route('api.v1.admin.categories.index', ['is_active' => 'false']))->json('data.*.id'));
        $this->assertSame([$meals->id], $this->getJson(route('api.v1.admin.categories.index', ['search' => 'MEAL']))->json('data.*.id'));
        $this->assertSame([$parcels->id], $this->getJson(route('api.v1.admin.categories.index', ['search' => 'طرو']))->json('data.*.id'));

        $this->getJson(route('api.v1.admin.categories.index', ['per_page' => 1]))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_show_returns_one_category(): void
    {
        $category = Category::factory()->create();

        $this->getJson(route('api.v1.admin.categories.show', $category))
            ->assertOk()
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.children_count', 0);
    }

    public function test_a_main_category_is_created_at_the_end_of_the_main_list(): void
    {
        Category::factory()->create(['sort_order' => 7]);

        $this->postJson(route('api.v1.admin.categories.store'), ['name' => ['ar' => 'طعام', 'en' => 'Food'], 'is_food' => true])
            ->assertCreated()
            ->assertJsonPath('data.name', ['ar' => 'طعام', 'en' => 'Food'])
            ->assertJsonPath('data.parent_id', null)
            ->assertJsonPath('data.is_food', true)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.sort_order', 8);

        $this->postJson(route('api.v1.admin.categories.store'), ['name' => ['ar' => 'طرود', 'en' => 'Parcels']])
            ->assertCreated()
            ->assertJsonPath('data.is_food', false);
    }

    public function test_a_subcategory_takes_its_parents_food_flag_unless_sent_and_sorts_among_its_siblings(): void
    {
        $food = Category::factory()->food()->create(['sort_order' => 50]);
        Category::factory()->childOf($food)->create(['sort_order' => 2]);

        $this->postJson(route('api.v1.admin.categories.store'), ['name' => ['ar' => 'وجبات', 'en' => 'Meals'], 'parent_id' => $food->id])
            ->assertCreated()
            ->assertJsonPath('data.parent_id', $food->id)
            ->assertJsonPath('data.parent.id', $food->id)
            ->assertJsonPath('data.is_food', true)
            ->assertJsonPath('data.sort_order', 3);

        $this->postJson(route('api.v1.admin.categories.store'), ['name' => ['ar' => 'أدوات', 'en' => 'Cutlery'], 'parent_id' => $food->id, 'is_food' => false])
            ->assertCreated()
            ->assertJsonPath('data.is_food', false);
    }

    public function test_a_subcategory_cannot_be_a_parent(): void
    {
        $main = Category::factory()->create();
        $child = Category::factory()->childOf($main)->create();

        $response = $this->postJson(route('api.v1.admin.categories.store'), ['name' => ['ar' => 'ثالث', 'en' => 'Third'], 'parent_id' => $child->id], ['Accept-Language' => 'en'])
            ->assertUnprocessable();
        $this->assertSame(['The parent must be a main category: a subcategory cannot have its own subcategories.'], $response->json('errors.parent_id'));

        $this->postJson(route('api.v1.admin.categories.store'), ['name' => ['ar' => 'ثالث', 'en' => 'Third'], 'parent_id' => 999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['parent_id']);
    }

    public function test_names_are_unique_among_siblings_only_ignoring_letter_case(): void
    {
        $food = Category::factory()->create();
        $parcels = Category::factory()->create();
        Category::factory()->childOf($food)->create(['name' => ['ar' => 'أخرى', 'en' => 'Other']]);

        $response = $this->postJson(route('api.v1.admin.categories.store'), ['name' => ['ar' => 'جديد', 'en' => 'OTHER'], 'parent_id' => $food->id], ['Accept-Language' => 'en'])
            ->assertUnprocessable();
        $this->assertSame(['Another category under the same parent already has this name.'], $response->json('errors')['name.en']);

        $this->postJson(route('api.v1.admin.categories.store'), ['name' => ['ar' => 'أخرى', 'en' => 'Other'], 'parent_id' => $parcels->id])
            ->assertCreated();
        $this->postJson(route('api.v1.admin.categories.store'), ['name' => ['ar' => 'أخرى', 'en' => 'Other']])
            ->assertCreated();
    }

    public function test_both_names_are_required(): void
    {
        $this->postJson(route('api.v1.admin.categories.store'), ['name' => ['ar' => 'طعام']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name.en']);

        $this->postJson(route('api.v1.admin.categories.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_changes_only_the_fields_sent(): void
    {
        $category = Category::factory()->create(['name' => ['ar' => 'قديم', 'en' => 'Old'], 'sort_order' => 4]);

        $this->patchJson(route('api.v1.admin.categories.update', $category), ['sort_order' => 9, 'is_food' => true])
            ->assertOk()
            ->assertJsonPath('data.sort_order', 9)
            ->assertJsonPath('data.is_food', true)
            ->assertJsonPath('data.name', ['ar' => 'قديم', 'en' => 'Old']);

        $this->patchJson(route('api.v1.admin.categories.update', $category), ['name' => ['ar' => 'جديد', 'en' => 'New']])
            ->assertOk()
            ->assertJsonPath('data.name', ['ar' => 'جديد', 'en' => 'New']);
    }

    public function test_a_category_moves_under_a_main_category_and_back(): void
    {
        $food = Category::factory()->create();
        $meals = Category::factory()->create();

        $this->patchJson(route('api.v1.admin.categories.update', $meals), ['parent_id' => $food->id])
            ->assertOk()
            ->assertJsonPath('data.parent_id', $food->id);

        $this->patchJson(route('api.v1.admin.categories.update', $meals), ['parent_id' => null])
            ->assertOk()
            ->assertJsonPath('data.parent_id', null);
    }

    public function test_a_move_never_makes_a_third_level_or_a_loop(): void
    {
        $food = Category::factory()->create();
        $meals = Category::factory()->childOf($food)->create();
        $parcels = Category::factory()->create();

        $this->patchJson(route('api.v1.admin.categories.update', $parcels), ['parent_id' => $meals->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['parent_id']);

        $this->patchJson(route('api.v1.admin.categories.update', $food), ['parent_id' => $food->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['parent_id']);

        $this->patchJson(route('api.v1.admin.categories.update', $food), ['parent_id' => $parcels->id], ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.parent_id.0', 'This category has subcategories, so it cannot be moved under another category.');

        $this->assertNull($food->refresh()->parent_id);
    }

    public function test_the_second_of_two_opposite_moves_is_refused_not_a_server_error(): void
    {
        $first = Category::factory()->create();
        $second = Category::factory()->create();

        $this->patchJson(route('api.v1.admin.categories.update', $first), ['parent_id' => $second->id])
            ->assertOk()
            ->assertJsonPath('data.parent_id', $second->id);

        $this->patchJson(route('api.v1.admin.categories.update', $second), ['parent_id' => $first->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['parent_id']);

        $this->assertSame($second->id, $first->refresh()->parent_id);
        $this->assertNull($second->refresh()->parent_id);
    }

    public function test_a_move_checks_the_name_against_the_new_siblings(): void
    {
        $food = Category::factory()->create();
        Category::factory()->childOf($food)->create(['name' => ['ar' => 'أخرى', 'en' => 'Other']]);
        $other = Category::factory()->create(['name' => ['ar' => 'أخرى', 'en' => 'Other']]);

        $this->patchJson(route('api.v1.admin.categories.update', $other), ['parent_id' => $food->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name.ar']);

        $this->assertNull($other->refresh()->parent_id);
    }

    public function test_activate_and_deactivate_change_the_public_list(): void
    {
        $main = Category::factory()->create();
        Category::factory()->childOf($main)->create();

        $this->postJson(route('api.v1.admin.categories.deactivate', $main))
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
        $this->getJson(route('api.v1.public.categories.index'))->assertJsonCount(0, 'data');

        $this->postJson(route('api.v1.admin.categories.activate', $main))
            ->assertOk()
            ->assertJsonPath('data.is_active', true);
        $this->getJson(route('api.v1.public.categories.index'))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $main->id);
    }

    public function test_a_category_without_subcategories_is_deleted_with_its_icon(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();
        $this->postJson(route('api.v1.admin.categories.icon.store', $category), ['icon' => UploadedFile::fake()->image('icon.png')])->assertOk();

        $this->deleteJson(route('api.v1.admin.categories.destroy', $category))->assertNoContent();

        $this->assertModelMissing($category);
        $this->assertDatabaseCount('media', 0);
    }

    public function test_a_category_with_subcategories_is_not_deleted(): void
    {
        $category = Category::factory()->create();
        Category::factory()->childOf($category)->create();

        $this->deleteJson(route('api.v1.admin.categories.destroy', $category), [], ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.category.0', 'This category has subcategories, so it cannot be deleted. Delete or move them first, or deactivate it instead.');

        $this->deleteJson(route('api.v1.admin.categories.destroy', $category), [], ['Accept-Language' => 'ar'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.category.0', 'هذه الفئة بها فئات فرعية، لذلك لا يمكن حذفها. احذفها أو انقلها أولًا، أو قم بإيقاف الفئة بدلًا من ذلك.');

        $this->assertModelExists($category);
    }

    public function test_an_icon_is_uploaded_to_the_public_disk_replaced_and_removed(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();

        $first = $this->postJson(route('api.v1.admin.categories.icon.store', $category), ['icon' => UploadedFile::fake()->image('first.png')])
            ->assertOk()
            ->json('data.icon_url');
        $this->assertStringContainsString('first.png', $first);

        $second = $this->postJson(route('api.v1.admin.categories.icon.store', $category), ['icon' => UploadedFile::fake()->image('second.webp')])
            ->assertOk()
            ->json('data.icon_url');
        $this->assertStringContainsString('second.webp', $second);

        $media = $category->refresh()->getMedia(Category::ICON_COLLECTION);
        $this->assertCount(1, $media);
        $this->assertSame('public', $media->first()->disk);
        Storage::disk('public')->assertExists($media->first()->getPathRelativeToRoot());

        $this->deleteJson(route('api.v1.admin.categories.icon.destroy', $category))
            ->assertOk()
            ->assertJsonPath('data.icon_url', null);
        $this->assertDatabaseCount('media', 0);

        $this->deleteJson(route('api.v1.admin.categories.icon.destroy', $category))->assertOk();
    }

    public function test_the_icon_must_be_a_raster_image(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();

        $this->postJson(route('api.v1.admin.categories.icon.store', $category), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['icon']);

        foreach ([UploadedFile::fake()->create('icon.svg', 10, 'image/svg+xml'), UploadedFile::fake()->create('icon.pdf', 10, 'application/pdf')] as $file) {
            $this->postJson(route('api.v1.admin.categories.icon.store', $category), ['icon' => $file])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['icon']);
        }

        $this->assertDatabaseCount('media', 0);
    }

    public function test_an_unknown_category_is_404(): void
    {
        $this->getJson(route('api.v1.admin.categories.show', 999))->assertNotFound();
    }
}
