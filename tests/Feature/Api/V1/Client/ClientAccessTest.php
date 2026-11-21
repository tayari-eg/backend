<?php

namespace Tests\Feature\Api\V1\Client;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_get_401(): void
    {
        $this->getJson(route('api.v1.client.business-profile.show'))->assertUnauthorized();
    }

    public function test_staff_cannot_use_client_routes(): void
    {
        Sanctum::actingAs($this->createStaffUser(StaffRole::SuperAdmin));

        $this->getJson(route('api.v1.client.business-profile.show'), ['Accept-Language' => 'en'])
            ->assertForbidden()
            ->assertJsonPath('message', 'This section is only available to customer and business accounts.');
    }

    public function test_blocked_users_cannot_use_client_routes(): void
    {
        Sanctum::actingAs(User::factory()->blocked()->create());

        $this->getJson(route('api.v1.client.business-profile.show'))->assertForbidden();
    }

    public function test_an_unverified_phone_is_blocked_when_verification_is_required(): void
    {
        config(['tayari.auth.require_phone_verification' => true]);
        Sanctum::actingAs(User::factory()->unverifiedPhone()->business()->create());

        $this->getJson(route('api.v1.client.business-profile.show'), ['Accept-Language' => 'en'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Please verify your phone number first.');
    }

    public function test_an_unverified_phone_is_allowed_when_verification_is_not_required(): void
    {
        config(['tayari.auth.require_phone_verification' => false]);
        $profile = BusinessProfile::factory()->for(User::factory()->unverifiedPhone()->business())->create();
        Sanctum::actingAs($profile->user);

        $this->getJson(route('api.v1.client.business-profile.show'))->assertOk();
        $this->assertNull($profile->user->refresh()->phone_verified_at);
    }

    public function test_switching_verification_on_later_blocks_unverified_users_until_they_verify(): void
    {
        config(['tayari.auth.require_phone_verification' => false]);
        $profile = BusinessProfile::factory()->for(User::factory()->unverifiedPhone()->business())->create();
        Sanctum::actingAs($profile->user);
        $this->getJson(route('api.v1.client.business-profile.show'))->assertOk();

        config(['tayari.auth.require_phone_verification' => true]);

        $this->getJson(route('api.v1.client.business-profile.show'))->assertForbidden();
    }

    public function test_a_client_cannot_see_another_clients_profile(): void
    {
        BusinessProfile::factory()->create();
        Sanctum::actingAs(User::factory()->business()->create());

        $this->getJson(route('api.v1.client.business-profile.show'))->assertNotFound();
    }
}
