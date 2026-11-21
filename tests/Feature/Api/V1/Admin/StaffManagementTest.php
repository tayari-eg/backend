<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = $this->createStaffUser(StaffRole::SuperAdmin);
        Sanctum::actingAs($this->superAdmin);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function newStaff(array $overrides = []): array
    {
        return [
            'name' => 'Sara Ops',
            'phone' => '0100 000 0001',
            'password' => 'staff-password',
            'password_confirmation' => 'staff-password',
            'role' => 'ops',
            ...$overrides,
        ];
    }

    public function test_the_list_shows_staff_with_their_role_and_status_and_never_customers(): void
    {
        $support = $this->createStaffUser(StaffRole::Support);
        $support->forceFill(['status' => UserStatus::Blocked, 'blocked_reason' => 'Left'])->save();
        User::factory()->create();

        $this->getJson(route('api.v1.admin.staff.index'))
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.id', $support->id)
            ->assertJsonPath('data.0.role', 'support')
            ->assertJsonPath('data.0.status', 'blocked')
            ->assertJsonPath('data.0.blocked_reason', 'Left')
            ->assertJsonPath('data.1.role', 'super_admin');
    }

    public function test_a_super_admin_creates_a_staff_member_who_can_log_in(): void
    {
        $this->postJson(route('api.v1.admin.staff.store'), $this->newStaff())
            ->assertCreated()
            ->assertJsonPath('data.phone', '+201000000001')
            ->assertJsonPath('data.role', 'ops')
            ->assertJsonPath('data.status', 'active');

        $staff = User::query()->where('phone', '+201000000001')->firstOrFail();
        $this->assertTrue($staff->hasRole('ops'));
        $this->assertTrue($staff->hasVerifiedPhone());

        $this->postJson(route('api.v1.auth.token'), ['phone' => '01000000001', 'password' => 'staff-password', 'device_name' => 'Test'])
            ->assertOk();
    }

    public function test_the_create_form_is_validated(): void
    {
        User::factory()->create(['phone' => '+201000000001']);

        $this->postJson(route('api.v1.admin.staff.store'), $this->newStaff(), ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.phone.0', 'This phone number already belongs to an account.');

        $this->postJson(route('api.v1.admin.staff.store'), $this->newStaff([
            'phone' => '01000000002',
            'password' => 'short',
            'password_confirmation' => 'other',
            'role' => 'super_admin',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password', 'role']);

        $this->postJson(route('api.v1.admin.staff.store'), $this->newStaff([
            'phone' => '01000000002',
            'password' => str_repeat('a', 65),
            'password_confirmation' => str_repeat('a', 65),
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_a_super_admin_changes_a_staff_members_role(): void
    {
        $staff = $this->createStaffUser(StaffRole::Support);

        $this->putJson(route('api.v1.admin.staff.role.update', $staff), ['role' => 'admin'])
            ->assertOk()
            ->assertJsonPath('data.role', 'admin');

        $this->assertSame(['admin'], $staff->refresh()->getRoleNames()->all());

        $this->putJson(route('api.v1.admin.staff.role.update', $staff), ['role' => 'super_admin'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    public function test_another_super_admin_can_be_demoted(): void
    {
        $otherSuperAdmin = $this->createStaffUser(StaffRole::SuperAdmin);

        $this->putJson(route('api.v1.admin.staff.role.update', $otherSuperAdmin), ['role' => 'admin'])
            ->assertOk()
            ->assertJsonPath('data.role', 'admin');
    }

    public function test_nobody_changes_their_own_role_or_blocks_themselves(): void
    {
        $this->putJson(route('api.v1.admin.staff.role.update', $this->superAdmin), ['role' => 'support'], ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.staff.0', 'You cannot change your own role.');

        $this->postJson(route('api.v1.admin.staff.block', $this->superAdmin), ['reason' => 'x'], ['Accept-Language' => 'ar'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.staff.0', 'لا يمكنك حظر حسابك.');

        $this->assertTrue($this->superAdmin->refresh()->hasRole('super_admin'));
        $this->assertSame(UserStatus::Active, $this->superAdmin->status);
    }

    public function test_a_super_admin_blocks_and_unblocks_a_staff_member(): void
    {
        $staff = $this->createStaffUser(StaffRole::Ops);
        $staff->createToken('phone');

        $this->postJson(route('api.v1.admin.staff.block', $staff), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);

        $this->postJson(route('api.v1.admin.staff.block', $staff), ['reason' => 'Left the company'])
            ->assertOk()
            ->assertJsonPath('data.status', 'blocked')
            ->assertJsonPath('data.blocked_reason', 'Left the company');
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $staff->id]);

        $this->postJson(route('api.v1.admin.staff.unblock', $staff))
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    }

    public function test_customers_are_404_on_the_staff_routes(): void
    {
        $customer = User::factory()->create();

        $this->putJson(route('api.v1.admin.staff.role.update', $customer), ['role' => 'admin'])->assertNotFound();
        $this->postJson(route('api.v1.admin.staff.block', $customer), ['reason' => 'x'])->assertNotFound();
        $this->postJson(route('api.v1.admin.staff.unblock', $customer))->assertNotFound();

        $this->assertFalse($customer->refresh()->isStaff());
    }
}
