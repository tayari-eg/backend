<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_changes_the_password_and_deletes_the_other_tokens(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('phone');
        $other = $user->createToken('tablet');

        $this->withToken($current->plainTextToken)
            ->putJson(route('api.v1.auth.password.update'), [
                'current_password' => 'password',
                'password' => 'brand-new-pass',
                'password_confirmation' => 'brand-new-pass',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('brand-new-pass', $user->refresh()->password));
        $this->assertModelExists($current->accessToken);
        $this->assertModelMissing($other->accessToken);
    }

    public function test_the_current_password_must_be_right(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('phone')->plainTextToken)
            ->putJson(route('api.v1.auth.password.update'), [
                'current_password' => 'not-my-password',
                'password' => 'brand-new-pass',
                'password_confirmation' => 'brand-new-pass',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_the_new_password_needs_at_least_8_characters(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('phone')->plainTextToken)
            ->putJson(route('api.v1.auth.password.update'), [
                'current_password' => 'password',
                'password' => 'short1',
                'password_confirmation' => 'short1',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }
}
