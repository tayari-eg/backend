<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\Accounts\Actions\ResendOtpAction;
use App\Domains\Accounts\Enums\OtpPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ResendOtpRequest;
use Illuminate\Http\JsonResponse;

class ResendOtpController extends Controller
{
    /**
     * Send a new OTP. The answer is the same whether or not the phone is registered.
     *
     * @unauthenticated
     */
    public function __invoke(ResendOtpRequest $request, ResendOtpAction $resendOtp): JsonResponse
    {
        $resendOtp->handle($request->phoneNumber(), OtpPurpose::from($request->validated('purpose')));

        return response()->json(['message' => __('otp.sent')]);
    }
}
