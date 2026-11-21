<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BlockUserAction
{
    /**
     * Blocks the account and deletes every token, so the mobile apps are signed out at once.
     * Blocking an already blocked user only updates the reason.
     *
     * @throws ValidationException for the actor's own account or the last active super admin
     */
    public function handle(User $user, string $reason, User $actor): User
    {
        if ($user->is($actor)) {
            throw ValidationException::withMessages(['staff' => __('admin.cannot_block_self')]);
        }

        DB::transaction(function () use ($user, $reason): void {
            // Locking every active super admin stops two super admins from blocking each other at the same moment.
            $activeSuperAdminIds = User::query()
                ->role(StaffRole::SuperAdmin->value)
                ->where('status', UserStatus::Active)
                ->lockForUpdate()
                ->pluck('id');

            if ($activeSuperAdminIds->all() === [$user->id]) {
                throw ValidationException::withMessages(['staff' => __('admin.last_super_admin')]);
            }

            $user->forceFill([
                'status' => UserStatus::Blocked,
                'blocked_reason' => $reason,
            ])->save();

            $user->tokens()->delete();
        });

        return $user;
    }
}
