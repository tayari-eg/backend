<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Accounts\Enums\OtpPurpose;
use App\Domains\Accounts\Events\PhoneVerified;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResetPasswordAction
{
    public function __construct(private VerifyOtpAction $verifyOtp) {}

    /**
     * Sets the new password and signs the user out of every device. The reset code proves the caller owns the phone,
     * so an unverified phone becomes verified.
     *
     * @throws ValidationException when the code is not accepted
     */
    public function handle(PhoneNumber $phone, #[\SensitiveParameter] string $code, #[\SensitiveParameter] string $password): void
    {
        // The code is checked first, so an unknown phone gets the same error as a wrong code for a registered one.
        $this->verifyOtp->handle($phone, OtpPurpose::ResetPassword, $code);

        $user = User::query()->where('phone', $phone->value)->first()
            ?? throw ValidationException::withMessages(['code' => __('otp.invalid_or_expired')]);

        DB::transaction(function () use ($user, $password): void {
            $verifiesPhone = ! $user->hasVerifiedPhone();

            $user->forceFill([
                'password' => $password,
                'remember_token' => null,
                'phone_verified_at' => $user->phone_verified_at ?? now(),
            ])->save();
            $user->tokens()->delete();

            // PhoneVerified waits for the commit (ShouldDispatchAfterCommit).
            if ($verifiesPhone) {
                PhoneVerified::dispatch($user);
            }
        });
    }
}
