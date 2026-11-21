<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * A new OTP was asked for before the resend cooldown passed. Callers check the cooldown first, so this only guards races.
 */
class OtpResendCooldownException extends TooManyRequestsHttpException
{
    public function __construct(public readonly int $secondsToWait)
    {
        parent::__construct($secondsToWait, __('otp.resend_too_soon', ['seconds' => $secondsToWait]));
    }
}
