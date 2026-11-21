<?php

namespace Tests\Feature\Api\V1\Shared;

use App\Http\Middleware\EnsureAppVersionIsSupported;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureAppVersionIsSupportedTest extends TestCase
{
    private const string PROTECTED_URI = '/api/v1/_test/version-check';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tayari.min_supported_versions.android' => '2.0.0',
            'tayari.min_supported_versions.ios' => '2.0.0',
        ]);

        // No other v1 route exists yet, so a test-only route stands in for one behind the version check.
        Route::middleware(['api', EnsureAppVersionIsSupported::class])
            ->get(self::PROTECTED_URI, fn (): array => ['ok' => true]);
    }

    public function test_an_old_android_version_is_blocked_with_426(): void
    {
        $response = $this->getJson(self::PROTECTED_URI, [
            'X-Platform' => 'android',
            'X-App-Version' => '1.9.9',
        ]);

        $response->assertStatus(426)
            ->assertExactJson([
                'message' => 'This version of the app is no longer supported. Please update the app to continue.',
                'errors' => [],
            ]);
    }

    public function test_an_old_android_version_can_still_read_app_config(): void
    {
        $this->getJson(route('api.v1.public.app-config'), [
            'X-Platform' => 'android',
            'X-App-Version' => '1.9.9',
        ])->assertOk()
            ->assertJsonPath('data.min_supported_version.android', '2.0.0');
    }

    public function test_app_config_inherits_the_version_check_from_the_v1_group_and_excludes_it(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.public.app-config');

        $this->assertNotNull($route);
        $this->assertContains(EnsureAppVersionIsSupported::class, $route->middleware());
        $this->assertContains(EnsureAppVersionIsSupported::class, $route->excludedMiddleware());
    }

    public function test_a_blocked_request_logs_the_minimum_version(): void
    {
        Log::spy();

        $this->getJson(self::PROTECTED_URI, [
            'X-Platform' => 'android',
            'X-App-Version' => '1.9.9',
        ])->assertStatus(426);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Blocked unsupported app version', ['minimum_version' => '2.0.0']);
    }

    public function test_an_allowed_request_does_not_log_a_blocked_version(): void
    {
        Log::spy();

        $this->getJson(self::PROTECTED_URI, [
            'X-Platform' => 'android',
            'X-App-Version' => '2.0.0',
        ])->assertOk();

        Log::shouldNotHaveReceived('info');
    }

    public function test_an_old_ios_version_is_blocked_with_426(): void
    {
        $this->getJson(self::PROTECTED_URI, [
            'X-Platform' => 'ios',
            'X-App-Version' => '1.0.0',
        ])->assertStatus(426);
    }

    public function test_the_current_android_version_is_allowed(): void
    {
        $this->getJson(self::PROTECTED_URI, [
            'X-Platform' => 'android',
            'X-App-Version' => '2.0.0+15',
        ])->assertOk();
    }

    public function test_a_newer_android_version_is_allowed(): void
    {
        $this->getJson(self::PROTECTED_URI, [
            'X-Platform' => 'android',
            'X-App-Version' => '2.10.0',
        ])->assertOk();
    }

    public function test_web_is_never_blocked(): void
    {
        $this->getJson(self::PROTECTED_URI, [
            'X-Platform' => 'web',
            'X-App-Version' => '0.0.1',
        ])->assertOk();
    }

    public function test_nothing_is_blocked_when_no_minimum_is_configured(): void
    {
        config(['tayari.min_supported_versions.android' => null]);

        $this->getJson(self::PROTECTED_URI, [
            'X-Platform' => 'android',
            'X-App-Version' => '0.0.1',
        ])->assertOk();
    }
}
