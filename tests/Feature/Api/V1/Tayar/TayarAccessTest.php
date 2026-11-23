<?php

namespace Tests\Feature\Api\V1\Tayar;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Enums\UserStatus;
use App\Domains\Tayars\Models\TayarProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TayarAccessTest extends TestCase
{
    use RefreshDatabase;

    private function goOnline(): TestResponse
    {
        return $this->postJson(route('api.v1.tayar.online'), $this->zoneOnlinePayload(), ['Accept-Language' => 'en']);
    }

    public function test_guests_get_401(): void
    {
        $this->goOnline()->assertUnauthorized();
    }

    public function test_users_without_an_approved_application_are_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->goOnline()
            ->assertForbidden()
            ->assertJsonPath('message', 'Tayar mode is only available after your tayar application is approved.');

        Sanctum::actingAs(TayarProfile::factory()->create()->user);
        $this->goOnline()->assertForbidden();

        Sanctum::actingAs(TayarProfile::factory()->rejected()->create()->user);
        $this->goOnline()->assertForbidden();
    }

    public function test_blocked_tayars_are_forbidden(): void
    {
        $profile = TayarProfile::factory()->approved()->create();
        $profile->user->forceFill(['status' => UserStatus::Blocked])->save();
        Sanctum::actingAs($profile->user);

        $this->goOnline()
            ->assertForbidden()
            ->assertJsonPath('message', 'Your account has been blocked. Please contact support.');
    }

    public function test_staff_are_forbidden(): void
    {
        Sanctum::actingAs($this->createStaffUser(StaffRole::Admin));

        $this->goOnline()->assertForbidden();
    }

    public function test_an_approved_tayar_gets_in(): void
    {
        Sanctum::actingAs(TayarProfile::factory()->approved()->create()->user);

        $this->goOnline()->assertOk();
    }
}
