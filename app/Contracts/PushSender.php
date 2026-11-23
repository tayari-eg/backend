<?php

namespace App\Contracts;

use App\Domains\Accounts\Models\UserDevice;
use App\Exceptions\InvalidPushTokenException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

interface PushSender
{
    /**
     * @param  array<string, string>  $data
     *
     * @throws InvalidPushTokenException when the device's token is dead and should be removed
     * @throws RequestException|ConnectionException when the push could not be sent
     */
    public function send(UserDevice $device, string $title, string $body, array $data): void;
}
