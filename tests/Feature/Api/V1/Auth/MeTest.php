<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Domains\Accounts\Enums\StaffPermission;
use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Tayars\Models\TayarProfile;
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
            ->assertJsonPath('data.roles', [])
            ->assertJsonPath('data.permissions', []);
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
            ->assertJsonPath('data.permissions', ['business_profiles.view', 'national_ids.view', 'business_types.view', 'users.view', 'tayars.view', 'vehicle_types.view']);
    }

    public function test_a_super_admin_gets_every_permission(): void
    {
        Sanctum::actingAs($this->createStaffUser(StaffRole::SuperAdmin));

        $this->getJson(route('api.v1.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.roles', ['super_admin'])
            ->assertJsonPath('data.permissions', array_column(StaffPermission::cases(), 'value'));
    }

    public function test_it_returns_the_last_mode_and_the_tayar_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson(route('api.v1.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.last_mode', 'client')
            ->assertJsonPath('data.tayar_profile', null);

        $profile = TayarProfile::factory()->approved()->onlineAt(30.0444, 31.2357)->create(['user_id' => $user->id]);
        $user->forceFill(['last_mode' => 'tayar'])->save();

        $this->getJson(route('api.v1.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.last_mode', 'tayar')
            ->assertJsonPath('data.tayar_profile', ['id' => $profile->id, 'status' => 'approved', 'is_online' => true]);
    }

    public function test_guests_get_401(): void
    {
        $this->getJson(route('api.v1.auth.me'))->assertUnauthorized();
    }
}
