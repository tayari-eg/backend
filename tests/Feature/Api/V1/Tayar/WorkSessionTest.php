<?php

namespace Tests\Feature\Api\V1\Tayar;

use App\Domains\Catalog\Models\Area;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\TayarWorkSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tayar_goes_online_and_offline(): void
    {
        $profile = TayarProfile::factory()->approved()->create();
        Sanctum::actingAs($profile->user);

        $this->postJson(route('api.v1.tayar.online'), $this->zoneOnlinePayload())
            ->assertOk()
            ->assertJsonPath('data.is_online', true)
            ->assertJsonPath('data.work_session.vehicle_id', $profile->vehicle->id)
            ->assertJsonPath('data.location.lat', 30.0444)
            ->assertJsonPath('data.location.lng', 31.2357);

        $this->postJson(route('api.v1.tayar.offline'))
            ->assertOk()
            ->assertJsonPath('data.is_online', false)
            ->assertJsonPath('data.work_session', null)
            ->assertJsonPath('data.location.lat', 30.0444);

        $this->assertNotNull(TayarWorkSession::query()->sole()->ended_at);
    }

    public function test_going_online_in_client_mode_is_403(): void
    {
        $profile = TayarProfile::factory()->approved()->create();
        $profile->user->forceFill(['last_mode' => 'client'])->save();
        Sanctum::actingAs($profile->user);

        $this->postJson(route('api.v1.tayar.online'), $this->zoneOnlinePayload(), ['Accept-Language' => 'en'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Switch to tayar mode before going online.');

        $this->postJson(route('api.v1.tayar.online'), $this->zoneOnlinePayload(), ['Accept-Language' => 'ar'])
            ->assertForbidden()
            ->assertJsonPath('message', 'انتقل إلى وضع الطيار أولًا قبل بدء العمل.');

        $this->assertFalse($profile->refresh()->is_online);
        $this->assertSame(0, TayarWorkSession::query()->count());
    }

    public function test_going_online_needs_a_position(): void
    {
        Sanctum::actingAs(TayarProfile::factory()->approved()->create()->user);

        $this->postJson(route('api.v1.tayar.online'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat', 'lng']);

        $this->postJson(route('api.v1.tayar.online'), ['lat' => 91, 'lng' => 30.12345678])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat', 'lng']);
    }

    public function test_going_online_without_an_active_vehicle_is_422(): void
    {
        $profile = TayarProfile::factory()->approved()->create();
        $profile->vehicle->forceFill(['is_active' => false])->save();
        Sanctum::actingAs($profile->user);

        $this->postJson(route('api.v1.tayar.online'), $this->zoneOnlinePayload(), ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.vehicle.0', 'You need an approved vehicle to go online.');
    }

    public function test_going_online_twice_keeps_one_session(): void
    {
        Sanctum::actingAs(TayarProfile::factory()->approved()->create()->user);

        $first = $this->postJson(route('api.v1.tayar.online'), $this->zoneOnlinePayload())->json('data.work_session.id');
        $second = $this->postJson(route('api.v1.tayar.online'), $this->zoneOnlinePayload(30.0609, 31.2197))
            ->assertJsonPath('data.location.lat', 30.0609)
            ->json('data.work_session.id');

        $this->assertSame($first, $second);
        $this->assertSame(1, TayarWorkSession::query()->count());
    }

    public function test_going_offline_while_offline_is_fine(): void
    {
        Sanctum::actingAs(TayarProfile::factory()->approved()->create()->user);

        $this->postJson(route('api.v1.tayar.offline'))
            ->assertOk()
            ->assertJsonPath('data.is_online', false);
    }

    public function test_going_online_in_the_zone_mode_keeps_the_radius(): void
    {
        Sanctum::actingAs(TayarProfile::factory()->approved()->create()->user);
        $zone = $this->zoneMode();

        $this->postJson(route('api.v1.tayar.online'), [...$this->zoneOnlinePayload(), 'radius_km' => 7.5], ['Accept-Language' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.work_session.mode.id', $zone->id)
            ->assertJsonPath('data.work_session.mode.code', 'zone')
            ->assertJsonPath('data.work_session.mode.name', $zone->getTranslation('name', 'en'))
            ->assertJsonPath('data.work_session.radius_km', 7.5)
            ->assertJsonPath('data.work_session.area', null)
            ->assertJsonPath('data.work_session.destination', null);

        $session = TayarWorkSession::query()->sole();
        $this->assertSame($zone->id, $session->mode_id);
        $this->assertSame('7.50', $session->radius_km);
    }

    public function test_going_online_in_the_area_mode_keeps_the_area(): void
    {
        Sanctum::actingAs(TayarProfile::factory()->approved()->create()->user);
        $mode = Mode::factory()->area()->create();
        $area = Area::factory()->create(['name' => ['ar' => 'مدينة نصر', 'en' => 'Nasr City']]);

        $this->postJson(route('api.v1.tayar.online'), ['lat' => 30.0444, 'lng' => 31.2357, 'mode_id' => $mode->id, 'area_id' => $area->id], ['Accept-Language' => 'ar'])
            ->assertOk()
            ->assertJsonPath('data.work_session.mode.code', 'area')
            ->assertJsonPath('data.work_session.area.id', $area->id)
            ->assertJsonPath('data.work_session.area.name', 'مدينة نصر')
            ->assertJsonPath('data.work_session.radius_km', null);

        $this->assertSame($area->id, TayarWorkSession::query()->sole()->area_id);
    }

    public function test_going_online_in_the_long_distance_mode_keeps_the_destination(): void
    {
        Sanctum::actingAs(TayarProfile::factory()->approved()->create()->user);
        $mode = Mode::factory()->longDistance()->create();

        $this->postJson(route('api.v1.tayar.online'), [
            'lat' => 30.0444,
            'lng' => 31.2357,
            'mode_id' => $mode->id,
            'destination_address' => 'Alexandria, Smouha',
            'destination_lat' => 31.2156,
            'destination_lng' => 29.9553,
        ])
            ->assertOk()
            ->assertJsonPath('data.work_session.destination.address', 'Alexandria, Smouha')
            ->assertJsonPath('data.work_session.destination.lat', 31.2156)
            ->assertJsonPath('data.work_session.destination.lng', 29.9553);

        $session = TayarWorkSession::query()->sole();
        $this->assertSame('31.2156000', $session->destination_lat);
        $this->assertSame('29.9553000', $session->destination_lng);
    }

    public function test_each_mode_needs_its_own_fields_and_refuses_the_others(): void
    {
        Sanctum::actingAs(TayarProfile::factory()->approved()->create()->user);
        $position = ['lat' => 30.0444, 'lng' => 31.2357];
        $zone = $this->zoneMode();
        $areaMode = Mode::factory()->area()->create();
        $longDistance = Mode::factory()->longDistance()->create();
        $area = Area::factory()->create();

        $this->postJson(route('api.v1.tayar.online'), $position)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['mode_id']);

        $this->postJson(route('api.v1.tayar.online'), [...$position, 'mode_id' => $zone->id, 'area_id' => $area->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['radius_km', 'area_id']);

        $this->postJson(route('api.v1.tayar.online'), [...$position, 'mode_id' => $areaMode->id, 'radius_km' => 5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['area_id', 'radius_km']);

        $this->postJson(route('api.v1.tayar.online'), [...$position, 'mode_id' => $longDistance->id, 'destination_address' => 'Alexandria'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['destination_lat', 'destination_lng'])
            ->assertJsonMissingValidationErrors(['destination_address']);

        $this->postJson(route('api.v1.tayar.online'), [...$position, 'mode_id' => $zone->id, 'radius_km' => 5, 'destination_address' => 'Alexandria'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['destination_address']);

        $this->assertSame(0, TayarWorkSession::query()->count());
    }

    public function test_the_zone_radius_must_be_within_the_limits(): void
    {
        config(['tayari.tayars.zone_min_radius_km' => 1, 'tayari.tayars.zone_max_radius_km' => 15]);
        Sanctum::actingAs(TayarProfile::factory()->approved()->create()->user);

        foreach ([0.5, 15.5, 2.555] as $radius) {
            $this->postJson(route('api.v1.tayar.online'), [...$this->zoneOnlinePayload(), 'radius_km' => $radius])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['radius_km']);
        }

        $this->postJson(route('api.v1.tayar.online'), [...$this->zoneOnlinePayload(), 'radius_km' => 15])->assertOk();
    }

    public function test_an_inactive_mode_or_area_is_refused(): void
    {
        Sanctum::actingAs(TayarProfile::factory()->approved()->create()->user);
        $inactiveZone = Mode::factory()->zone()->inactive()->create();
        $areaMode = Mode::factory()->area()->create();
        $inactiveArea = Area::factory()->inactive()->create();

        $this->postJson(route('api.v1.tayar.online'), ['lat' => 30.0444, 'lng' => 31.2357, 'mode_id' => $inactiveZone->id, 'radius_km' => 5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['mode_id']);

        $this->postJson(route('api.v1.tayar.online'), ['lat' => 30.0444, 'lng' => 31.2357, 'mode_id' => $areaMode->id, 'area_id' => $inactiveArea->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['area_id']);
    }

    public function test_going_online_again_with_other_settings_ends_the_session_and_starts_a_new_one(): void
    {
        Sanctum::actingAs(TayarProfile::factory()->approved()->create()->user);
        $areaMode = Mode::factory()->area()->create();
        $area = Area::factory()->create();

        $first = $this->postJson(route('api.v1.tayar.online'), $this->zoneOnlinePayload())->json('data.work_session.id');
        $wider = $this->postJson(route('api.v1.tayar.online'), [...$this->zoneOnlinePayload(), 'radius_km' => 10])
            ->assertOk()
            ->assertJsonPath('data.work_session.radius_km', 10)
            ->json('data.work_session.id');
        $inArea = $this->postJson(route('api.v1.tayar.online'), ['lat' => 30.0444, 'lng' => 31.2357, 'mode_id' => $areaMode->id, 'area_id' => $area->id])
            ->assertOk()
            ->assertJsonPath('data.is_online', true)
            ->json('data.work_session.id');

        $this->assertNotSame($first, $wider);
        $this->assertNotSame($wider, $inArea);
        $this->assertNotNull(TayarWorkSession::query()->find($first)->ended_at);
        $this->assertNotNull(TayarWorkSession::query()->find($wider)->ended_at);
        $this->assertNull(TayarWorkSession::query()->find($inArea)->ended_at);
        $this->assertSame(1, TayarWorkSession::query()->whereNull('ended_at')->count());
    }
}
