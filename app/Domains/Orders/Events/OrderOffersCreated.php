<?php

namespace App\Domains\Orders\Events;

use App\Domains\Orders\Models\OrderOffer;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A new round of offers for one order.
 */
class OrderOffersCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    /**
     * @param  list<OrderOffer>  $offers
     */
    public function __construct(public array $offers) {}
}
