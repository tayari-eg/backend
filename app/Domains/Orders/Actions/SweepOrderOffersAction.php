<?php

namespace App\Domains\Orders\Actions;

use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;

class SweepOrderOffersAction
{
    public function __construct(private DispatchOrderOffersAction $dispatchOrderOffers) {}

    /**
     * Moves on every order still looking for a tayar with no live offer: its overdue offers expire, then it gets a new
     * round or is cancelled once the search time is over. Each order goes through DispatchOrderOffersAction, which locks
     * the order before its offers. An order that fails is reported and skipped, so it never stops the rest of the run.
     * Returns how many offers were made.
     */
    public function handle(): int
    {
        $count = 0;

        Order::query()
            ->where('status', OrderStatus::LookingForTayar)
            ->whereDoesntHave('offers', fn ($offers) => $offers->live())
            ->select('id')
            ->chunkById(100, function ($orders) use (&$count): void {
                foreach ($orders as $order) {
                    $count += count(rescue(fn (): array => $this->dispatchOrderOffers->handle($order), []));
                }
            });

        return $count;
    }
}
