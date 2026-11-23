<?php

namespace App\Domains\Orders\Listeners;

use App\Domains\Orders\Actions\DispatchOrderOffersAction;
use App\Domains\Orders\Events\OrderCreated;

class StartOrderMatching
{
    public function __construct(private DispatchOrderOffersAction $dispatchOrderOffers) {}

    /**
     * Offers the new order at once. A failure is reported but never fails the order already saved: the
     * orders:dispatch-offers sweep tries again within a minute.
     */
    public function handle(OrderCreated $event): void
    {
        rescue(fn () => $this->dispatchOrderOffers->handle($event->order));
    }
}
