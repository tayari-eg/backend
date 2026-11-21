<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Accounts\Enums\OtpPurpose;
use App\Domains\Accounts\Events\PhoneVerified;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class VerifyPhoneAction
{
    public function __construct(private VerifyOtpAction $verifyOtp) {}

    /**
     * Does not log the user in; the app calls a login endpoint afterwards.
     *
     * @throws ValidationException when the code is not accepted
     */
    public function handle(PhoneNumber $phone, string $code): User
    {
        // The code is checked first, so an unknown phone gets the same error as a wrong code for a registered one.
        $this->verifyOtp->handle($phone, OtpPurpose::VerifyPhone, $code);

        $user = User::query()->where('phone', $phone->value)->first()
            ?? throw ValidationException::withMessages(['code' => __('otp.invalid_or_expired')]);

        if (! $user->hasVerifiedPhone()) {
            $user->forceFill(['phone_verified_at' => now()])->save();

            PhoneVerified::dispatch($user);
        }

        return $user;
    }
}
