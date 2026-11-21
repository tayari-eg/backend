<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\Accounts\Actions\VerifyPhoneAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\VerifyPhoneRequest;
use Illuminate\Http\JsonResponse;

class VerifyPhoneController extends Controller
{
    /**
     * Confirm the phone with the OTP. Does not log in; call a login endpoint next.
     *
     * @unauthenticated
     */
    public function __invoke(VerifyPhoneRequest $request, VerifyPhoneAction $verifyPhone): JsonResponse
    {
        $verifyPhone->handle($request->phoneNumber(), $request->validated('code'));

        return response()->json(['message' => __('auth.phone_verified')]);
    }
}
