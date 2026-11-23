<?php

namespace Tests\Unit\Services\Push;

use App\Domains\Accounts\Models\UserDevice;
use App\Services\Push\LogPushSender;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogPushSenderTest extends TestCase
{
    public function test_it_logs_a_warning_with_the_reason_and_never_the_token(): void
    {
        Log::spy();
        $device = UserDevice::factory()->make(['user_id' => 3, 'push_token' => 'secret-device-token']);
        $device->id = 7;

        (new LogPushSender('FIREBASE_CREDENTIALS is empty'))->send($device, 'Title', 'Body', ['type' => 'test']);

        Log::shouldHaveReceived('warning')->once()->with('Push not sent: FIREBASE_CREDENTIALS is empty', [
            'user_id' => 3,
            'device_id' => 7,
            'title' => 'Title',
        ]);
    }
}
