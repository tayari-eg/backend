<?php

namespace App\Domains\Orders\Listeners;

use App\Domains\Accounts\Actions\SendPushNotificationAction;
use App\Domains\Orders\Enums\OrderPushType;
use App\Domains\Orders\Events\OrderOffersCreated;
use App\Domains\Orders\Models\OrderOffer;
use App\Jobs\SendPushToDevice;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

class SendOrderOfferPushes
{
    public function __construct(private SendPushNotificationAction $sendPush) {}

    /**
     * Sends after the response (or at the end of the cron run), not through the queue: the shared host drains the queue
     * once a minute, about as long as an offer lasts. That means no retries, so one failed push never stops the others;
     * the tayar app also polls for offers.
     */
    public function handle(OrderOffersCreated $event): void
    {
        $tayars = User::query()->whereIn('id', array_map(fn (OrderOffer $offer): int => $offer->tayar_id, $event->offers))->get()->keyBy('id');

        foreach ($event->offers as $offer) {
            $tayar = $tayars->get($offer->tayar_id);

            if ($tayar === null) {
                continue;
            }

            $type = OrderPushType::OrderOffer;
            $jobs = $this->sendPush->handle(
                $tayar,
                $type->value,
                $type->titleKey(),
                $type->bodyKey(),
                ['earning' => (string) $offer->tayar_earning, 'distance' => (string) $offer->distance_km],
                ['order_id' => $offer->order_id, 'offer_id' => $offer->id],
                queue: false,
            );

            $jobs->each(fn (SendPushToDevice $job) => app()->terminating(fn () => rescue(fn () => Bus::dispatchSync($job))));
        }
    }
}
