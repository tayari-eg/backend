<?php

namespace App\Domains\Accounts\Actions;

use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutAction
{
    /**
     * Deletes the token on the mobile path, or ends the web session on the SPA path.
     */
    public function handle(User $user, ?Session $session): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();

            return;
        }

        Auth::guard('web')->logout();
        $session?->invalidate();
        $session?->regenerateToken();
    }
}
