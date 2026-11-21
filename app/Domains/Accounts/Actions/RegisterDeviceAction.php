<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Enums\Platform;
use App\Domains\Accounts\Models\UserDevice;
use App\Models\User;

class RegisterDeviceAction
{
    /**
     * A push token belongs to one device, so a token saved for another user moves to this one.
     */
    public function handle(User $user, Platform $platform, string $pushToken, ?string $appVersion): UserDevice
    {
        return UserDevice::query()->updateOrCreate(
            ['push_token' => $pushToken],
            [
                'user_id' => $user->id,
                'platform' => $platform,
                'app_version' => $appVersion,
                'last_seen_at' => now(),
            ],
        );
    }
}
