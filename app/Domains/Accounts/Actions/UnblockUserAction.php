<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Enums\UserStatus;
use App\Models\User;

class UnblockUserAction
{
    public function handle(User $user): User
    {
        $user->forceFill([
            'status' => UserStatus::Active,
            'blocked_reason' => null,
        ])->save();

        return $user;
    }
}
