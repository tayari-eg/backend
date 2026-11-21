<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Accounts\Enums\AccountType;
use App\Domains\Accounts\Enums\Locale;
use App\Domains\Accounts\Enums\OtpPurpose;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterUserAction
{
    public function __construct(private SendOtpAction $sendOtp) {}

    /**
     * Only creates a new account (RegisterRequest rejects taken phones), then sends the OTP when verification is required.
     *
     * @return array{user: User, resend_available_in: ?int} resend_available_in is null when no OTP is needed
     *
     * @throws ValidationException when another request registered the same phone after validation
     */
    public function handle(string $name, PhoneNumber $phone, string $password, AccountType $accountType, Locale $locale): array
    {
        try {
            // The transaction is a savepoint when nested, so a failed insert doesn't abort an outer PostgreSQL transaction.
            $user = DB::transaction(fn (): User => User::query()->create([
                'name' => $name,
                'phone' => $phone->value,
                'password' => $password,
                'account_type' => $accountType,
                'locale' => $locale,
            ]));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['phone' => __('auth.phone_taken')]);
        }

        if (! config()->boolean('tayari.auth.require_phone_verification', true)) {
            return ['user' => $user, 'resend_available_in' => null];
        }

        $secondsToWait = $this->sendOtp->secondsUntilResendAllowed($phone, OtpPurpose::VerifyPhone);

        return [
            'user' => $user,
            'resend_available_in' => $secondsToWait > 0 ? $secondsToWait : $this->sendOtp->handle($phone, OtpPurpose::VerifyPhone, $locale),
        ];
    }
}
