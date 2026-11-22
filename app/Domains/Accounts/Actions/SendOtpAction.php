<?php

namespace App\Domains\Accounts\Actions;

use App\Contracts\SmsSender;
use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Accounts\Enums\Locale;
use App\Domains\Accounts\Enums\OtpPurpose;
use App\Domains\Accounts\Models\OtpCode;
use App\Exceptions\OtpResendCooldownException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SendOtpAction
{
    /**
     * Hashed once per process with the configured cost; it holds nothing request-specific, so it is Octane-safe.
     */
    private static ?string $timingHash = null;

    public function __construct(private SmsSender $smsSender) {}

    /**
     * Spends about as long as a real send hashes its code, so a silent skip doesn't reveal whether a phone is registered.
     */
    public function matchSendTiming(): void
    {
        self::$timingHash ??= Hash::make(Str::random(32));

        Hash::check(Str::random(32), self::$timingHash);
    }

    /**
     * Replaces any open code for this phone and purpose, and sends the new one by SMS inside the request.
     * Returns the seconds until another code may be sent.
     *
     * @throws OtpResendCooldownException when the resend cooldown has not passed
     */
    public function handle(PhoneNumber $phone, OtpPurpose $purpose, Locale $locale): int
    {
        $now = now();
        $secondsToWait = $this->secondsUntilResendAllowed($phone, $purpose);

        if ($secondsToWait > 0) {
            throw new OtpResendCooldownException($secondsToWait);
        }

        $length = config('tayari.otp.length');
        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
        $expiresMinutes = config('tayari.otp.expires_minutes');

        DB::transaction(function () use ($phone, $purpose, $code, $now, $expiresMinutes): void {
            OtpCode::query()
                ->where('phone', $phone->value)
                ->where('purpose', $purpose)
                ->whereNull('used_at')
                ->delete();

            OtpCode::query()->create([
                'phone' => $phone->value,
                'purpose' => $purpose,
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => $now->copy()->addMinutes($expiresMinutes),
            ]);
        });

        $this->smsSender->send($phone, __("otp.sms.{$purpose->value}", ['code' => $code, 'minutes' => $expiresMinutes], $locale->value));

        return config('tayari.otp.resend_cooldown_seconds');
    }

    /**
     * 0 when a new code may be sent now.
     */
    public function secondsUntilResendAllowed(PhoneNumber $phone, OtpPurpose $purpose): int
    {
        $latestCode = OtpCode::query()
            ->where('phone', $phone->value)
            ->where('purpose', $purpose)
            ->latest('id')
            ->first();

        return $latestCode?->secondsUntilResendAllowed(now(), config('tayari.otp.resend_cooldown_seconds')) ?? 0;
    }
}
