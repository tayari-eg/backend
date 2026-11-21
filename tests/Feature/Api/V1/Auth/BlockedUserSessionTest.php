<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockedUserSessionTest extends TestCase
{
    use RefreshDatabase;

    private const array SPA_HEADERS = ['Origin' => 'http://localhost:3000', 'Referer' => 'http://localhost:3000/', 'Accept-Language' => 'en'];

    private User $blockedUser;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost:3000']]);
        $this->blockedUser = User::factory()->blocked()->create();
        $this->actingAs($this->blockedUser, 'web');
    }

    public function test_me_is_refused(): void
    {
        $this->getJson(route('api.v1.auth.me'), self::SPA_HEADERS)
            ->assertForbidden()
            ->assertJsonPath('message', 'Your account has been blocked. Please contact support.');
    }

    public function test_changing_the_password_is_refused(): void
    {
        $this->putJson(route('api.v1.auth.password.update'), [
            'current_password' => 'password',
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ], self::SPA_HEADERS)->assertForbidden();
    }

    public function test_devices_are_refused(): void
    {
        $this->postJson(route('api.v1.auth.devices.store'), ['platform' => 'web', 'push_token' => 'fcm-token'], self::SPA_HEADERS)->assertForbidden();
        $this->deleteJson(route('api.v1.auth.devices.destroy'), ['push_token' => 'fcm-token'], self::SPA_HEADERS)->assertForbidden();

        $this->assertDatabaseCount('user_devices', 0);
    }

    public function test_logging_out_still_works(): void
    {
        $this->postJson(route('api.v1.auth.logout'), [], self::SPA_HEADERS)->assertOk();

        $this->assertGuest('web');
    }
}
