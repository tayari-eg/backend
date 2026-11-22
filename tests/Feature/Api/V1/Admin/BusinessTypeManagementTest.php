<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Accounts\Models\BusinessType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BusinessTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs($this->createStaffUser(StaffRole::Admin));
    }

    public function test_the_list_shows_active_and_inactive_types_in_sort_order_with_both_names(): void
    {
        $second = BusinessType::factory()->inactive()->create(['sort_order' => 2, 'name' => ['ar' => 'صيدلية', 'en' => 'Pharmacy']]);
        $first = BusinessType::factory()->create(['sort_order' => 1]);
        BusinessProfile::factory()->count(2)->create(['business_type_id' => $first->id]);

        $this->getJson(route('api.v1.admin.business-types.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.0.business_profiles_count', 2)
            ->assertJsonPath('data.1.id', $second->id)
            ->assertJsonPath('data.1.name', ['ar' => 'صيدلية', 'en' => 'Pharmacy'])
            ->assertJsonPath('data.1.is_active', false)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_the_list_filters_by_is_active_and_is_paginated(): void
    {
        BusinessType::factory()->count(3)->create();
        $inactive = BusinessType::factory()->inactive()->create();

        $this->getJson(route('api.v1.admin.business-types.index', ['is_active' => 'false']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inactive->id);

        $this->getJson(route('api.v1.admin.business-types.index', ['is_active' => 'true', 'per_page' => 2]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_search_matches_the_arabic_or_the_english_name_in_any_letter_case(): void
    {
        $pharmacy = BusinessType::factory()->create(['name' => ['ar' => 'صيدلية', 'en' => 'Pharmacy']]);
        $restaurant = BusinessType::factory()->create(['name' => ['ar' => 'مطعم', 'en' => 'Restaurant']]);

        $this->getJson(route('api.v1.admin.business-types.index', ['search' => 'صيدل']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pharmacy->id);

        foreach (['RESTAU', 'restau', 'ReStAu'] as $search) {
            $this->getJson(route('api.v1.admin.business-types.index', ['search' => $search]))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $restaurant->id);
        }
    }

    public function test_percent_and_underscore_in_the_search_are_literal(): void
    {
        $underscore = BusinessType::factory()->create(['name' => ['ar' => 'وجبات', 'en' => 'Fast_Food']]);
        BusinessType::factory()->create(['name' => ['ar' => 'أكل', 'en' => 'FastXFood']]);
        $percent = BusinessType::factory()->create(['name' => ['ar' => 'خصم', 'en' => '100% Organic']]);
        BusinessType::factory()->create(['name' => ['ar' => 'عضوي', 'en' => '1000 Organic']]);

        $this->assertSame([$underscore->id], $this->getJson(route('api.v1.admin.business-types.index', ['search' => 't_f']))->json('data.*.id'));
        $this->assertSame([$percent->id], $this->getJson(route('api.v1.admin.business-types.index', ['search' => '0%']))->json('data.*.id'));
    }

    public function test_show_returns_one_type(): void
    {
        $type = BusinessType::factory()->create();

        $this->getJson(route('api.v1.admin.business-types.show', $type))
            ->assertOk()
            ->assertJsonPath('data.id', $type->id)
            ->assertJsonPath('data.business_profiles_count', 0);
    }

    public function test_a_type_is_created_at_the_end_of_the_list_by_default(): void
    {
        BusinessType::factory()->create(['sort_order' => 7]);

        $this->postJson(route('api.v1.admin.business-types.store'), ['name' => ['ar' => 'مخبز', 'en' => 'Bakery']])
            ->assertCreated()
            ->assertJsonPath('data.name', ['ar' => 'مخبز', 'en' => 'Bakery'])
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.sort_order', 8);

        $this->postJson(route('api.v1.admin.business-types.store'), ['name' => ['ar' => 'ورد', 'en' => 'Flowers'], 'is_active' => false, 'sort_order' => 3])
            ->assertCreated()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.sort_order', 3);
    }

    public function test_both_names_are_required(): void
    {
        $this->postJson(route('api.v1.admin.business-types.store'), ['name' => ['ar' => 'مخبز']], ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name.en']);

        $this->postJson(route('api.v1.admin.business-types.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_a_name_used_by_another_type_is_rejected_in_either_language(): void
    {
        $existing = BusinessType::factory()->create(['name' => ['ar' => 'مطعم', 'en' => 'Restaurant']]);

        $response = $this->postJson(route('api.v1.admin.business-types.store'), ['name' => ['ar' => 'مطعم', 'en' => 'Diner']], ['Accept-Language' => 'en'])
            ->assertUnprocessable();
        $this->assertSame(['Another business type already has this name.'], $response->json('errors')['name.ar']);

        $this->postJson(route('api.v1.admin.business-types.store'), ['name' => ['ar' => 'كافيه', 'en' => 'Restaurant']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name.en']);

        $other = BusinessType::factory()->create();
        $this->patchJson(route('api.v1.admin.business-types.update', $other), ['name' => ['ar' => 'جديد', 'en' => 'Restaurant']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name.en']);

        $this->patchJson(route('api.v1.admin.business-types.update', $existing), ['name' => ['ar' => 'مطعم', 'en' => 'Restaurant']])
            ->assertOk();
    }

    public function test_a_name_is_taken_ignoring_letter_case(): void
    {
        $restaurant = BusinessType::factory()->create(['name' => ['ar' => 'مطعم', 'en' => 'Restaurant']]);
        $other = BusinessType::factory()->create();

        $created = $this->postJson(route('api.v1.admin.business-types.store'), ['name' => ['ar' => 'كافيه', 'en' => 'restaurant']], ['Accept-Language' => 'en'])
            ->assertUnprocessable();
        $this->assertSame(['Another business type already has this name.'], $created->json('errors')['name.en']);

        $updated = $this->patchJson(route('api.v1.admin.business-types.update', $other), ['name' => ['ar' => 'جديد', 'en' => 'RESTAURANT']], ['Accept-Language' => 'en'])
            ->assertUnprocessable();
        $this->assertSame(['Another business type already has this name.'], $updated->json('errors')['name.en']);

        $this->patchJson(route('api.v1.admin.business-types.update', $restaurant), ['name' => ['ar' => 'مطعم', 'en' => 'restaurant']])
            ->assertOk()
            ->assertJsonPath('data.name.en', 'restaurant');
    }

    public function test_update_changes_only_the_fields_sent(): void
    {
        $type = BusinessType::factory()->create(['name' => ['ar' => 'قديم', 'en' => 'Old'], 'sort_order' => 4]);

        $this->patchJson(route('api.v1.admin.business-types.update', $type), ['sort_order' => 9])
            ->assertOk()
            ->assertJsonPath('data.sort_order', 9)
            ->assertJsonPath('data.name', ['ar' => 'قديم', 'en' => 'Old']);

        $this->patchJson(route('api.v1.admin.business-types.update', $type), ['name' => ['ar' => 'جديد', 'en' => 'New']])
            ->assertOk()
            ->assertJsonPath('data.name', ['ar' => 'جديد', 'en' => 'New']);

        $this->patchJson(route('api.v1.admin.business-types.update', $type), ['name' => ['ar' => 'فقط']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name.en']);
    }

    public function test_activate_and_deactivate_change_the_public_list(): void
    {
        $type = BusinessType::factory()->create();

        $this->postJson(route('api.v1.admin.business-types.deactivate', $type))
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
        $this->getJson(route('api.v1.public.business-types.index'))->assertJsonCount(0, 'data');

        $this->postJson(route('api.v1.admin.business-types.activate', $type))
            ->assertOk()
            ->assertJsonPath('data.is_active', true);
        $this->getJson(route('api.v1.public.business-types.index'))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $type->id);
    }

    public function test_an_unused_type_is_deleted(): void
    {
        $type = BusinessType::factory()->create();

        $this->deleteJson(route('api.v1.admin.business-types.destroy', $type))->assertNoContent();

        $this->assertModelMissing($type);
    }

    public function test_a_type_used_by_a_business_profile_is_not_deleted(): void
    {
        $type = BusinessType::factory()->create();
        BusinessProfile::factory()->create(['business_type_id' => $type->id]);

        $this->deleteJson(route('api.v1.admin.business-types.destroy', $type), [], ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.business_type.0', 'This business type is used by business profiles, so it cannot be deleted. Deactivate it instead.');

        $this->deleteJson(route('api.v1.admin.business-types.destroy', $type), [], ['Accept-Language' => 'ar'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.business_type.0', 'نوع النشاط هذا مستخدم في ملفات أنشطة تجارية، لذلك لا يمكن حذفه. قم بإيقافه بدلًا من ذلك.');

        $this->assertModelExists($type);
    }

    public function test_an_unknown_type_is_404(): void
    {
        $this->getJson(route('api.v1.admin.business-types.show', 999))->assertNotFound();
    }
}
