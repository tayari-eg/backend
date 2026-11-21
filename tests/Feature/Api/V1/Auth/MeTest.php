<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_logged_in_user_without_secrets(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson(route('api.v1.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.phone', $user->phone)
            ->assertJsonPath('data.national_id.status', null)
            ->assertJsonPath('data.business_profile', null)
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.remember_token')
            ->assertJsonMissingPath('data.roles');
    }

    public function test_it_includes_the_business_profile(): void
    {
        $profile = BusinessProfile::factory()->create();
        Sanctum::actingAs($profile->user);

        $this->getJson(route('api.v1.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.business_profile.id', $profile->id)
            ->assertJsonPath('data.business_profile.verification_status', 'pending');
    }

    public function test_staff_see_their_roles_and_permissions(): void
    {
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));

        $this->getJson(route('api.v1.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.roles', ['support'])
            ->assertJsonPath('data.permissions', ['business_profiles.view', 'national_ids.view']);
    }

    public function test_guests_get_401(): void
    {
        $this->getJson(route('api.v1.auth.me'))->assertUnauthorized();
    }
}
