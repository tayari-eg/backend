<?php

namespace Tests\Feature\Api\V1\Public;

use Illuminate\Support\Carbon;
use Tests\TestCase;

class AppConfigTest extends TestCase
{
    private const array VERSION_ENV_KEYS = [
        'APP_MIN_SUPPORTED_VERSION_ANDROID',
        'APP_MIN_SUPPORTED_VERSION_IOS',
        'APP_LATEST_VERSION_ANDROID',
        'APP_LATEST_VERSION_IOS',
    ];

    public function test_it_returns_min_supported_and_latest_versions_and_server_time_in_utc(): void
    {
        config([
            'tayari.min_supported_versions.android' => '1.4.0',
            'tayari.min_supported_versions.ios' => '1.5.2',
            'tayari.latest_versions.android' => '1.8.0',
            'tayari.latest_versions.ios' => '1.9.1',
            'tayari.auth.require_phone_verification' => true,
        ]);
        Carbon::setTestNow('2026-11-20 10:30:00');

        $response = $this->getJson(route('api.v1.public.app-config'));

        $response->assertOk()
            ->assertExactJson([
                'data' => [
                    'min_supported_version' => [
                        'android' => '1.4.0',
                        'ios' => '1.5.2',
                    ],
                    'latest_version' => [
                        'android' => '1.8.0',
                        'ios' => '1.9.1',
                    ],
                    'phone_verification_required' => true,
                    'server_time' => '2026-11-20T10:30:00Z',
                ],
            ]);
    }

    public function test_it_is_public(): void
    {
        $this->getJson('/api/v1/app-config')->assertOk();
    }

    public function test_it_reports_when_phone_verification_is_not_required(): void
    {
        config(['tayari.auth.require_phone_verification' => false]);

        $this->getJson(route('api.v1.public.app-config'))
            ->assertOk()
            ->assertJsonPath('data.phone_verification_required', false);
    }

    public function test_blank_version_env_values_are_returned_as_null(): void
    {
        $this->setBlankVersionEnvValues();
        config(['tayari' => require config_path('tayari.php')]);

        $response = $this->getJson(route('api.v1.public.app-config'));

        $response->assertOk()
            ->assertJsonPath('data.min_supported_version', ['android' => null, 'ios' => null])
            ->assertJsonPath('data.latest_version', ['android' => null, 'ios' => null]);
    }

    protected function tearDown(): void
    {
        foreach (self::VERSION_ENV_KEYS as $key) {
            unset($_ENV[$key], $_SERVER[$key]);
        }

        parent::tearDown();
    }

    private function setBlankVersionEnvValues(): void
    {
        foreach (self::VERSION_ENV_KEYS as $key) {
            $_ENV[$key] = '';
            $_SERVER[$key] = '';
        }
    }
}
