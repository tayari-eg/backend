<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Data\PhoneNumber;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;

class StartWebSessionAction
{
    public function __construct(private ResolveLoginUserAction $resolveLoginUser) {}

    /**
     * Logs the user into the web guard behind Sanctum's SPA cookie, with a fresh session ID.
     */
    public function handle(PhoneNumber $phone, string $password, Session $session): User
    {
        $user = $this->resolveLoginUser->handle($phone, $password);

        Auth::guard('web')->login($user);
        $session->regenerate();

        return $user;
    }
}
