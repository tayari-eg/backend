<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Domains\Accounts\Enums\OtpPurpose;
use App\Domains\Accounts\Events\PhoneVerified;
use App\Domains\Accounts\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class VerifyPhoneTest extends TestCase
{
    use RefreshDatabase;

    private const string PHONE = '+201012345678';

    private function createCode(array $attributes = []): OtpCode
    {
        return OtpCode::factory()->withCode('123456')->create(['phone' => self::PHONE, 'purpose' => OtpPurpose::VerifyPhone, ...$attributes]);
    }

    public function test_the_right_code_verifies_the_phone_without_logging_in(): void
    {
        Event::fake([PhoneVerified::class]);
        $user = User::factory()->unverifiedPhone()->create(['phone' => self::PHONE]);
        $otpCode = $this->createCode();

        $response = $this->postJson(route('api.v1.auth.verify-phone'), ['phone' => '01012345678', 'code' => '123456']);

        $response->assertOk()->assertJsonMissingPath('token')->assertJsonMissingPath('data');
        $this->assertNotNull($user->refresh()->phone_verified_at);
        $this->assertNotNull($otpCode->refresh()->used_at);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        Event::assertDispatched(PhoneVerified::class, fn (PhoneVerified $event): bool => $event->user->is($user));
    }

    public function test_a_wrong_code_counts_an_attempt(): void
    {
        User::factory()->unverifiedPhone()->create(['phone' => self::PHONE]);
        $otpCode = $this->createCode();

        $this->postJson(route('api.v1.auth.verify-phone'), ['phone' => self::PHONE, 'code' => '000000'], ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.code.0', 'The code is wrong or has expired. Please check it or request a new one.');

        $this->assertSame(1, $otpCode->refresh()->attempts);
        $this->assertNull(User::query()->sole()->phone_verified_at);
    }

    public function test_an_expired_code_is_rejected(): void
    {
        User::factory()->unverifiedPhone()->create(['phone' => self::PHONE]);
        $this->createCode(['expires_at' => now()->subSecond()]);

        $this->postJson(route('api.v1.auth.verify-phone'), ['phone' => self::PHONE, 'code' => '123456'], ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.code.0', 'The code is wrong or has expired. Please check it or request a new one.');
    }

    public function test_the_code_is_blocked_after_the_max_attempts_even_when_right(): void
    {
        config(['tayari.otp.max_attempts' => 3]);
        User::factory()->unverifiedPhone()->create(['phone' => self::PHONE]);
        $this->createCode(['attempts' => 2]);

        $this->postJson(route('api.v1.auth.verify-phone'), ['phone' => self::PHONE, 'code' => '000000'], ['Accept-Language' => 'en'])
            ->assertJsonPath('errors.code.0', 'The code is wrong or has expired. Please check it or request a new one.');

        $this->postJson(route('api.v1.auth.verify-phone'), ['phone' => self::PHONE, 'code' => '123456'], ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.code.0', 'The code is wrong or has expired. Please check it or request a new one.');

        $this->assertNull(User::query()->sole()->phone_verified_at);
    }

    public function test_after_the_max_attempts_a_registered_phone_and_an_unknown_phone_get_the_same_json(): void
    {
        config(['tayari.otp.max_attempts' => 3]);
        User::factory()->unverifiedPhone()->create(['phone' => self::PHONE]);
        $this->createCode(['attempts' => 3]);

        $registered = $this->postJson(route('api.v1.auth.verify-phone'), ['phone' => self::PHONE, 'code' => '123456'], ['Accept-Language' => 'en']);
        $unknown = $this->postJson(route('api.v1.auth.verify-phone'), ['phone' => '+201112345678', 'code' => '123456'], ['Accept-Language' => 'en']);

        $registered->assertUnprocessable()->assertJsonPath('errors.code.0', 'The code is wrong or has expired. Please check it or request a new one.');
        $this->assertSame($registered->json(), $unknown->json());
        $this->assertNull(User::query()->sole()->phone_verified_at);
    }

    public function test_a_used_code_cannot_be_used_again(): void
    {
        User::factory()->unverifiedPhone()->create(['phone' => self::PHONE]);
        $this->createCode(['used_at' => now()]);

        $this->postJson(route('api.v1.auth.verify-phone'), ['phone' => self::PHONE, 'code' => '123456'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    public function test_a_reset_password_code_does_not_verify_the_phone(): void
    {
        User::factory()->unverifiedPhone()->create(['phone' => self::PHONE]);
        $this->createCode(['purpose' => OtpPurpose::ResetPassword]);

        $this->postJson(route('api.v1.auth.verify-phone'), ['phone' => self::PHONE, 'code' => '123456'])
            ->assertUnprocessable();
    }

    public function test_verify_phone_still_works_when_verification_is_not_required(): void
    {
        config(['tayari.auth.require_phone_verification' => false]);
        $user = User::factory()->unverifiedPhone()->create(['phone' => self::PHONE]);
        $this->createCode();

        $this->postJson(route('api.v1.auth.verify-phone'), ['phone' => self::PHONE, 'code' => '123456'])->assertOk();

        $this->assertNotNull($user->refresh()->phone_verified_at);
    }

    public function test_an_unknown_phone_gets_the_same_error_as_a_wrong_code_for_a_registered_phone(): void
    {
        User::factory()->unverifiedPhone()->create(['phone' => self::PHONE]);
        $this->createCode();

        $registered = $this->postJson(route('api.v1.auth.verify-phone'), ['phone' => self::PHONE, 'code' => '000000'], ['Accept-Language' => 'en']);
        $unknown = $this->postJson(route('api.v1.auth.verify-phone'), ['phone' => '+201112345678', 'code' => '000000'], ['Accept-Language' => 'en']);

        $registered->assertUnprocessable()->assertJsonPath('errors.code.0', 'The code is wrong or has expired. Please check it or request a new one.');
        $unknown->assertUnprocessable();
        $this->assertSame($registered->json(), $unknown->json());
    }

    public function test_an_expired_code_for_a_registered_phone_and_an_unknown_phone_get_the_same_json(): void
    {
        User::factory()->unverifiedPhone()->create(['phone' => self::PHONE]);
        $this->createCode(['expires_at' => now()->subSecond()]);

        $registered = $this->postJson(route('api.v1.auth.verify-phone'), ['phone' => self::PHONE, 'code' => '123456'], ['Accept-Language' => 'en']);
        $unknown = $this->postJson(route('api.v1.auth.verify-phone'), ['phone' => '+201112345678', 'code' => '123456'], ['Accept-Language' => 'en']);

        $registered->assertUnprocessable()->assertJsonPath('errors.code.0', 'The code is wrong or has expired. Please check it or request a new one.');
        $this->assertSame($registered->json(), $unknown->json());
    }
}
