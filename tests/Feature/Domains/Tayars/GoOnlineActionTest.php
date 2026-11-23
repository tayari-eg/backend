<?php

namespace Tests\Feature\Domains\Tayars;

use App\Domains\Catalog\Models\Area;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Tayars\Actions\GoOfflineAction;
use App\Domains\Tayars\Actions\GoOnlineAction;
use App\Domains\Tayars\Data\WorkSessionSettings;
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

        $profile = app(GoOnlineAction::class)->handle($profile, 30.0444, 31.2357, $this->zoneWorkSessionSettings());

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
        $profile = app(GoOnlineAction::class)->handle(TayarProfile::factory()->approved()->create(), 30.0444, 31.2357, $this->zoneWorkSessionSettings());

        $again = app(GoOnlineAction::class)->handle($profile, 30.0609, 31.2197, $this->zoneWorkSessionSettings());

        $this->assertSame(1, TayarWorkSession::query()->count());
        $this->assertSame($profile->current_work_session_id, $again->current_work_session_id);
        $this->assertSame('30.0609000', $again->current_lat);
    }

    public function test_going_online_needs_an_approved_active_vehicle(): void
    {
        $profile = TayarProfile::factory()->approved()->create();
        $profile->vehicle->forceFill(['is_active' => false])->save();

        try {
            app(GoOnlineAction::class)->handle($profile, 30.0444, 31.2357, $this->zoneWorkSessionSettings());
            $this->fail('Going online without an active vehicle was expected to fail.');
        } catch (ValidationException $exception) {
            $this->assertSame([__('tayars.no_active_vehicle')], $exception->errors()['vehicle']);
        }

        $this->assertFalse($profile->refresh()->is_online);
    }

    public function test_going_online_needs_an_approved_application(): void
    {
        $this->expectException(AuthorizationException::class);

        app(GoOnlineAction::class)->handle(TayarProfile::factory()->create(), 30.0444, 31.2357, $this->zoneWorkSessionSettings());
    }

    public function test_going_offline_closes_the_session_and_is_safe_to_repeat(): void
    {
        $profile = app(GoOnlineAction::class)->handle(TayarProfile::factory()->approved()->create(), 30.0444, 31.2357, $this->zoneWorkSessionSettings());

        $this->assertTrue(app(GoOfflineAction::class)->handle($profile));
        $this->assertFalse(app(GoOfflineAction::class)->handle($profile));

        $this->assertFalse($profile->refresh()->is_online);
        $this->assertNull($profile->current_work_session_id);
        $this->assertNotNull(TayarWorkSession::query()->sole()->ended_at);
    }

    public function test_the_session_keeps_the_mode_and_its_settings(): void
    {
        $profile = app(GoOnlineAction::class)->handle(TayarProfile::factory()->approved()->create(), 30.0444, 31.2357, $this->zoneWorkSessionSettings('7.5'));

        $session = TayarWorkSession::query()->sole();
        $this->assertSame($profile->current_work_session_id, $session->id);
        $this->assertSame($this->zoneMode()->id, $session->mode_id);
        $this->assertSame('7.50', $session->radius_km);
        $this->assertNull($session->area_id);
        $this->assertNull($session->destination_address);
    }

    public function test_the_same_settings_written_differently_keep_the_session(): void
    {
        $profile = app(GoOnlineAction::class)->handle(TayarProfile::factory()->approved()->create(), 30.0444, 31.2357, $this->zoneWorkSessionSettings('5'));

        app(GoOnlineAction::class)->handle($profile, 30.0444, 31.2357, $this->zoneWorkSessionSettings('5.00'));

        $this->assertSame(1, TayarWorkSession::query()->count());
    }

    public function test_other_settings_end_the_session_and_start_a_new_one(): void
    {
        $profile = app(GoOnlineAction::class)->handle(TayarProfile::factory()->approved()->create(), 30.0444, 31.2357, $this->zoneWorkSessionSettings());
        $firstSessionId = $profile->current_work_session_id;
        $area = Area::factory()->create();

        $profile = app(GoOnlineAction::class)->handle($profile, 30.0609, 31.2197, new WorkSessionSettings(Mode::factory()->area()->create(), area: $area));

        $this->assertNotSame($firstSessionId, $profile->current_work_session_id);
        $this->assertNotNull(TayarWorkSession::query()->find($firstSessionId)->ended_at);
        $current = TayarWorkSession::query()->find($profile->current_work_session_id);
        $this->assertNull($current->ended_at);
        $this->assertSame($area->id, $current->area_id);
        $this->assertTrue($profile->is_online);
    }

    public function test_a_change_that_fails_keeps_the_current_session(): void
    {
        $profile = app(GoOnlineAction::class)->handle(TayarProfile::factory()->approved()->create(), 30.0444, 31.2357, $this->zoneWorkSessionSettings());
        $profile->vehicle->forceFill(['is_active' => false])->save();

        try {
            app(GoOnlineAction::class)->handle($profile, 30.0444, 31.2357, $this->zoneWorkSessionSettings('10'));
            $this->fail('Changing the settings without an active vehicle was expected to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('vehicle', $exception->errors());
        }

        $this->assertNull(TayarWorkSession::query()->sole()->ended_at);
        $this->assertTrue($profile->refresh()->is_online);
    }
}
