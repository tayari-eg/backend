<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class MobileTokenLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_a_sanctum_token_named_after_the_device(): void
    {
        $user = User::factory()->create(['phone' => '+201012345678']);

        $response = $this->postJson(route('api.v1.auth.token'), [
            'phone' => '01012345678',
            'password' => 'password',
            'device_name' => 'Pixel 8',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonMissingPath('data.password');

        $token = PersonalAccessToken::findToken($response->json('token'));
        $this->assertSame('Pixel 8', $token->name);
        $this->assertTrue($token->tokenable->is($user));
    }

    public function test_wrong_credentials_get_the_same_error_as_an_unknown_phone(): void
    {
        User::factory()->create(['phone' => '+201012345678']);

        $wrongPassword = $this->postJson(route('api.v1.auth.token'), ['phone' => '01012345678', 'password' => 'nope-nope', 'device_name' => 'Pixel']);
        $unknownPhone = $this->postJson(route('api.v1.auth.token'), ['phone' => '01112345678', 'password' => 'nope-nope', 'device_name' => 'Pixel']);

        $wrongPassword->assertUnprocessable()->assertJsonValidationErrors(['phone']);
        $this->assertSame($wrongPassword->json(), $unknownPhone->json());
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_a_blocked_user_cannot_log_in(): void
    {
        User::factory()->blocked()->create(['phone' => '+201012345678']);

        $this->postJson(route('api.v1.auth.token'), ['phone' => '01012345678', 'password' => 'password', 'device_name' => 'Pixel'], ['Accept-Language' => 'en'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Your account has been blocked. Please contact support.');
    }

    public function test_an_unverified_phone_cannot_log_in_when_verification_is_required(): void
    {
        config(['tayari.auth.require_phone_verification' => true]);
        User::factory()->unverifiedPhone()->create(['phone' => '+201012345678']);

        $this->postJson(route('api.v1.auth.token'), ['phone' => '01012345678', 'password' => 'password', 'device_name' => 'Pixel'], ['Accept-Language' => 'en'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Please verify your phone number first.');
    }

    public function test_an_unverified_phone_can_log_in_when_verification_is_not_required(): void
    {
        config(['tayari.auth.require_phone_verification' => false]);
        User::factory()->unverifiedPhone()->create(['phone' => '+201012345678']);

        $this->postJson(route('api.v1.auth.token'), ['phone' => '01012345678', 'password' => 'password', 'device_name' => 'Pixel'])
            ->assertOk()
            ->assertJsonPath('data.is_phone_verified', false);
    }

    public function test_login_is_rate_limited(): void
    {
        config(['tayari.auth.login_attempts_per_minute' => 2]);

        foreach (range(1, 2) as $attempt) {
            $this->postJson(route('api.v1.auth.token'), ['phone' => '01012345678', 'password' => 'wrong-pass', 'device_name' => 'Pixel'])->assertUnprocessable();
        }

        $this->postJson(route('api.v1.auth.token'), ['phone' => '01012345678', 'password' => 'wrong-pass', 'device_name' => 'Pixel'])->assertTooManyRequests();
    }

    public function test_login_is_also_limited_per_phone_across_ips(): void
    {
        config(['tayari.auth.login_attempts_per_minute' => 100, 'tayari.auth.login_attempts_per_phone_per_hour' => 2]);
        $payload = ['phone' => '01012345678', 'password' => 'wrong-pass', 'device_name' => 'Pixel'];

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])->postJson(route('api.v1.auth.token'), $payload)->assertUnprocessable();
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])->postJson(route('api.v1.auth.token'), $payload)->assertUnprocessable();
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.3'])->postJson(route('api.v1.auth.token'), $payload)->assertTooManyRequests();

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.3'])
            ->postJson(route('api.v1.auth.token'), [...$payload, 'phone' => '01112345678'])
            ->assertUnprocessable();
    }
}
