<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\Accounts\Actions\ChangePasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ChangePasswordRequest;
use Illuminate\Http\JsonResponse;
use Laravel\Sanctum\PersonalAccessToken;

class PasswordController extends Controller
{
    /**
     * Change the password. Other mobile tokens are deleted; the current one stays.
     */
    public function __invoke(ChangePasswordRequest $request, ChangePasswordAction $changePassword): JsonResponse
    {
        $currentToken = $request->user()->currentAccessToken();

        $changePassword->handle(
            $request->user(),
            $request->validated('password'),
            $currentToken instanceof PersonalAccessToken ? $currentToken->id : null,
        );

        return response()->json(['message' => __('auth.password_changed')]);
    }
}
