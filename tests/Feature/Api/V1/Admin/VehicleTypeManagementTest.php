<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Tayars\Models\Vehicle;
use App\Domains\Tayars\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VehicleTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs($this->createStaffUser(StaffRole::Admin));
    }

    public function test_the_list_shows_active_and_inactive_types_in_sort_order_with_both_names(): void
    {
        $second = VehicleType::factory()->inactive()->create(['sort_order' => 2, 'name' => ['ar' => 'صيدلية', 'en' => 'Pharmacy']]);
        $first = VehicleType::factory()->create(['sort_order' => 1]);
        Vehicle::factory()->count(2)->create(['vehicle_type_id' => $first->id]);

        $this->getJson(route('api.v1.admin.vehicle-types.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.0.vehicles_count', 2)
            ->assertJsonPath('data.1.id', $second->id)
            ->assertJsonPath('data.1.name', ['ar' => 'صيدلية', 'en' => 'Pharmacy'])
            ->assertJsonPath('data.1.is_active', false)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_the_list_filters_by_is_active_and_is_paginated(): void
    {
        VehicleType::factory()->count(3)->create();
        $inactive = VehicleType::factory()->inactive()->create();

        $this->getJson(route('api.v1.admin.vehicle-types.index', ['is_active' => 'false']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inactive->id);

        $this->getJson(route('api.v1.admin.vehicle-types.index', ['is_active' => 'true', 'per_page' => 2]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_search_matches_the_arabic_or_the_english_name_in_any_letter_case(): void
    {
        $pharmacy = VehicleType::factory()->create(['name' => ['ar' => 'صيدلية', 'en' => 'Pharmacy']]);
        $restaurant = VehicleType::factory()->create(['name' => ['ar' => 'مطعم', 'en' => 'Restaurant']]);

        $this->getJson(route('api.v1.admin.vehicle-types.index', ['search' => 'صيدل']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pharmacy->id);

        foreach (['RESTAU', 'restau', 'ReStAu'] as $search) {
            $this->getJson(route('api.v1.admin.vehicle-types.index', ['search' => $search]))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $restaurant->id);
        }
    }

    public function test_percent_and_underscore_in_the_search_are_literal(): void
    {
        $underscore = VehicleType::factory()->create(['name' => ['ar' => 'وجبات', 'en' => 'Fast_Food']]);
        VehicleType::factory()->create(['name' => ['ar' => 'أكل', 'en' => 'FastXFood']]);
        $percent = VehicleType::factory()->create(['name' => ['ar' => 'خصم', 'en' => '100% Organic']]);
        VehicleType::factory()->create(['name' => ['ar' => 'عضوي', 'en' => '1000 Organic']]);

        $this->assertSame([$underscore->id], $this->getJson(route('api.v1.admin.vehicle-types.index', ['search' => 't_f']))->json('data.*.id'));
        $this->assertSame([$percent->id], $this->getJson(route('api.v1.admin.vehicle-types.index', ['search' => '0%']))->json('data.*.id'));
    }

    public function test_show_returns_one_type(): void
    {
        $type = VehicleType::factory()->create();

        $this->getJson(route('api.v1.admin.vehicle-types.show', $type))
            ->assertOk()
            ->assertJsonPath('data.id', $type->id)
            ->assertJsonPath('data.vehicles_count', 0);
    }

    public function test_a_type_is_created_at_the_end_of_the_list_by_default(): void
    {
        VehicleType::factory()->create(['sort_order' => 7]);

        $this->postJson(route('api.v1.admin.vehicle-types.store'), ['name' => ['ar' => 'مخبز', 'en' => 'Bakery']])
            ->assertCreated()
            ->assertJsonPath('data.name', ['ar' => 'مخبز', 'en' => 'Bakery'])
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.sort_order', 8);

        $this->postJson(route('api.v1.admin.vehicle-types.store'), ['name' => ['ar' => 'ورد', 'en' => 'Flowers'], 'is_active' => false, 'sort_order' => 3])
            ->assertCreated()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.sort_order', 3);
    }

    public function test_the_licence_and_food_flags_default_to_true_and_can_be_changed(): void
    {
        $id = $this->postJson(route('api.v1.admin.vehicle-types.store'), ['name' => ['ar' => 'سيارة', 'en' => 'Car']])
            ->assertCreated()
            ->assertJsonPath('data.requires_license', true)
            ->assertJsonPath('data.can_carry_food', true)
            ->json('data.id');

        $this->patchJson(route('api.v1.admin.vehicle-types.update', $id), ['requires_license' => false, 'can_carry_food' => false])
            ->assertOk()
            ->assertJsonPath('data.requires_license', false)
            ->assertJsonPath('data.can_carry_food', false);
    }

    public function test_both_names_are_required(): void
    {
        $this->postJson(route('api.v1.admin.vehicle-types.store'), ['name' => ['ar' => 'مخبز']], ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name.en']);

        $this->postJson(route('api.v1.admin.vehicle-types.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_a_name_used_by_another_type_is_rejected_in_either_language(): void
    {
        $existing = VehicleType::factory()->create(['name' => ['ar' => 'مطعم', 'en' => 'Restaurant']]);

        $response = $this->postJson(route('api.v1.admin.vehicle-types.store'), ['name' => ['ar' => 'مطعم', 'en' => 'Diner']], ['Accept-Language' => 'en'])
            ->assertUnprocessable();
        $this->assertSame(['Another vehicle type already has this name.'], $response->json('errors')['name.ar']);

        $this->postJson(route('api.v1.admin.vehicle-types.store'), ['name' => ['ar' => 'كافيه', 'en' => 'Restaurant']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name.en']);

        $other = VehicleType::factory()->create();
        $this->patchJson(route('api.v1.admin.vehicle-types.update', $other), ['name' => ['ar' => 'جديد', 'en' => 'Restaurant']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name.en']);

        $this->patchJson(route('api.v1.admin.vehicle-types.update', $existing), ['name' => ['ar' => 'مطعم', 'en' => 'Restaurant']])
            ->assertOk();
    }

    public function test_a_name_is_taken_ignoring_letter_case(): void
    {
        $restaurant = VehicleType::factory()->create(['name' => ['ar' => 'مطعم', 'en' => 'Restaurant']]);
        $other = VehicleType::factory()->create();

        $created = $this->postJson(route('api.v1.admin.vehicle-types.store'), ['name' => ['ar' => 'كافيه', 'en' => 'restaurant']], ['Accept-Language' => 'en'])
            ->assertUnprocessable();
        $this->assertSame(['Another vehicle type already has this name.'], $created->json('errors')['name.en']);

        $updated = $this->patchJson(route('api.v1.admin.vehicle-types.update', $other), ['name' => ['ar' => 'جديد', 'en' => 'RESTAURANT']], ['Accept-Language' => 'en'])
            ->assertUnprocessable();
        $this->assertSame(['Another vehicle type already has this name.'], $updated->json('errors')['name.en']);

        $this->patchJson(route('api.v1.admin.vehicle-types.update', $restaurant), ['name' => ['ar' => 'مطعم', 'en' => 'restaurant']])
            ->assertOk()
            ->assertJsonPath('data.name.en', 'restaurant');
    }

    public function test_update_changes_only_the_fields_sent(): void
    {
        $type = VehicleType::factory()->create(['name' => ['ar' => 'قديم', 'en' => 'Old'], 'sort_order' => 4]);

        $this->patchJson(route('api.v1.admin.vehicle-types.update', $type), ['sort_order' => 9])
            ->assertOk()
            ->assertJsonPath('data.sort_order', 9)
            ->assertJsonPath('data.name', ['ar' => 'قديم', 'en' => 'Old']);

        $this->patchJson(route('api.v1.admin.vehicle-types.update', $type), ['name' => ['ar' => 'جديد', 'en' => 'New']])
            ->assertOk()
            ->assertJsonPath('data.name', ['ar' => 'جديد', 'en' => 'New']);

        $this->patchJson(route('api.v1.admin.vehicle-types.update', $type), ['name' => ['ar' => 'فقط']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name.en']);
    }

    public function test_activate_and_deactivate_change_the_public_list(): void
    {
        $type = VehicleType::factory()->create();

        $this->postJson(route('api.v1.admin.vehicle-types.deactivate', $type))
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
        $this->getJson(route('api.v1.public.vehicle-types.index'))->assertJsonCount(0, 'data');

        $this->postJson(route('api.v1.admin.vehicle-types.activate', $type))
            ->assertOk()
            ->assertJsonPath('data.is_active', true);
        $this->getJson(route('api.v1.public.vehicle-types.index'))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $type->id);
    }

    public function test_an_unused_type_is_deleted(): void
    {
        $type = VehicleType::factory()->create();

        $this->deleteJson(route('api.v1.admin.vehicle-types.destroy', $type))->assertNoContent();

        $this->assertModelMissing($type);
    }

    public function test_a_type_used_by_a_vehicle_is_not_deleted(): void
    {
        $type = VehicleType::factory()->create();
        Vehicle::factory()->create(['vehicle_type_id' => $type->id]);

        $this->deleteJson(route('api.v1.admin.vehicle-types.destroy', $type), [], ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.vehicle_type.0', 'This vehicle type is used by vehicles, so it cannot be deleted. Deactivate it instead.');

        $this->deleteJson(route('api.v1.admin.vehicle-types.destroy', $type), [], ['Accept-Language' => 'ar'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.vehicle_type.0', 'نوع المركبة هذا مستخدم في مركبات، لذلك لا يمكن حذفه. قم بإيقافه بدلًا من ذلك.');

        $this->assertModelExists($type);
    }

    public function test_an_unknown_type_is_404(): void
    {
        $this->getJson(route('api.v1.admin.vehicle-types.show', 999))->assertNotFound();
    }
}
