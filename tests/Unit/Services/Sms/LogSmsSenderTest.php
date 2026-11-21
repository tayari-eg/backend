<?php

namespace Tests\Unit\Services\Sms;

use App\Domains\Accounts\Data\PhoneNumber;
use App\Services\Sms\LogSmsSender;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogSmsSenderTest extends TestCase
{
    public function test_it_writes_the_full_message_in_testing(): void
    {
        Log::spy();

        (new LogSmsSender)->send(PhoneNumber::from('01012345678'), 'Your code is 123456');

        Log::shouldHaveReceived('info')->once()->with('SMS sent', ['to' => '+201012345678', 'message' => 'Your code is 123456']);
    }

    public function test_it_redacts_the_message_outside_local_and_testing(): void
    {
        Log::spy();
        $this->app['env'] = 'production';

        (new LogSmsSender)->send(PhoneNumber::from('01012345678'), 'Your code is 123456');

        Log::shouldHaveReceived('info')->once()->with('SMS sent', ['to' => '+201012345678', 'message' => '[redacted]']);
    }
}
