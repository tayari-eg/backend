<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Domains\Tayars\Actions\GoOnlineAction;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\TayarWorkSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SwitchModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_approved_tayar_switches_to_tayar_mode_and_back(): void
    {
        $profile = TayarProfile::factory()->approved()->create();
        Sanctum::actingAs($profile->user);

        $this->patchJson(route('api.v1.auth.mode.update'), ['mode' => 'tayar'])
            ->assertOk()
            ->assertJsonPath('data.last_mode', 'tayar')
            ->assertJsonPath('data.tayar_profile.status', 'approved');

        $this->assertSame('tayar', $profile->user->refresh()->last_mode->value);
    }

    public function test_switching_to_client_mode_sets_an_online_tayar_offline(): void
    {
        $profile = app(GoOnlineAction::class)->handle(TayarProfile::factory()->approved()->create(), 30.0444, 31.2357);
        $profile->user->forceFill(['last_mode' => 'tayar'])->save();
        Sanctum::actingAs($profile->user);

        $this->patchJson(route('api.v1.auth.mode.update'), ['mode' => 'client'])
            ->assertOk()
            ->assertJsonPath('data.last_mode', 'client')
            ->assertJsonPath('data.tayar_profile.is_online', false);

        $profile->refresh();
        $this->assertFalse($profile->is_online);
        $this->assertNull($profile->current_work_session_id);
        $this->assertNotNull(TayarWorkSession::query()->where('tayar_profile_id', $profile->id)->sole()->ended_at);

        // Back in client mode, going online is refused until the app switches to tayar mode again.
        $this->postJson(route('api.v1.tayar.online'), ['lat' => 30.0444, 'lng' => 31.2357])->assertForbidden();
    }

    public function test_tayar_mode_needs_an_approved_application(): void
    {
        Sanctum::actingAs(TayarProfile::factory()->create()->user);

        $this->patchJson(route('api.v1.auth.mode.update'), ['mode' => 'tayar'], ['Accept-Language' => 'en'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Tayar mode is only available after your tayar application is approved.');
    }

    public function test_the_mode_is_validated_and_needs_a_login(): void
    {
        $this->patchJson(route('api.v1.auth.mode.update'), ['mode' => 'client'])->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());

        $this->patchJson(route('api.v1.auth.mode.update'), ['mode' => 'driver'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['mode']);
    }
}
