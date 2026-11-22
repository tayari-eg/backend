<?php

namespace App\Domains\Tayars\Enums;

enum ReviewDecision: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
}
