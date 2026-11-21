<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_get_401(): void
    {
        $this->getJson(route('api.v1.admin.business-profiles.index'))->assertUnauthorized();
    }

    public function test_clients_cannot_use_admin_routes(): void
    {
        Sanctum::actingAs(User::factory()->business()->create());

        $this->getJson(route('api.v1.admin.business-profiles.index'), ['Accept-Language' => 'en'])
            ->assertForbidden()
            ->assertJsonPath('message', 'This section is only available to Tayari staff.');
    }

    public function test_blocked_staff_cannot_use_admin_routes(): void
    {
        $admin = $this->createStaffUser();
        $admin->forceFill(['status' => 'blocked'])->save();
        Sanctum::actingAs($admin);

        $this->getJson(route('api.v1.admin.business-profiles.index'))->assertForbidden();
    }

    public function test_the_super_admin_passes_every_permission_through_the_gate(): void
    {
        $profile = BusinessProfile::factory()->create();
        Sanctum::actingAs($this->createStaffUser(StaffRole::SuperAdmin));

        $this->getJson(route('api.v1.admin.national-ids.index'))->assertOk();
        $this->postJson(route('api.v1.admin.business-profiles.approve', $profile))->assertOk();
    }

    public function test_a_missing_permission_is_forbidden_with_a_translated_message(): void
    {
        $profile = BusinessProfile::factory()->create();
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));

        $this->postJson(route('api.v1.admin.business-profiles.reject', $profile), ['reason' => 'x'], ['Accept-Language' => 'ar'])
            ->assertForbidden()
            ->assertJsonPath('message', 'غير مسموح لك بهذا الإجراء.');
    }

    public function test_admins_log_in_through_the_web_session(): void
    {
        config(['sanctum.stateful' => ['localhost:3000']]);
        $admin = $this->createStaffUser();
        $headers = ['Origin' => 'http://localhost:3000', 'Referer' => 'http://localhost:3000/'];

        $this->postJson(route('api.v1.auth.login'), ['phone' => $admin->phone, 'password' => 'password'], $headers)->assertOk();

        $this->getJson(route('api.v1.admin.business-profiles.index'), $headers)->assertOk();
    }
}
