<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Accounts\Enums\OtpPurpose;
use App\Domains\Accounts\Models\OtpCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class VerifyOtpAction
{
    /**
     * Marks the latest open code as used when it matches; a wrong code counts as an attempt.
     *
     * @throws ValidationException when the code is wrong, expired, used up or missing
     */
    public function handle(PhoneNumber $phone, OtpPurpose $purpose, #[\SensitiveParameter] string $code): void
    {
        $maxAttempts = config('tayari.otp.max_attempts');

        // The wrong-attempt counter must be saved, so the error is thrown after the transaction commits.
        $errorKey = DB::transaction(function () use ($phone, $purpose, $code, $maxAttempts): ?string {
            $otpCode = OtpCode::query()
                ->where('phone', $phone->value)
                ->where('purpose', $purpose)
                ->whereNull('used_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            // Wrong, expired, used-up and missing codes (including unknown phones) share one message,
            // so registered phones can't be told apart.
            if ($otpCode === null || $otpCode->isExpired(now()) || ! $otpCode->hasAttemptsLeft($maxAttempts)) {
                return 'otp.invalid_or_expired';
            }

            if (! Hash::check($code, $otpCode->code_hash)) {
                $otpCode->increment('attempts');

                return 'otp.invalid_or_expired';
            }

            $otpCode->forceFill(['used_at' => now()])->save();

            return null;
        });

        if ($errorKey !== null) {
            throw ValidationException::withMessages(['code' => __($errorKey)]);
        }
    }
}
