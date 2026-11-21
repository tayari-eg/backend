<?php

namespace App\Domains\Accounts\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Blocked = 'blocked';
}
