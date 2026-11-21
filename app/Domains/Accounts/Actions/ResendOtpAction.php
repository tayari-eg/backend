<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Accounts\Enums\OtpPurpose;
use App\Exceptions\OtpResendCooldownException;
use App\Models\User;

class ResendOtpAction
{
    public function __construct(private SendOtpAction $sendOtp) {}

    /**
     * Sends silently only when it makes sense (an account exists, for verify_phone it is still unverified, and the
     * cooldown has passed), so the response never reveals whether a phone is registered.
     */
    public function handle(PhoneNumber $phone, OtpPurpose $purpose): void
    {
        $user = User::query()->where('phone', $phone->value)->first();

        if ($user === null || ($purpose === OtpPurpose::VerifyPhone && $user->hasVerifiedPhone())) {
            return;
        }

        if ($this->sendOtp->secondsUntilResendAllowed($phone, $purpose) > 0) {
            return;
        }

        try {
            $this->sendOtp->handle($phone, $purpose, $user->locale);
        } catch (OtpResendCooldownException) {
            // A parallel request sent a code between the check above and this send; stay quiet like the check does.
        }
    }
}
