<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BlockUserAction
{
    /**
     * Blocks the account and deletes every token, so the mobile apps are signed out at once.
     */
    public function handle(User $user, string $reason): User
    {
        DB::transaction(function () use ($user, $reason): void {
            $user->forceFill([
                'status' => UserStatus::Blocked,
                'blocked_reason' => $reason,
            ])->save();

            $user->tokens()->delete();
        });

        return $user;
    }
}
