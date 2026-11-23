<?php

namespace App\Services\Push;

use App\Contracts\PushSender;
use App\Domains\Accounts\Models\UserDevice;
use Illuminate\Support\Facades\Log;

/**
 * Used when the Firebase credentials are missing (local and tests): nothing is sent, the push is only logged.
 */
class LogPushSender implements PushSender
{
    public function __construct(public readonly string $reason) {}

    public function send(UserDevice $device, string $title, string $body, array $data): void
    {
        Log::warning("Push not sent: {$this->reason}", [
            'user_id' => $device->user_id,
            'device_id' => $device->id,
            'title' => $title,
        ]);
    }
}
