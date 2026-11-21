<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\Accounts\Actions\ResetPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;

class ResetPasswordController extends Controller
{
    /**
     * Set a new password with the reset OTP. Signs the user out of every device.
     *
     * @unauthenticated
     */
    public function __invoke(ResetPasswordRequest $request, ResetPasswordAction $resetPassword): JsonResponse
    {
        $resetPassword->handle($request->phoneNumber(), $request->validated('code'), $request->validated('password'));

        return response()->json(['message' => __('passwords.reset')]);
    }
}
