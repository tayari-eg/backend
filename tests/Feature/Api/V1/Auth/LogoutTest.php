<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_mobile_path_deletes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('phone');
        $other = $user->createToken('tablet');

        $this->withToken($current->plainTextToken)->postJson(route('api.v1.auth.logout'))->assertOk();

        $this->assertModelMissing($current->accessToken);
        $this->assertModelExists($other->accessToken);
    }

    public function test_the_web_path_ends_the_session(): void
    {
        config(['sanctum.stateful' => ['localhost:3000']]);
        $user = User::factory()->create();
        $token = $user->createToken('phone');

        $this->actingAs($user, 'web')
            ->postJson(route('api.v1.auth.logout'), [], ['Origin' => 'http://localhost:3000', 'Referer' => 'http://localhost:3000/'])
            ->assertOk();

        $this->assertGuest('web');
        $this->assertModelExists($token->accessToken);
    }

    public function test_guests_get_401(): void
    {
        $this->postJson(route('api.v1.auth.logout'))->assertUnauthorized();
    }
}
