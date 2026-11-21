<?php

namespace Tests\Feature\Api\V1\Shared;

use App\Domains\Accounts\Enums\Platform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiErrorFormatTest extends TestCase
{
    public function test_a_missing_route_returns_the_standard_error_shape(): void
    {
        $response = $this->getJson('/api/v1/does-not-exist');

        $response->assertNotFound()
            ->assertExactJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors', []);
    }

    public function test_an_unknown_enum_route_value_returns_404_without_the_enum_class_name(): void
    {
        Route::middleware('api')->get('api/v1/_test/platforms/{platform}', fn (Platform $platform): array => ['platform' => $platform->value]);

        $response = $this->getJson('/api/v1/_test/platforms/symbian');

        $response->assertNotFound()
            ->assertExactJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors', []);
        $this->assertStringNotContainsString(Platform::class, $response->json('message'));
        $this->assertStringNotContainsString('Platform', $response->json('message'));
    }

    public function test_a_validation_error_returns_the_standard_error_shape(): void
    {
        // No v1 endpoint validates input yet, so a test-only route triggers the validation error.
        Route::middleware('api')->post('api/v1/_test/validation', function (Request $request): array {
            return $request->validate(['phone' => ['required', 'string']]);
        });

        $response = $this->postJson('/api/v1/_test/validation', []);

        $response->assertUnprocessable()
            ->assertExactJsonStructure(['message', 'errors' => ['phone']])
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_a_server_error_hides_the_stack_trace_when_debug_is_off(): void
    {
        config(['app.debug' => false]);
        Route::middleware('api')->get('api/v1/_test/server-error', function (): never {
            throw new \RuntimeException('Secret internal detail');
        });

        $response = $this->getJson('/api/v1/_test/server-error');

        $response->assertInternalServerError()
            ->assertExactJson([
                'message' => 'Server Error',
                'errors' => [],
            ]);
    }
}
