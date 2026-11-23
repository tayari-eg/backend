<?php

namespace App\Domains\Orders\Policies;

use App\Domains\Orders\Models\OrderOffer;
use App\Models\User;

class OrderOfferPolicy
{
    /**
     * Only the tayar an offer was made to may accept or reject it.
     */
    public function respond(User $user, OrderOffer $offer): bool
    {
        return $offer->tayar_id === $user->id;
    }
}
