<?php

namespace App\Domains\Accounts\Enums;

enum AccountType: string
{
    case Individual = 'individual';
    case Business = 'business';
}
