<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use App\Domains\Accounts\Data\PhoneNumber;
use Illuminate\Support\Facades\Log;

/**
 * Stand-in until an SMS provider is chosen. Messages carry OTPs, so the body is only written in local and testing.
 */
class LogSmsSender implements SmsSender
{
    public function send(PhoneNumber $to, string $message): void
    {
        Log::info('SMS sent', [
            'to' => $to->value,
            'message' => app()->environment(['local', 'testing']) ? $message : '[redacted]',
        ]);
    }
}
