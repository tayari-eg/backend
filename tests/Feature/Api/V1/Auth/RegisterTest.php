<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Domains\Accounts\Actions\RegisterUserAction;
use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Accounts\Enums\AccountType;
use App\Domains\Accounts\Enums\Locale;
use App\Domains\Accounts\Enums\OtpPurpose;
use App\Domains\Accounts\Enums\RegisterAs;
use App\Domains\Accounts\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Mona Adel',
            'phone' => '010 1234 5678',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'register_as' => 'business',
            ...$overrides,
        ];
    }

    public function test_it_registers_and_sends_a_hashed_otp_when_verification_is_required(): void
    {
        config(['tayari.auth.require_phone_verification' => true, 'tayari.otp.resend_cooldown_seconds' => 60]);
        $sms = $this->fakeSmsSender();

        $response = $this->postJson(route('api.v1.auth.register'), $this->payload(), ['Accept-Language' => 'en']);

        $response->assertCreated()
            ->assertJsonPath('phone_verification_required', true)
            ->assertJsonPath('resend_available_in', 60)
            ->assertJsonPath('data.phone', '+201012345678')
            ->assertJsonPath('data.account_type', 'business')
            ->assertJsonPath('data.register_as', 'business')
            ->assertJsonPath('data.next_step', 'complete_business_profile')
            ->assertJsonPath('data.locale', 'en')
            ->assertJsonPath('data.is_phone_verified', false)
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('token');

        $user = User::query()->where('phone', '+201012345678')->sole();
        $this->assertTrue(Hash::check('secret-pass', $user->password));
        $this->assertSame(AccountType::Business, $user->account_type);
        $this->assertSame(RegisterAs::Business, $user->register_as);
        $this->assertSame(Locale::En, $user->locale);
        $this->assertNull($user->phone_verified_at);

        $this->assertCount(1, $sms->messages);
        $code = $sms->lastCode();
        $otpCode = OtpCode::query()->where('phone', '+201012345678')->where('purpose', OtpPurpose::VerifyPhone)->sole();
        $this->assertNotSame($code, $otpCode->code_hash);
        $this->assertTrue(Hash::check($code, $otpCode->code_hash));
        $this->assertStringNotContainsString($code, $response->getContent());
    }

    public function test_the_locale_defaults_to_arabic(): void
    {
        $this->fakeSmsSender();

        // Test requests carry Symfony's default "en-us" header, so an empty one stands for "no header".
        $this->postJson(route('api.v1.auth.register'), $this->payload(), ['Accept-Language' => ''])->assertCreated();

        $this->assertSame(Locale::Ar, User::query()->sole()->locale);
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function existingPhones(): array
    {
        return [
            'verified phone' => [true],
            'unverified phone' => [false],
        ];
    }

    #[DataProvider('existingPhones')]
    public function test_registering_an_existing_phone_is_rejected_and_changes_nothing(bool $isVerified): void
    {
        config(['tayari.auth.require_phone_verification' => true]);
        $sms = $this->fakeSmsSender();
        $factory = User::factory();
        $existing = ($isVerified ? $factory : $factory->unverifiedPhone())->create([
            'phone' => '+201012345678',
            'name' => 'Old Name',
            'account_type' => AccountType::Individual,
        ]);

        $this->postJson(route('api.v1.auth.register'), $this->payload(), ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone'])
            ->assertJsonPath('errors.phone.0', 'This phone number already has an account. Please log in, or use forgot password.');

        $user = User::query()->sole();
        $this->assertTrue($user->is($existing));
        $this->assertSame('Old Name', $user->name);
        $this->assertSame(AccountType::Individual, $user->account_type);
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertSame($isVerified, $user->hasVerifiedPhone());
        $this->assertSame([], $sms->messages);
        $this->assertDatabaseCount('otp_codes', 0);
    }

    public function test_a_concurrent_register_for_the_same_phone_is_a_validation_error_not_a_500(): void
    {
        $this->fakeSmsSender();
        app()->setLocale('en');
        // Another request inserted the phone after this one passed validation.
        $winner = User::factory()->unverifiedPhone()->create(['phone' => '+201012345678', 'name' => 'First Request']);

        try {
            app(RegisterUserAction::class)->handle('Second Request', PhoneNumber::from('01012345678'), 'secret-pass', RegisterAs::Business, Locale::En);
            $this->fail('A ValidationException was expected.');
        } catch (ValidationException $exception) {
            $this->assertSame(['phone' => ['This phone number already has an account. Please log in, or use forgot password.']], $exception->errors());
        }

        $this->assertTrue(User::query()->sole()->is($winner));
        $this->assertSame('First Request', $winner->refresh()->name);
    }

    public function test_the_taken_phone_message_is_translated_to_arabic(): void
    {
        User::factory()->create(['phone' => '+201012345678']);

        $this->postJson(route('api.v1.auth.register'), $this->payload(), ['Accept-Language' => 'ar'])
            ->assertJsonPath('errors.phone.0', 'رقم الموبايل ده عليه حساب بالفعل. من فضلك سجّل الدخول، أو استخدم نسيت كلمة المرور.');
    }

    public function test_without_required_verification_it_registers_without_an_otp(): void
    {
        config(['tayari.auth.require_phone_verification' => false]);
        $sms = $this->fakeSmsSender();

        $this->postJson(route('api.v1.auth.register'), $this->payload())
            ->assertCreated()
            ->assertJsonPath('phone_verification_required', false)
            ->assertJsonPath('resend_available_in', null)
            ->assertJsonPath('data.is_phone_verified', false);

        $this->assertNull(User::query()->sole()->phone_verified_at);
        $this->assertSame([], $sms->messages);
        $this->assertDatabaseCount('otp_codes', 0);
    }

    public function test_without_required_verification_an_existing_unverified_phone_is_taken(): void
    {
        config(['tayari.auth.require_phone_verification' => false]);
        $this->fakeSmsSender();
        User::factory()->unverifiedPhone()->create(['phone' => '+201012345678']);

        $this->postJson(route('api.v1.auth.register'), $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_the_password_needs_at_least_8_characters_and_a_confirmation(): void
    {
        $this->postJson(route('api.v1.auth.register'), $this->payload(['password' => 'short12', 'password_confirmation' => 'short12']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $this->postJson(route('api.v1.auth.register'), $this->payload(['password_confirmation' => 'different-pass']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_it_rejects_an_invalid_phone_and_register_as(): void
    {
        $this->postJson(route('api.v1.auth.register'), $this->payload(['phone' => '0223456789', 'register_as' => 'driver']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone', 'register_as']);
    }

    public function test_register_as_is_required_and_individual_is_not_accepted_yet(): void
    {
        $withoutChoice = $this->payload();
        unset($withoutChoice['register_as']);

        $this->postJson(route('api.v1.auth.register'), $withoutChoice)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['register_as']);

        $this->postJson(route('api.v1.auth.register'), $this->payload(['register_as' => 'individual']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['register_as']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registering_as_a_tayar_keeps_the_individual_account_type_and_points_to_the_tayar_application(): void
    {
        $this->fakeSmsSender();

        $this->postJson(route('api.v1.auth.register'), $this->payload(['register_as' => 'tayar']))
            ->assertCreated()
            ->assertJsonPath('data.register_as', 'tayar')
            ->assertJsonPath('data.account_type', 'individual')
            ->assertJsonPath('data.next_step', 'complete_tayar_application');

        $user = User::query()->sole();
        $this->assertSame(RegisterAs::Tayar, $user->register_as);
        $this->assertSame(AccountType::Individual, $user->account_type);
    }

    public function test_an_account_type_sent_by_an_older_client_is_ignored(): void
    {
        $this->fakeSmsSender();

        $this->postJson(route('api.v1.auth.register'), $this->payload(['register_as' => 'tayar', 'account_type' => 'business']))
            ->assertCreated()
            ->assertJsonPath('data.account_type', 'individual');
    }

    public function test_passwords_longer_than_64_characters_are_rejected_on_register_and_login(): void
    {
        $longPassword = str_repeat('a', 65);

        $this->postJson(route('api.v1.auth.register'), $this->payload(['password' => $longPassword, 'password_confirmation' => $longPassword]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $this->postJson(route('api.v1.auth.token'), ['phone' => '01012345678', 'password' => $longPassword, 'device_name' => 'Pixel'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }
}
