<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Data\PhoneNumber;
use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

class IssueMobileTokenAction
{
    public function __construct(private ResolveLoginUserAction $resolveLoginUser) {}

    /**
     * @return array{user: User, token: NewAccessToken}
     */
    public function handle(PhoneNumber $phone, string $password, string $deviceName): array
    {
        $user = $this->resolveLoginUser->handle($phone, $password);

        return ['user' => $user, 'token' => $user->createToken($deviceName)];
    }
}
