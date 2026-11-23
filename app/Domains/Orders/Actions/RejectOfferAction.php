<?php

namespace App\Domains\Orders\Actions;

use App\Domains\Orders\Enums\OfferStatus;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderOffer;
use Illuminate\Support\Facades\DB;

class RejectOfferAction
{
    public function __construct(private DispatchOrderOffersAction $dispatchOrderOffers) {}

    /**
     * Rejects a live offer; the tayar never gets this order again. Rejecting an offer that is no longer live changes
     * nothing. When it was the round's last live offer, the next round starts at once.
     */
    public function handle(OrderOffer $offer): void
    {
        $orderStillWaiting = DB::transaction(function () use ($offer): ?Order {
            $order = Order::query()->lockForUpdate()->find($offer->order_id);
            $offer = OrderOffer::query()->lockForUpdate()->findOrFail($offer->id);

            if ($offer->isLive()) {
                $offer->moveTo(OfferStatus::Rejected, ['responded_at' => now()]);
            }

            $roundIsOver = $order?->status === OrderStatus::LookingForTayar
                && ! OrderOffer::query()->where('order_id', $order->id)->live()->exists();

            return $roundIsOver ? $order : null;
        });

        if ($orderStillWaiting !== null) {
            $this->dispatchOrderOffers->handle($orderStillWaiting);
        }
    }
}
