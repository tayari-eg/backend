<?php

namespace Tests\Feature\Domains\Accounts;

use App\Domains\Accounts\Actions\BlockUserAction;
use App\Domains\Accounts\Actions\ChangeStaffRoleAction;
use App\Domains\Accounts\Actions\UnblockUserAction;
use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BlockUserActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocking_a_user_stores_the_reason_and_deletes_every_token(): void
    {
        $user = User::factory()->create();
        $user->createToken('phone');
        $user->createToken('tablet');
        $otherUser = User::factory()->create();
        $otherUser->createToken('phone');

        app(BlockUserAction::class)->handle($user, 'Fraud reports', $this->createStaffUser());

        $user->refresh();
        $this->assertSame(UserStatus::Blocked, $user->status);
        $this->assertSame('Fraud reports', $user->blocked_reason);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $otherUser->id]);
    }

    public function test_blocking_an_already_blocked_user_updates_the_reason(): void
    {
        $user = User::factory()->blocked()->create();

        app(BlockUserAction::class)->handle($user, 'New reason', $this->createStaffUser());

        $this->assertSame('New reason', $user->refresh()->blocked_reason);
        $this->assertSame(UserStatus::Blocked, $user->status);
    }

    public function test_nobody_can_block_their_own_account(): void
    {
        $superAdmin = $this->createStaffUser(StaffRole::SuperAdmin);
        $this->createStaffUser(StaffRole::SuperAdmin);

        try {
            app(BlockUserAction::class)->handle($superAdmin, 'Self', $superAdmin);
            $this->fail('Blocking yourself must fail.');
        } catch (ValidationException $exception) {
            $this->assertSame([__('admin.cannot_block_self')], $exception->errors()['staff']);
        }

        $this->assertSame(UserStatus::Active, $superAdmin->refresh()->status);
    }

    public function test_the_last_active_super_admin_cannot_be_blocked(): void
    {
        $lastSuperAdmin = $this->createStaffUser(StaffRole::SuperAdmin);
        $this->createStaffUser(StaffRole::SuperAdmin)->forceFill(['status' => UserStatus::Blocked])->save();

        try {
            app(BlockUserAction::class)->handle($lastSuperAdmin, 'Locked out', $this->createStaffUser(StaffRole::Admin));
            $this->fail('Blocking the last active super admin must fail.');
        } catch (ValidationException $exception) {
            $this->assertSame([__('admin.last_super_admin')], $exception->errors()['staff']);
        }

        $this->assertSame(UserStatus::Active, $lastSuperAdmin->refresh()->status);
    }

    public function test_a_super_admin_can_be_blocked_while_another_one_is_active(): void
    {
        $superAdmin = $this->createStaffUser(StaffRole::SuperAdmin);
        $otherSuperAdmin = $this->createStaffUser(StaffRole::SuperAdmin);

        app(BlockUserAction::class)->handle($superAdmin, 'Left the company', $otherSuperAdmin);

        $this->assertSame(UserStatus::Blocked, $superAdmin->refresh()->status);
    }

    public function test_the_last_active_super_admin_cannot_be_demoted(): void
    {
        $lastSuperAdmin = $this->createStaffUser(StaffRole::SuperAdmin);

        try {
            app(ChangeStaffRoleAction::class)->handle($lastSuperAdmin, StaffRole::Admin, $this->createStaffUser(StaffRole::Admin));
            $this->fail('Demoting the last active super admin must fail.');
        } catch (ValidationException $exception) {
            $this->assertSame([__('admin.last_super_admin')], $exception->errors()['staff']);
        }

        $this->assertTrue($lastSuperAdmin->refresh()->hasRole(StaffRole::SuperAdmin->value));
    }

    public function test_unblocking_an_active_user_succeeds(): void
    {
        $user = User::factory()->create();

        app(UnblockUserAction::class)->handle($user);

        $this->assertSame(UserStatus::Active, $user->refresh()->status);
    }

    public function test_unblocking_a_user_makes_the_account_active_again(): void
    {
        $user = User::factory()->blocked()->create();

        app(UnblockUserAction::class)->handle($user);

        $user->refresh();
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertNull($user->blocked_reason);
    }
}
