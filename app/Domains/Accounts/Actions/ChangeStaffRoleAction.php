<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeStaffRoleAction
{
    /**
     * @throws ValidationException for the actor's own account or the last active super admin
     */
    public function handle(User $staff, StaffRole $role, User $actor): User
    {
        if ($staff->is($actor)) {
            throw ValidationException::withMessages(['staff' => __('admin.cannot_change_own_role')]);
        }

        DB::transaction(function () use ($staff, $role): void {
            // Locking every active super admin stops two super admins from demoting each other at the same moment.
            $activeSuperAdminIds = User::query()
                ->role(StaffRole::SuperAdmin->value)
                ->where('status', UserStatus::Active)
                ->lockForUpdate()
                ->pluck('id');

            if ($activeSuperAdminIds->all() === [$staff->id] && $role !== StaffRole::SuperAdmin) {
                throw ValidationException::withMessages(['staff' => __('admin.last_super_admin')]);
            }

            $staff->syncRoles([$role->value]);
        });

        return $staff;
    }
}
