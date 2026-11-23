<?php

namespace App\Contracts;

use App\Domains\Accounts\Data\PhoneNumber;

interface SmsSender
{
    public function send(PhoneNumber $to, #[\SensitiveParameter] string $message): void;
}
