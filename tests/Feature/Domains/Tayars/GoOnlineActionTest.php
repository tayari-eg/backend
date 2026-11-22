<?php

namespace Tests\Feature\Domains\Tayars;

use App\Domains\Tayars\Actions\GoOfflineAction;
use App\Domains\Tayars\Actions\GoOnlineAction;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\TayarWorkSession;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GoOnlineActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_going_online_starts_a_session_with_the_active_vehicle_at_the_position(): void
    {
        $profile = TayarProfile::factory()->approved()->create();

        $profile = app(GoOnlineAction::class)->handle($profile, 30.0444, 31.2357);

        $this->assertTrue($profile->is_online);
        $this->assertSame('30.0444000', $profile->current_lat);
        $this->assertNotNull($profile->location_updated_at);
        $session = TayarWorkSession::query()->sole();
        $this->assertSame($session->id, $profile->current_work_session_id);
        $this->assertSame($profile->vehicle->id, $session->vehicle_id);
        $this->assertNull($session->ended_at);
    }

    public function test_going_online_again_keeps_the_session_and_moves_the_position(): void
    {
        $profile = app(GoOnlineAction::class)->handle(TayarProfile::factory()->approved()->create(), 30.0444, 31.2357);

        $again = app(GoOnlineAction::class)->handle($profile, 30.0609, 31.2197);

        $this->assertSame(1, TayarWorkSession::query()->count());
        $this->assertSame($profile->current_work_session_id, $again->current_work_session_id);
        $this->assertSame('30.0609000', $again->current_lat);
    }

    public function test_going_online_needs_an_approved_active_vehicle(): void
    {
        $profile = TayarProfile::factory()->approved()->create();
        $profile->vehicle->forceFill(['is_active' => false])->save();

        try {
            app(GoOnlineAction::class)->handle($profile, 30.0444, 31.2357);
            $this->fail('Going online without an active vehicle was expected to fail.');
        } catch (ValidationException $exception) {
            $this->assertSame([__('tayars.no_active_vehicle')], $exception->errors()['vehicle']);
        }

        $this->assertFalse($profile->refresh()->is_online);
    }

    public function test_going_online_needs_an_approved_application(): void
    {
        $this->expectException(AuthorizationException::class);

        app(GoOnlineAction::class)->handle(TayarProfile::factory()->create(), 30.0444, 31.2357);
    }

    public function test_going_offline_closes_the_session_and_is_safe_to_repeat(): void
    {
        $profile = app(GoOnlineAction::class)->handle(TayarProfile::factory()->approved()->create(), 30.0444, 31.2357);

        $this->assertTrue(app(GoOfflineAction::class)->handle($profile));
        $this->assertFalse(app(GoOfflineAction::class)->handle($profile));

        $this->assertFalse($profile->refresh()->is_online);
        $this->assertNull($profile->current_work_session_id);
        $this->assertNotNull(TayarWorkSession::query()->sole()->ended_at);
    }
}
