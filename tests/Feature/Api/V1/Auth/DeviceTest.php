<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Domains\Accounts\Enums\Platform;
use App\Domains\Accounts\Models\UserDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_the_push_token_without_returning_it(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson(route('api.v1.auth.devices.store'), [
            'platform' => 'ios',
            'push_token' => 'fcm-token-1',
            'app_version' => '1.2.0',
        ])->assertCreated()
            ->assertJsonPath('data.platform', 'ios')
            ->assertJsonPath('data.app_version', '1.2.0')
            ->assertJsonMissingPath('data.push_token');

        $device = UserDevice::query()->sole();
        $this->assertTrue($device->user->is($user));
        $this->assertSame(Platform::Ios, $device->platform);
        $this->assertNotNull($device->last_seen_at);
    }

    public function test_saving_the_same_token_again_updates_it(): void
    {
        $user = User::factory()->create();
        UserDevice::factory()->for($user)->create(['push_token' => 'fcm-token-1', 'app_version' => '1.0.0']);
        Sanctum::actingAs($user);

        $this->postJson(route('api.v1.auth.devices.store'), ['platform' => 'android', 'push_token' => 'fcm-token-1', 'app_version' => '1.1.0'])->assertOk();

        $this->assertSame('1.1.0', UserDevice::query()->sole()->app_version);
    }

    public function test_a_token_saved_for_another_user_moves_to_the_new_user(): void
    {
        $previousUser = User::factory()->create();
        UserDevice::factory()->for($previousUser)->create(['push_token' => 'shared-device-token']);
        $newUser = User::factory()->create();
        Sanctum::actingAs($newUser);

        $this->postJson(route('api.v1.auth.devices.store'), ['platform' => 'android', 'push_token' => 'shared-device-token'])->assertSuccessful();

        $this->assertSame($newUser->id, UserDevice::query()->sole()->user_id);
    }

    public function test_a_user_can_remove_only_their_own_token(): void
    {
        $user = User::factory()->create();
        $own = UserDevice::factory()->for($user)->create(['push_token' => 'mine']);
        $someoneElses = UserDevice::factory()->create(['push_token' => 'theirs']);
        Sanctum::actingAs($user);

        $this->deleteJson(route('api.v1.auth.devices.destroy'), ['push_token' => 'mine'])->assertNoContent();
        $this->deleteJson(route('api.v1.auth.devices.destroy'), ['push_token' => 'theirs'])->assertNoContent();

        $this->assertModelMissing($own);
        $this->assertModelExists($someoneElses);
    }

    public function test_it_validates_the_platform(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.v1.auth.devices.store'), ['platform' => 'windows', 'push_token' => 'x'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['platform']);
    }

    public function test_guests_get_401(): void
    {
        $this->postJson(route('api.v1.auth.devices.store'), ['platform' => 'ios', 'push_token' => 'x'])->assertUnauthorized();
    }
}
