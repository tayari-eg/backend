<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Accounts\Enums\OtpPurpose;
use App\Exceptions\OtpResendCooldownException;
use App\Models\User;

class RequestPasswordResetAction
{
    public function __construct(private SendOtpAction $sendOtp) {}

    /**
     * Sends a reset code only when the account exists and the cooldown has passed; callers always answer the same way,
     * so phones can't be enumerated.
     */
    public function handle(PhoneNumber $phone): void
    {
        $user = User::query()->where('phone', $phone->value)->first();

        if ($user === null || $this->sendOtp->secondsUntilResendAllowed($phone, OtpPurpose::ResetPassword) > 0) {
            return;
        }

        try {
            $this->sendOtp->handle($phone, OtpPurpose::ResetPassword, $user->locale);
        } catch (OtpResendCooldownException) {
            // A parallel request sent a code between the check above and this send; stay quiet like the check does.
        }
    }
}
