<?php

namespace Tests\Feature\Api\V1\Tayar;

use App\Domains\Tayars\Actions\GoOnlineAction;
use App\Domains\Tayars\Models\TayarProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use RefreshDatabase;

    private function onlineTayar(): TayarProfile
    {
        $profile = app(GoOnlineAction::class)->handle(TayarProfile::factory()->approved()->create(), 30.0444, 31.2357, $this->zoneWorkSessionSettings());
        Sanctum::actingAs($profile->user);

        return $profile;
    }

    public function test_an_online_tayar_sends_the_position(): void
    {
        $profile = $this->onlineTayar();
        $this->travel(10)->seconds();

        $this->postJson(route('api.v1.tayar.location'), ['lat' => 30.0609123, 'lng' => 31.2197456])->assertNoContent();

        $profile->refresh();
        $this->assertSame('30.0609123', $profile->current_lat);
        $this->assertSame('31.2197456', $profile->current_lng);
        $this->assertTrue($profile->location_updated_at->equalTo(now()->startOfSecond()));
    }

    public function test_only_lat_and_lng_are_taken(): void
    {
        $this->onlineTayar();

        $this->postJson(route('api.v1.tayar.location'), ['lat' => 'north'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat', 'lng']);
    }

    public function test_an_offline_tayar_gets_422(): void
    {
        Sanctum::actingAs(TayarProfile::factory()->approved()->create()->user);

        $this->postJson(route('api.v1.tayar.location'), ['lat' => 30.0444, 'lng' => 31.2357], ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', 'Go online before sending your location.');
    }

    public function test_location_updates_have_their_own_rate_limit(): void
    {
        config(['tayari.tayars.location_updates_per_minute' => 2]);
        $this->onlineTayar();

        $this->postJson(route('api.v1.tayar.location'), ['lat' => 30.0444, 'lng' => 31.2357])->assertNoContent();
        $this->postJson(route('api.v1.tayar.location'), ['lat' => 30.0444, 'lng' => 31.2357])->assertNoContent();
        $this->postJson(route('api.v1.tayar.location'), ['lat' => 30.0444, 'lng' => 31.2357])
            ->assertTooManyRequests()
            ->assertHeader('Retry-After');

        // Going offline is not counted against the location limit.
        $this->postJson(route('api.v1.tayar.offline'))->assertOk();
    }
}
