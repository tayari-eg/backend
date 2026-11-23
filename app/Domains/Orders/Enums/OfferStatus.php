<?php

namespace App\Domains\Orders\Enums;

/**
 * An offer of one order to one tayar. Only an offered offer changes; the others are final.
 */
enum OfferStatus: string
{
    case Offered = 'offered';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Withdrawn = 'withdrawn';

    public function canTransitionTo(self $next): bool
    {
        return $this === self::Offered && $next !== self::Offered;
    }
}
