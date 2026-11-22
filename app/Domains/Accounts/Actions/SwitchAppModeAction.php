<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Enums\AppMode;
use App\Domains\Tayars\Actions\GoOfflineAction;
use App\Domains\Tayars\Models\TayarProfile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class SwitchAppModeAction
{
    public function __construct(private GoOfflineAction $goOffline) {}

    /**
     * Saves the mode the app reopens in. Tayar mode needs an approved application; leaving it sets an online tayar
     * offline, since a tayar in client mode can't take offers. The tayar profile row is locked first (GoOnlineAction
     * takes the same lock), so going online can't slip in between saving the mode and going offline.
     *
     * @throws AuthorizationException when switching to tayar mode without an approved application
     */
    public function handle(User $user, AppMode $mode): User
    {
        DB::transaction(function () use ($user, $mode): void {
            $tayarProfile = TayarProfile::query()->where('user_id', $user->id)->lockForUpdate()->first();

            if ($mode === AppMode::Tayar && $tayarProfile?->isApproved() !== true) {
                throw new AuthorizationException(__('tayars.not_approved'));
            }

            $user->forceFill(['last_mode' => $mode])->save();

            if ($mode === AppMode::Client && $tayarProfile !== null) {
                $this->goOffline->handle($tayarProfile);
            }
        });

        return $user;
    }
}
