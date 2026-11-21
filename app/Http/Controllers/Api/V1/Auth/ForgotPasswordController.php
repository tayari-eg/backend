<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\Accounts\Actions\RequestPasswordResetAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use Illuminate\Http\JsonResponse;

class ForgotPasswordController extends Controller
{
    /**
     * Send a password reset OTP. Always uses the OTP, whatever the phone verification switch says.
     *
     * @unauthenticated
     */
    public function __invoke(ForgotPasswordRequest $request, RequestPasswordResetAction $requestPasswordReset): JsonResponse
    {
        $requestPasswordReset->handle($request->phoneNumber());

        return response()->json(['message' => __('passwords.sent')]);
    }
}
