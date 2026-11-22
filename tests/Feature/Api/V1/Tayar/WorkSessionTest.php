<?php

namespace Tests\Feature\Api\V1\Tayar;

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

        $this->postJson(route('api.v1.tayar.online'), ['lat' => 30.0444, 'lng' => 31.2357])
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

        $this->postJson(route('api.v1.tayar.online'), ['lat' => 30.0444, 'lng' => 31.2357], ['Accept-Language' => 'en'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Switch to tayar mode before going online.');

        $this->postJson(route('api.v1.tayar.online'), ['lat' => 30.0444, 'lng' => 31.2357], ['Accept-Language' => 'ar'])
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

        $this->postJson(route('api.v1.tayar.online'), ['lat' => 30.0444, 'lng' => 31.2357], ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.vehicle.0', 'You need an approved vehicle to go online.');
    }

    public function test_going_online_twice_keeps_one_session(): void
    {
        Sanctum::actingAs(TayarProfile::factory()->approved()->create()->user);

        $first = $this->postJson(route('api.v1.tayar.online'), ['lat' => 30.0444, 'lng' => 31.2357])->json('data.work_session.id');
        $second = $this->postJson(route('api.v1.tayar.online'), ['lat' => 30.0609, 'lng' => 31.2197])
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
}
