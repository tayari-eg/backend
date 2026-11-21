<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionLoginTest extends TestCase
{
    use RefreshDatabase;

    private const array SPA_HEADERS = ['Origin' => 'http://localhost:3000', 'Referer' => 'http://localhost:3000/'];

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost:3000']]);
    }

    public function test_the_website_logs_in_with_a_session_and_no_token(): void
    {
        $user = User::factory()->create(['phone' => '+201012345678']);

        $response = $this->postJson(route('api.v1.auth.login'), ['phone' => '01012345678', 'password' => 'password'], self::SPA_HEADERS);

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonMissingPath('token');
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->getJson(route('api.v1.auth.me'), self::SPA_HEADERS)->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_a_request_from_outside_the_website_gets_no_session(): void
    {
        User::factory()->create(['phone' => '+201012345678']);

        $this->postJson(route('api.v1.auth.login'), ['phone' => '01012345678', 'password' => 'password'], ['Origin' => 'https://evil.example'])
            ->assertBadRequest();

        $this->assertGuest('web');
    }

    public function test_a_blocked_user_cannot_log_in(): void
    {
        User::factory()->blocked()->create(['phone' => '+201012345678']);

        $this->postJson(route('api.v1.auth.login'), ['phone' => '01012345678', 'password' => 'password'], self::SPA_HEADERS)
            ->assertForbidden();

        $this->assertGuest('web');
    }

    public function test_an_unverified_phone_cannot_log_in_when_verification_is_required(): void
    {
        config(['tayari.auth.require_phone_verification' => true]);
        User::factory()->unverifiedPhone()->create(['phone' => '+201012345678']);

        $this->postJson(route('api.v1.auth.login'), ['phone' => '01012345678', 'password' => 'password'], [...self::SPA_HEADERS, 'Accept-Language' => 'ar'])
            ->assertForbidden()
            ->assertJsonPath('message', 'من فضلك أكّد رقم موبايلك أولًا.');

        $this->assertGuest('web');
    }

    public function test_an_unverified_phone_can_log_in_when_verification_is_not_required(): void
    {
        config(['tayari.auth.require_phone_verification' => false]);
        $user = User::factory()->unverifiedPhone()->create(['phone' => '+201012345678']);

        $this->postJson(route('api.v1.auth.login'), ['phone' => '01012345678', 'password' => 'password'], self::SPA_HEADERS)
            ->assertOk();

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_wrong_credentials_are_rejected(): void
    {
        User::factory()->create(['phone' => '+201012345678']);

        $this->postJson(route('api.v1.auth.login'), ['phone' => '01012345678', 'password' => 'wrong-pass'], self::SPA_HEADERS)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);

        $this->assertGuest('web');
    }
}
