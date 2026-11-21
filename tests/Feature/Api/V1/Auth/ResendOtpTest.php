<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Domains\Accounts\Actions\SendOtpAction;
use App\Domains\Accounts\Enums\OtpPurpose;
use App\Domains\Accounts\Models\OtpCode;
use App\Exceptions\OtpResendCooldownException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class ResendOtpTest extends TestCase
{
    use RefreshDatabase;

    private const string PHONE = '+201012345678';

    public function test_it_sends_a_new_code_and_replaces_the_open_one(): void
    {
        $sms = $this->fakeSmsSender();
        User::factory()->unverifiedPhone()->create(['phone' => self::PHONE]);
        $oldCode = OtpCode::factory()->create(['phone' => self::PHONE, 'created_at' => now()->subMinutes(2)]);

        $this->postJson(route('api.v1.auth.otp.resend'), ['phone' => self::PHONE, 'purpose' => 'verify_phone'])->assertOk();

        $this->assertCount(1, $sms->messages);
        $this->assertModelMissing($oldCode);
        $this->assertSame(1, OtpCode::query()->where('phone', self::PHONE)->count());
    }

    public function test_within_the_cooldown_it_sends_nothing_and_still_answers_200(): void
    {
        config(['tayari.otp.resend_cooldown_seconds' => 60]);
        $sms = $this->fakeSmsSender();
        User::factory()->unverifiedPhone()->create(['phone' => self::PHONE]);
        $earlierCode = OtpCode::factory()->create(['phone' => self::PHONE, 'created_at' => now()->subSeconds(20)]);

        $this->postJson(route('api.v1.auth.otp.resend'), ['phone' => self::PHONE, 'purpose' => 'verify_phone'])
            ->assertOk()
            ->assertJsonMissingPath('resend_available_in');

        $this->assertSame([], $sms->messages);
        $this->assertModelExists($earlierCode);
    }

    public function test_two_calls_in_a_row_answer_the_same_for_a_known_and_an_unknown_phone(): void
    {
        config(['tayari.otp.resend_cooldown_seconds' => 60]);
        $sms = $this->fakeSmsSender();
        User::factory()->unverifiedPhone()->create(['phone' => self::PHONE]);

        $responses = [];
        foreach ([self::PHONE, self::PHONE, '+201112345678', '+201112345678'] as $phone) {
            $response = $this->postJson(route('api.v1.auth.otp.resend'), ['phone' => $phone, 'purpose' => 'verify_phone']);
            $responses[] = [$response->getStatusCode(), $response->json()];
        }

        $this->assertSame([[200, ['message' => __('otp.sent')]]], array_values(array_unique($responses, SORT_REGULAR)));
        $this->assertCount(1, $sms->messages);
    }

    public function test_it_answers_the_same_for_unknown_and_already_verified_phones(): void
    {
        $sms = $this->fakeSmsSender();
        User::factory()->create(['phone' => '+201112345678']);

        $unknown = $this->postJson(route('api.v1.auth.otp.resend'), ['phone' => self::PHONE, 'purpose' => 'verify_phone']);
        $verified = $this->postJson(route('api.v1.auth.otp.resend'), ['phone' => '+201112345678', 'purpose' => 'verify_phone']);

        $unknown->assertOk();
        $verified->assertOk();
        $this->assertSame($unknown->json('message'), $verified->json('message'));
        $this->assertSame([], $sms->messages);
    }

    public function test_it_resends_a_reset_password_code(): void
    {
        $sms = $this->fakeSmsSender();
        User::factory()->create(['phone' => self::PHONE]);

        $this->postJson(route('api.v1.auth.otp.resend'), ['phone' => self::PHONE, 'purpose' => 'reset_password'])->assertOk();

        $this->assertCount(1, $sms->messages);
        $this->assertDatabaseHas('otp_codes', ['phone' => self::PHONE, 'purpose' => OtpPurpose::ResetPassword->value]);
    }

    public function test_it_is_rate_limited_per_phone(): void
    {
        config(['tayari.otp.resend_cooldown_seconds' => 0, 'tayari.otp.rate_limits.per_phone' => 2, 'tayari.otp.rate_limits.per_ip' => 100]);
        $this->fakeSmsSender();
        User::factory()->unverifiedPhone()->create(['phone' => self::PHONE]);

        foreach (['01012345678', '+201012345678'] as $phone) {
            $this->postJson(route('api.v1.auth.otp.resend'), ['phone' => $phone, 'purpose' => 'verify_phone'])->assertOk();
        }

        $this->postJson(route('api.v1.auth.otp.resend'), ['phone' => '0020 10 1234 5678', 'purpose' => 'verify_phone'])->assertTooManyRequests();
    }

    public function test_it_is_rate_limited_per_ip(): void
    {
        config(['tayari.otp.rate_limits.per_phone' => 100, 'tayari.otp.rate_limits.per_ip' => 2]);
        $this->fakeSmsSender();

        $this->postJson(route('api.v1.auth.otp.resend'), ['phone' => '01012345678', 'purpose' => 'verify_phone'])->assertOk();
        $this->postJson(route('api.v1.auth.otp.resend'), ['phone' => '01112345678', 'purpose' => 'verify_phone'])->assertOk();
        $this->postJson(route('api.v1.auth.otp.resend'), ['phone' => '01212345678', 'purpose' => 'verify_phone'])->assertTooManyRequests();
    }

    public function test_a_cooldown_race_stays_quiet(): void
    {
        User::factory()->unverifiedPhone()->create(['phone' => self::PHONE]);
        $this->mock(SendOtpAction::class, function (MockInterface $mock): void {
            $mock->shouldReceive('secondsUntilResendAllowed')->andReturn(0);
            $mock->shouldReceive('handle')->andThrow(new OtpResendCooldownException(30));
        });

        $this->postJson(route('api.v1.auth.otp.resend'), ['phone' => self::PHONE, 'purpose' => 'verify_phone'])
            ->assertOk()
            ->assertJsonPath('message', __('otp.sent'));
    }
}
