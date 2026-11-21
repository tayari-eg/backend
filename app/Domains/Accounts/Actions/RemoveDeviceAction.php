<?php

namespace App\Domains\Accounts\Actions;

use App\Models\User;

class RemoveDeviceAction
{
    /**
     * Only removes the token when it belongs to this user.
     */
    public function handle(User $user, string $pushToken): void
    {
        $user->devices()->where('push_token', $pushToken)->delete();
    }
}
