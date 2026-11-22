<?php

namespace App\Domains\Tayars\Enums;

/**
 * A vehicle is reviewed together with the tayar application.
 */
enum VehicleStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
