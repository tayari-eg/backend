<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Tayars\Models\TayarWorkSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ModeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs($this->createStaffUser(StaffRole::Admin));
    }

    public function test_the_list_shows_active_and_inactive_modes_in_sort_order_with_both_languages(): void
    {
        $zone = Mode::factory()->zone()->create(['sort_order' => 2]);
        $longDistance = Mode::factory()->longDistance()->inactive()->create([
            'sort_order' => 1,
            'name' => ['ar' => 'مسافات طويلة', 'en' => 'Long distance'],
            'description' => ['ar' => 'اتجاه واحد', 'en' => 'One direction'],
        ]);
        TayarWorkSession::factory()->create(['mode_id' => $zone->id]);

        $this->getJson(route('api.v1.admin.modes.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $longDistance->id)
            ->assertJsonPath('data.0.code', 'long_distance')
            ->assertJsonPath('data.0.name', ['ar' => 'مسافات طويلة', 'en' => 'Long distance'])
            ->assertJsonPath('data.0.description', ['ar' => 'اتجاه واحد', 'en' => 'One direction'])
            ->assertJsonPath('data.0.allows_food', false)
            ->assertJsonPath('data.0.is_active', false)
            ->assertJsonPath('data.1.id', $zone->id)
            ->assertJsonPath('data.1.work_sessions_count', 1);
    }

    public function test_the_list_filters_by_is_active_and_search(): void
    {
        $zone = Mode::factory()->zone()->create(['name' => ['ar' => 'حولك', 'en' => 'Zone']]);
        $area = Mode::factory()->area()->inactive()->create(['name' => ['ar' => 'منطقة', 'en' => 'Area']]);

        $this->assertSame([$area->id], $this->getJson(route('api.v1.admin.modes.index', ['is_active' => 'false']))->json('data.*.id'));
        $this->assertSame([$zone->id], $this->getJson(route('api.v1.admin.modes.index', ['search' => 'zON']))->json('data.*.id'));
    }

    public function test_show_returns_one_mode(): void
    {
        $mode = Mode::factory()->create();

        $this->getJson(route('api.v1.admin.modes.show', $mode))
            ->assertOk()
            ->assertJsonPath('data.id', $mode->id)
            ->assertJsonPath('data.code', 'zone')
            ->assertJsonPath('data.work_sessions_count', 0);
    }

    public function test_update_changes_only_the_fields_sent_and_never_the_code(): void
    {
        $mode = Mode::factory()->zone()->create(['name' => ['ar' => 'قديم', 'en' => 'Old'], 'sort_order' => 4]);

        $this->patchJson(route('api.v1.admin.modes.update', $mode), [
            'description' => ['ar' => 'وصف', 'en' => 'About'],
            'allows_food' => false,
            'sort_order' => 9,
            'code' => 'area',
        ])
            ->assertOk()
            ->assertJsonPath('data.code', 'zone')
            ->assertJsonPath('data.description', ['ar' => 'وصف', 'en' => 'About'])
            ->assertJsonPath('data.allows_food', false)
            ->assertJsonPath('data.sort_order', 9)
            ->assertJsonPath('data.name', ['ar' => 'قديم', 'en' => 'Old']);

        $this->patchJson(route('api.v1.admin.modes.update', $mode), ['description' => ['ar' => 'فقط']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['description.en']);
    }

    public function test_a_name_used_by_another_mode_is_rejected_ignoring_letter_case(): void
    {
        Mode::factory()->zone()->create(['name' => ['ar' => 'حولك', 'en' => 'Zone']]);
        $area = Mode::factory()->area()->create();

        $response = $this->patchJson(route('api.v1.admin.modes.update', $area), ['name' => ['ar' => 'جديد', 'en' => 'ZONE']], ['Accept-Language' => 'en'])
            ->assertUnprocessable();
        $this->assertSame(['Another mode already has this name.'], $response->json('errors')['name.en']);
    }

    public function test_activate_and_deactivate_change_the_public_list(): void
    {
        $mode = Mode::factory()->create();

        $this->postJson(route('api.v1.admin.modes.deactivate', $mode))
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
        $this->getJson(route('api.v1.public.modes.index'))->assertJsonCount(0, 'data');

        $this->postJson(route('api.v1.admin.modes.activate', $mode))
            ->assertOk()
            ->assertJsonPath('data.is_active', true);
        $this->getJson(route('api.v1.public.modes.index'))->assertJsonCount(1, 'data');
    }

    public function test_modes_cannot_be_created_or_deleted(): void
    {
        $mode = Mode::factory()->create();

        $this->postJson('/api/v1/admin/modes', ['name' => ['ar' => 'جديد', 'en' => 'New']])->assertStatus(405);
        $this->deleteJson("/api/v1/admin/modes/{$mode->id}")->assertStatus(405);

        $this->assertModelExists($mode);
    }

    public function test_an_unknown_mode_is_404(): void
    {
        $this->getJson(route('api.v1.admin.modes.show', 999))->assertNotFound();
    }
}
