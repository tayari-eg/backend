<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Domains\Accounts\Actions\SendOtpAction;
use App\Domains\Accounts\Enums\Locale;
use App\Domains\Accounts\Enums\OtpPurpose;
use App\Domains\Accounts\Events\PhoneVerified;
use App\Domains\Accounts\Models\OtpCode;
use App\Exceptions\OtpResendCooldownException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Mockery\MockInterface;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const string PHONE = '+201012345678';

    public function test_forgot_password_sends_a_reset_code_in_the_users_language(): void
    {
        $sms = $this->fakeSmsSender();
        User::factory()->create(['phone' => self::PHONE, 'locale' => Locale::En]);

        $this->postJson(route('api.v1.auth.forgot-password'), ['phone' => '01012345678'], ['Accept-Language' => 'ar'])->assertOk();

        $this->assertCount(1, $sms->messages);
        $this->assertStringContainsString('password reset code', $sms->messages[0]['message']);
        $this->assertDatabaseHas('otp_codes', ['phone' => self::PHONE, 'purpose' => OtpPurpose::ResetPassword->value]);
    }

    public function test_forgot_password_does_not_reveal_unknown_phones(): void
    {
        $sms = $this->fakeSmsSender();
        User::factory()->create(['phone' => '+201112345678']);

        $unknown = $this->postJson(route('api.v1.auth.forgot-password'), ['phone' => self::PHONE]);
        $known = $this->postJson(route('api.v1.auth.forgot-password'), ['phone' => '+201112345678']);

        $unknown->assertOk();
        $this->assertSame($known->json(), $unknown->json());
        $this->assertCount(1, $sms->messages);
    }

    public function test_two_forgot_password_calls_in_a_row_answer_the_same_for_a_known_and_an_unknown_phone(): void
    {
        config(['tayari.otp.resend_cooldown_seconds' => 60]);
        $sms = $this->fakeSmsSender();
        User::factory()->create(['phone' => self::PHONE]);

        $responses = [];
        foreach ([self::PHONE, self::PHONE, '+201112345678', '+201112345678'] as $phone) {
            $response = $this->postJson(route('api.v1.auth.forgot-password'), ['phone' => $phone]);
            $responses[] = [$response->getStatusCode(), $response->json()];
        }

        $this->assertSame([[200, ['message' => __('passwords.sent')]]], array_values(array_unique($responses, SORT_REGULAR)));
        $this->assertCount(1, $sms->messages);
    }

    public function test_forgot_password_uses_the_otp_even_when_phone_verification_is_off(): void
    {
        config(['tayari.auth.require_phone_verification' => false]);
        $sms = $this->fakeSmsSender();
        User::factory()->unverifiedPhone()->create(['phone' => self::PHONE]);

        $this->postJson(route('api.v1.auth.forgot-password'), ['phone' => self::PHONE])->assertOk();

        $this->assertCount(1, $sms->messages);
    }

    public function test_the_reset_code_sets_the_new_password_and_signs_out_every_device(): void
    {
        $user = User::factory()->create(['phone' => self::PHONE]);
        $user->createToken('phone');
        OtpCode::factory()->withCode('654321')->create(['phone' => self::PHONE, 'purpose' => OtpPurpose::ResetPassword]);

        $this->postJson(route('api.v1.auth.reset-password'), [
            'phone' => self::PHONE,
            'code' => '654321',
            'password' => 'new-secret-1',
            'password_confirmation' => 'new-secret-1',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-secret-1', $user->refresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_a_reset_marks_an_unverified_phone_as_verified(): void
    {
        Event::fake([PhoneVerified::class]);
        $user = User::factory()->unverifiedPhone()->create(['phone' => self::PHONE]);
        OtpCode::factory()->withCode('654321')->create(['phone' => self::PHONE, 'purpose' => OtpPurpose::ResetPassword]);

        $this->postJson(route('api.v1.auth.reset-password'), [
            'phone' => self::PHONE,
            'code' => '654321',
            'password' => 'new-secret-1',
            'password_confirmation' => 'new-secret-1',
        ])->assertOk();

        $this->assertNotNull($user->refresh()->phone_verified_at);
        Event::assertDispatchedTimes(PhoneVerified::class, 1);
        Event::assertDispatched(PhoneVerified::class, fn (PhoneVerified $event): bool => $event->user->is($user));
    }

    public function test_a_reset_for_an_already_verified_phone_does_not_dispatch_phone_verified(): void
    {
        Event::fake([PhoneVerified::class]);
        User::factory()->create(['phone' => self::PHONE]);
        OtpCode::factory()->withCode('654321')->create(['phone' => self::PHONE, 'purpose' => OtpPurpose::ResetPassword]);

        $this->postJson(route('api.v1.auth.reset-password'), [
            'phone' => self::PHONE,
            'code' => '654321',
            'password' => 'new-secret-1',
            'password_confirmation' => 'new-secret-1',
        ])->assertOk();

        Event::assertNotDispatched(PhoneVerified::class);
    }

    public function test_a_wrong_code_keeps_the_old_password(): void
    {
        $user = User::factory()->create(['phone' => self::PHONE]);
        OtpCode::factory()->withCode('654321')->create(['phone' => self::PHONE, 'purpose' => OtpPurpose::ResetPassword]);

        $this->postJson(route('api.v1.auth.reset-password'), [
            'phone' => self::PHONE,
            'code' => '111111',
            'password' => 'new-secret-1',
            'password_confirmation' => 'new-secret-1',
        ])->assertUnprocessable()->assertJsonValidationErrors(['code']);

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_a_verify_phone_code_cannot_reset_the_password(): void
    {
        User::factory()->create(['phone' => self::PHONE]);
        OtpCode::factory()->withCode('654321')->create(['phone' => self::PHONE, 'purpose' => OtpPurpose::VerifyPhone]);

        $this->postJson(route('api.v1.auth.reset-password'), [
            'phone' => self::PHONE,
            'code' => '654321',
            'password' => 'new-secret-1',
            'password_confirmation' => 'new-secret-1',
        ])->assertUnprocessable();
    }

    public function test_the_new_password_needs_at_least_8_characters(): void
    {
        User::factory()->create(['phone' => self::PHONE]);

        $this->postJson(route('api.v1.auth.reset-password'), [
            'phone' => self::PHONE,
            'code' => '654321',
            'password' => 'short1',
            'password_confirmation' => 'short1',
        ])->assertUnprocessable()->assertJsonValidationErrors(['password']);
    }

    public function test_an_unknown_phone_gets_the_same_reset_error_as_a_wrong_code_for_a_registered_phone(): void
    {
        User::factory()->create(['phone' => self::PHONE]);
        OtpCode::factory()->withCode('654321')->create(['phone' => self::PHONE, 'purpose' => OtpPurpose::ResetPassword]);
        $payload = ['code' => '111111', 'password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1'];

        $registered = $this->postJson(route('api.v1.auth.reset-password'), ['phone' => self::PHONE, ...$payload], ['Accept-Language' => 'en']);
        $unknown = $this->postJson(route('api.v1.auth.reset-password'), ['phone' => '+201112345678', ...$payload], ['Accept-Language' => 'en']);

        $registered->assertUnprocessable()->assertJsonPath('errors.code.0', 'The code is wrong or has expired. Please check it or request a new one.');
        $this->assertSame($registered->json(), $unknown->json());
    }

    public function test_an_expired_code_for_a_registered_phone_and_an_unknown_phone_get_the_same_reset_json(): void
    {
        User::factory()->create(['phone' => self::PHONE]);
        OtpCode::factory()->withCode('654321')->expired()->create(['phone' => self::PHONE, 'purpose' => OtpPurpose::ResetPassword]);
        $payload = ['code' => '654321', 'password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1'];

        $registered = $this->postJson(route('api.v1.auth.reset-password'), ['phone' => self::PHONE, ...$payload], ['Accept-Language' => 'en']);
        $unknown = $this->postJson(route('api.v1.auth.reset-password'), ['phone' => '+201112345678', ...$payload], ['Accept-Language' => 'en']);

        $registered->assertUnprocessable()->assertJsonPath('errors.code.0', 'The code is wrong or has expired. Please check it or request a new one.');
        $this->assertSame($registered->json(), $unknown->json());
    }

    public function test_after_the_max_attempts_the_right_code_fails_like_an_unknown_phone(): void
    {
        config(['tayari.otp.max_attempts' => 3]);
        $user = User::factory()->create(['phone' => self::PHONE]);
        OtpCode::factory()->withCode('654321')->create(['phone' => self::PHONE, 'purpose' => OtpPurpose::ResetPassword, 'attempts' => 2]);
        $payload = ['password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1'];

        $this->postJson(route('api.v1.auth.reset-password'), ['phone' => self::PHONE, 'code' => '111111', ...$payload])->assertUnprocessable();

        $registered = $this->postJson(route('api.v1.auth.reset-password'), ['phone' => self::PHONE, 'code' => '654321', ...$payload], ['Accept-Language' => 'en']);
        $unknown = $this->postJson(route('api.v1.auth.reset-password'), ['phone' => '+201112345678', 'code' => '654321', ...$payload], ['Accept-Language' => 'en']);

        $registered->assertUnprocessable()->assertJsonPath('errors.code.0', 'The code is wrong or has expired. Please check it or request a new one.');
        $this->assertSame($registered->json(), $unknown->json());
        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_a_cooldown_race_on_forgot_password_stays_quiet(): void
    {
        User::factory()->create(['phone' => self::PHONE]);
        $this->mock(SendOtpAction::class, function (MockInterface $mock): void {
            $mock->shouldReceive('secondsUntilResendAllowed')->andReturn(0);
            $mock->shouldReceive('handle')->andThrow(new OtpResendCooldownException(30));
        });

        $this->postJson(route('api.v1.auth.forgot-password'), ['phone' => self::PHONE])
            ->assertOk()
            ->assertJsonPath('message', __('passwords.sent'));
    }
}
