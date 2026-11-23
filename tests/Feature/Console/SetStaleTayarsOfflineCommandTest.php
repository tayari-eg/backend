<?php

namespace Tests\Feature\Console;

use App\Domains\Tayars\Actions\GoOnlineAction;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\TayarWorkSession;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetStaleTayarsOfflineCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_tayars_without_a_recent_location_go_offline_and_their_session_closes(): void
    {
        config(['tayari.tayars.offline_after_minutes' => 5]);
        $stale = app(GoOnlineAction::class)->handle(TayarProfile::factory()->approved()->create(), 30.0444, 31.2357, $this->zoneWorkSessionSettings());
        $fresh = app(GoOnlineAction::class)->handle(TayarProfile::factory()->approved()->create(), 30.0609, 31.2197, $this->zoneWorkSessionSettings());
        $stale->forceFill(['location_updated_at' => now()->subMinutes(6)])->save();
        $fresh->forceFill(['location_updated_at' => now()->subMinutes(4)])->save();

        $this->artisan('tayars:set-stale-offline')
            ->expectsOutputToContain('1 tayar(s) set offline.')
            ->assertSuccessful();

        $this->assertFalse($stale->refresh()->is_online);
        $this->assertNull($stale->current_work_session_id);
        $this->assertNotNull(TayarWorkSession::query()->where('tayar_profile_id', $stale->id)->sole()->ended_at);
        $this->assertTrue($fresh->refresh()->is_online);
        $this->assertNull(TayarWorkSession::query()->where('tayar_profile_id', $fresh->id)->sole()->ended_at);
    }

    public function test_it_runs_every_minute(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($event): bool => str_contains($event->command, 'tayars:set-stale-offline'));

        $this->assertNotNull($event);
        $this->assertSame('* * * * *', $event->expression);
    }
}
