<?php

namespace Tests\Feature\Api\V1\Shared;

use App\Http\Middleware\EnsureAppVersionIsSupported;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function upgradeMessages(): array
    {
        return [
            'arabic' => ['ar', 'هذا الإصدار من التطبيق لم يعد مدعومًا. من فضلك حدّث التطبيق للمتابعة.'],
            'english' => ['en', 'This version of the app is no longer supported. Please update the app to continue.'],
            'english with region and weights' => ['en-GB,en;q=0.9,ar;q=0.8', 'This version of the app is no longer supported. Please update the app to continue.'],
            'unsupported language falls back to arabic' => ['fr-FR', 'هذا الإصدار من التطبيق لم يعد مدعومًا. من فضلك حدّث التطبيق للمتابعة.'],
            'no header falls back to arabic' => ['', 'هذا الإصدار من التطبيق لم يعد مدعومًا. من فضلك حدّث التطبيق للمتابعة.'],
        ];
    }

    #[DataProvider('upgradeMessages')]
    public function test_the_426_message_follows_accept_language(string $acceptLanguage, string $message): void
    {
        config(['tayari.min_supported_versions.android' => '2.0.0']);
        Route::middleware(['api', EnsureAppVersionIsSupported::class])->get('api/v1/_test/localized', fn (): array => []);

        $this->getJson('/api/v1/_test/localized', ['X-Platform' => 'android', 'X-App-Version' => '1.0.0', 'Accept-Language' => $acceptLanguage])
            ->assertStatus(426)
            ->assertJsonPath('message', $message);
    }

    public function test_the_401_message_is_translated(): void
    {
        $this->getJson(route('api.v1.auth.me'), ['Accept-Language' => 'ar'])->assertUnauthorized()->assertJsonPath('message', 'غير مسجل الدخول.');
        $this->getJson(route('api.v1.auth.me'), ['Accept-Language' => 'en'])->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_the_500_message_is_translated(): void
    {
        config(['app.debug' => false]);
        Route::middleware('api')->get('api/v1/_test/boom', fn (): never => throw new \RuntimeException('Secret internal detail'));

        $this->getJson('/api/v1/_test/boom', ['Accept-Language' => 'ar'])->assertInternalServerError()->assertExactJson(['message' => 'خطأ في الخادم', 'errors' => []]);
        $this->getJson('/api/v1/_test/boom', ['Accept-Language' => 'en'])->assertInternalServerError()->assertExactJson(['message' => 'Server Error', 'errors' => []]);
    }

    public function test_a_missing_route_is_translated(): void
    {
        $this->getJson('/api/v1/does-not-exist', ['Accept-Language' => 'ar'])->assertNotFound()->assertJsonPath('message', 'غير موجود.');
    }

    public function test_validation_messages_and_attribute_names_are_translated(): void
    {
        $arabic = $this->postJson(route('api.v1.auth.token'), [], ['Accept-Language' => 'ar']);
        $english = $this->postJson(route('api.v1.auth.token'), [], ['Accept-Language' => 'en']);

        $arabic->assertUnprocessable()
            ->assertJsonPath('errors.phone.0', 'حقل رقم الموبايل مطلوب.')
            ->assertJsonPath('message', 'حقل رقم الموبايل مطلوب. (و2 أخطاء أخرى)');
        $english->assertJsonPath('errors.phone.0', 'The phone number field is required.');
    }

    public function test_the_egyptian_phone_rule_is_translated(): void
    {
        $this->postJson(route('api.v1.auth.token'), ['phone' => '123', 'password' => 'x', 'device_name' => 'x'], ['Accept-Language' => 'ar'])
            ->assertJsonPath('errors.phone.0', 'يجب أن يكون حقل رقم الموبايل رقم موبايل مصري صحيحًا.');
    }
}
