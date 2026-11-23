<?php

namespace Tests\Feature\Api\V1\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MalformedPhoneInputTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public static function endpoints(): array
    {
        return [
            'register' => ['api.v1.auth.register', ['name' => 'Mona', 'password' => 'secret-pass', 'password_confirmation' => 'secret-pass', 'register_as' => 'business']],
            'forgot password' => ['api.v1.auth.forgot-password', []],
            'mobile token' => ['api.v1.auth.token', ['password' => 'secret-pass', 'device_name' => 'Pixel']],
        ];
    }

    #[DataProvider('endpoints')]
    public function test_an_array_phone_is_a_validation_error_not_a_server_error(string $routeName, array $payload): void
    {
        $this->postJson(route($routeName), [...$payload, 'phone' => ['x']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }
}
