<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Enums\UserStatus;
use App\Domains\Tayars\Actions\GoOnlineAction;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\VehicleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TayarManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $query
     * @return list<int>
     */
    private function listedIds(array $query = []): array
    {
        return $this->getJson(route('api.v1.admin.tayars.index', $query))->assertOk()->json('data.*.id');
    }

    public function test_the_list_shows_approved_tayars_newest_first(): void
    {
        $older = TayarProfile::factory()->approved()->create();
        $newer = TayarProfile::factory()->approved()->create();
        TayarProfile::factory()->create();
        TayarProfile::factory()->rejected()->create();
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));

        $this->assertSame([$newer->id, $older->id], $this->listedIds());
    }

    public function test_the_list_filters_by_online_vehicle_type_blocked_and_search(): void
    {
        $online = app(GoOnlineAction::class)->handle(TayarProfile::factory()->approved()->create(), 30.0444, 31.2357);
        $bicycle = VehicleType::factory()->withoutLicense()->create();
        $cyclist = TayarProfile::factory()->approved()->create(['user_id' => User::factory()->create(['name' => 'Salma Nabil'])]);
        $cyclist->vehicle->update(['vehicle_type_id' => $bicycle->id]);
        $blocked = TayarProfile::factory()->approved()->create(['user_id' => User::factory()->create(['status' => UserStatus::Blocked])]);
        Sanctum::actingAs($this->createStaffUser());

        $this->assertSame([$online->id], $this->listedIds(['is_online' => 'true']));
        $this->assertSame([$cyclist->id], $this->listedIds(['vehicle_type_id' => $bicycle->id]));
        $this->assertSame([$blocked->id], $this->listedIds(['blocked' => 'true']));
        $this->assertNotContains($blocked->id, $this->listedIds(['blocked' => 'false']));
        $this->assertSame([$cyclist->id], $this->listedIds(['search' => 'salma']));
    }

    public function test_show_has_the_last_position_and_the_vehicle(): void
    {
        $tayar = app(GoOnlineAction::class)->handle(TayarProfile::factory()->approved()->create(), 30.0444, 31.2357);
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));

        $this->getJson(route('api.v1.admin.tayars.show', $tayar))
            ->assertOk()
            ->assertJsonPath('data.is_online', true)
            ->assertJsonPath('data.location.lat', 30.0444)
            ->assertJsonPath('data.vehicle.is_active', true)
            ->assertJsonPath('data.user.status', 'active');
    }

    public function test_a_tayar_that_is_not_approved_is_404(): void
    {
        Sanctum::actingAs($this->createStaffUser());

        $this->getJson(route('api.v1.admin.tayars.show', TayarProfile::factory()->create()))->assertNotFound();
    }

    public function test_a_tayar_is_blocked_through_the_users_block_and_drops_out_of_tayar_mode(): void
    {
        $tayar = TayarProfile::factory()->approved()->create();
        Sanctum::actingAs($this->createStaffUser(StaffRole::Ops));

        $this->postJson(route('api.v1.admin.users.block', $tayar->user), ['reason' => 'Many complaints'])->assertOk();

        Sanctum::actingAs($tayar->user->refresh());
        $this->postJson(route('api.v1.tayar.online'), ['lat' => 30.0444, 'lng' => 31.2357])->assertForbidden();
    }
}
