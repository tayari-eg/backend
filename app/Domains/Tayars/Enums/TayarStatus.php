<?php

namespace App\Domains\Tayars\Enums;

enum TayarStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
