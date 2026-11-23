<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Catalog\Models\Area;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Tayars\Models\TayarWorkSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AreaManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, mixed>
     */
    private const array NASR_CITY = [
        'name' => ['ar' => 'مدينة نصر', 'en' => 'Nasr City'],
        'city' => ['ar' => 'القاهرة', 'en' => 'Cairo'],
        'center_lat' => 30.0566,
        'center_lng' => 31.3301,
        'radius_km' => 6.5,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs($this->createStaffUser(StaffRole::Admin));
    }

    public function test_the_list_shows_active_and_inactive_areas_in_sort_order_with_both_languages(): void
    {
        $second = Area::factory()->inactive()->create(['sort_order' => 2, 'name' => ['ar' => 'المعادي', 'en' => 'Maadi']]);
        $first = Area::factory()->create(['sort_order' => 1]);
        TayarWorkSession::factory()->create(['mode_id' => Mode::factory()->area()->create()->id, 'area_id' => $first->id, 'radius_km' => null]);

        $this->getJson(route('api.v1.admin.areas.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.0.work_sessions_count', 1)
            ->assertJsonPath('data.1.id', $second->id)
            ->assertJsonPath('data.1.name', ['ar' => 'المعادي', 'en' => 'Maadi'])
            ->assertJsonPath('data.1.city', ['ar' => 'القاهرة', 'en' => 'Cairo'])
            ->assertJsonPath('data.1.is_active', false);
    }

    public function test_the_list_filters_by_is_active_and_search(): void
    {
        $nasrCity = Area::factory()->create(['name' => ['ar' => 'مدينة نصر', 'en' => 'Nasr City']]);
        $maadi = Area::factory()->inactive()->create(['name' => ['ar' => 'المعادي', 'en' => 'Maadi']]);

        $this->assertSame([$maadi->id], $this->getJson(route('api.v1.admin.areas.index', ['is_active' => 'false']))->json('data.*.id'));
        $this->assertSame([$nasrCity->id], $this->getJson(route('api.v1.admin.areas.index', ['search' => 'NASR']))->json('data.*.id'));
        $this->assertSame([$nasrCity->id], $this->getJson(route('api.v1.admin.areas.index', ['search' => 'نصر']))->json('data.*.id'));
    }

    public function test_show_returns_one_area(): void
    {
        $area = Area::factory()->create();

        $this->getJson(route('api.v1.admin.areas.show', $area))
            ->assertOk()
            ->assertJsonPath('data.id', $area->id)
            ->assertJsonPath('data.work_sessions_count', 0);
    }

    public function test_an_area_is_created_at_the_end_of_the_list_by_default(): void
    {
        Area::factory()->create(['sort_order' => 7]);

        $this->postJson(route('api.v1.admin.areas.store'), self::NASR_CITY)
            ->assertCreated()
            ->assertJsonPath('data.name', ['ar' => 'مدينة نصر', 'en' => 'Nasr City'])
            ->assertJsonPath('data.city', ['ar' => 'القاهرة', 'en' => 'Cairo'])
            ->assertJsonPath('data.center', ['lat' => 30.0566, 'lng' => 31.3301])
            ->assertJsonPath('data.radius_km', 6.5)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.sort_order', 8);

        $this->assertDatabaseHas('areas', ['center_lat' => '30.0566000', 'radius_km' => '6.50']);
    }

    public function test_the_centre_and_radius_are_validated(): void
    {
        $this->postJson(route('api.v1.admin.areas.store'), [...self::NASR_CITY, 'center_lat' => 91, 'center_lng' => 31.123456789, 'radius_km' => 0.4])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['center_lat', 'center_lng', 'radius_km']);

        $this->postJson(route('api.v1.admin.areas.store'), [...self::NASR_CITY, 'radius_km' => 50.5, 'city' => ['ar' => 'القاهرة']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['radius_km', 'city.en']);

        $this->postJson(route('api.v1.admin.areas.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'city', 'center_lat', 'center_lng', 'radius_km']);
    }

    public function test_a_name_used_by_another_area_is_rejected_ignoring_letter_case(): void
    {
        $existing = Area::factory()->create(['name' => ['ar' => 'مدينة نصر', 'en' => 'Nasr City']]);

        $response = $this->postJson(route('api.v1.admin.areas.store'), [...self::NASR_CITY, 'name' => ['ar' => 'جديد', 'en' => 'NASR CITY']], ['Accept-Language' => 'en'])
            ->assertUnprocessable();
        $this->assertSame(['Another area already has this name.'], $response->json('errors')['name.en']);

        $this->patchJson(route('api.v1.admin.areas.update', $existing), ['name' => ['ar' => 'مدينة نصر', 'en' => 'Nasr City']])
            ->assertOk();
    }

    public function test_update_changes_only_the_fields_sent(): void
    {
        $area = Area::factory()->create(['name' => ['ar' => 'قديم', 'en' => 'Old'], 'radius_km' => '3.00']);

        $this->patchJson(route('api.v1.admin.areas.update', $area), ['radius_km' => 8.25])
            ->assertOk()
            ->assertJsonPath('data.radius_km', 8.25)
            ->assertJsonPath('data.name', ['ar' => 'قديم', 'en' => 'Old']);

        $this->patchJson(route('api.v1.admin.areas.update', $area), ['city' => ['ar' => 'الجيزة', 'en' => 'Giza']])
            ->assertOk()
            ->assertJsonPath('data.city', ['ar' => 'الجيزة', 'en' => 'Giza']);
    }

    public function test_activate_and_deactivate_change_the_public_list(): void
    {
        $area = Area::factory()->create();

        $this->postJson(route('api.v1.admin.areas.deactivate', $area))
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
        $this->getJson(route('api.v1.public.areas.index'))->assertJsonCount(0, 'data');

        $this->postJson(route('api.v1.admin.areas.activate', $area))
            ->assertOk()
            ->assertJsonPath('data.is_active', true);
        $this->getJson(route('api.v1.public.areas.index'))->assertJsonCount(1, 'data');
    }

    public function test_an_unused_area_is_deleted(): void
    {
        $area = Area::factory()->create();

        $this->deleteJson(route('api.v1.admin.areas.destroy', $area))->assertNoContent();

        $this->assertModelMissing($area);
    }

    public function test_an_area_a_work_session_used_is_not_deleted(): void
    {
        $area = Area::factory()->create();
        TayarWorkSession::factory()->create(['mode_id' => Mode::factory()->area()->create()->id, 'area_id' => $area->id, 'radius_km' => null, 'ended_at' => now()]);

        $this->deleteJson(route('api.v1.admin.areas.destroy', $area), [], ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.area.0', 'Tayars have worked in this area, so it cannot be deleted. Deactivate it instead.');

        $this->deleteJson(route('api.v1.admin.areas.destroy', $area), [], ['Accept-Language' => 'ar'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.area.0', 'عمل طيارون في هذه المنطقة، لذلك لا يمكن حذفها. قم بإيقافها بدلًا من ذلك.');

        $this->assertModelExists($area);
    }

    public function test_an_unknown_area_is_404(): void
    {
        $this->getJson(route('api.v1.admin.areas.show', 999))->assertNotFound();
    }
}
