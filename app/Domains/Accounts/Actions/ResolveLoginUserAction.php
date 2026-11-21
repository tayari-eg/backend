<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Data\PhoneNumber;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * The credential, status and phone checks shared by the mobile token and web session logins.
 */
class ResolveLoginUserAction
{
    /**
     * @throws ValidationException when the phone or password is wrong
     */
    public function handle(PhoneNumber $phone, string $password): User
    {
        $user = User::query()->where('phone', $phone->value)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['phone' => __('auth.failed')]);
        }

        if ($user->isBlocked()) {
            abort(Response::HTTP_FORBIDDEN, __('auth.blocked'));
        }

        if (config()->boolean('tayari.auth.require_phone_verification', true) && ! $user->hasVerifiedPhone()) {
            abort(Response::HTTP_FORBIDDEN, __('auth.phone_not_verified'));
        }

        return $user;
    }
}
