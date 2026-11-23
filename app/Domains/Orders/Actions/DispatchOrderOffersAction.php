<?php

namespace App\Domains\Orders\Actions;

use App\Domains\Catalog\Enums\ModeCode;
use App\Domains\Orders\Enums\OfferStatus;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Events\OrderOffersCreated;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderOffer;
use App\Domains\Pricing\Actions\CalculateTayarEarningAction;
use App\Domains\Pricing\Data\Distance;
use App\Domains\Pricing\Data\GeoPoint;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\TayarWorkSession;
use App\Domains\Wallet\Enums\WalletType;
use App\Domains\Wallet\Models\Wallet;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DispatchOrderOffersAction
{
    /**
     * The nearest online tayars checked against the work session rules, per radius.
     */
    private const int CANDIDATE_LIMIT = 50;

    public function __construct(
        private CalculateTayarEarningAction $calculateTayarEarning,
        private CancelOrderAction $cancelOrder,
    ) {}

    /**
     * Moves the search for a tayar on, and is safe to run any number of times: marks the order's overdue offers
     * expired, then, when no offer is live, cancels the order as the system once the search time is over, or offers it
     * to the nearest eligible tayars in a new round. Each round starts at a wider radius, and widens further at once
     * when nobody is eligible. Does nothing for an order that is no longer looking for a tayar. Returns the new offers.
     *
     * Eligible: online, approved, active account, not the sender, never rejected this order, in a current work session
     * in the order's mode that fits it (zone: both points within the radius of the tayar; area: both points in the
     * area; long distance: the drop-off near the destination), an approved active vehicle that can carry food for food
     * orders, and fewer accepted orders than the mode allows.
     *
     * @return list<OrderOffer>
     */
    public function handle(Order $order): array
    {
        return DB::transaction(function () use ($order): array {
            $order = Order::query()->with(['category', 'mode'])->lockForUpdate()->find($order->id);

            if ($order === null || $order->status !== OrderStatus::LookingForTayar) {
                return [];
            }

            // Bulk update: overdue() only matches Offered, the only non-final offer status, so every row may move to Expired.
            OrderOffer::query()->where('order_id', $order->id)->overdue()->update(['status' => OfferStatus::Expired, 'updated_at' => now()]);

            if (OrderOffer::query()->where('order_id', $order->id)->where('status', OfferStatus::Offered)->exists()) {
                return [];
            }

            if ($order->searchEndsAt()->lte(now())) {
                $this->cancelOrder->handle($order, null, null);

                return [];
            }

            $round = (int) OrderOffer::query()->where('order_id', $order->id)->max('round') + 1;

            $offers = $this->candidates($order, $round)
                ->map(fn (TayarProfile $profile): OrderOffer => $this->offer($order, $profile, $round))
                ->values()
                ->all();

            if ($offers !== []) {
                OrderOffersCreated::dispatch($offers);
            }

            return $offers;
        });
    }

    /**
     * @return Collection<int, TayarProfile>
     */
    private function candidates(Order $order, int $round): Collection
    {
        $step = config()->integer('tayari.matching.radius_step_km');
        $maxRadius = config()->integer('tayari.matching.max_radius_km');
        $radius = min(config()->integer('tayari.matching.first_radius_km') + $step * ($round - 1), $maxRadius);

        while (true) {
            $eligible = $this->eligibleWithin($order, $radius);
            $nextRadius = min($radius + $step, $maxRadius);

            if ($eligible->isNotEmpty() || $nextRadius <= $radius) {
                return $eligible->take(config()->integer('tayari.matching.offers_per_round'))->values();
            }

            $radius = $nextRadius;
        }
    }

    /**
     * @return Collection<int, TayarProfile>
     */
    private function eligibleWithin(Order $order, int $radiusKm): Collection
    {
        $profiles = TayarProfile::query()
            ->nearestOnline((float) $order->pickup_lat, (float) $order->pickup_lng, $radiusKm)
            ->where('tayar_profiles.user_id', '!=', $order->sender_id)
            // Tayars owing more cash than allowed get no new offers until they hand it in.
            ->whereNotIn('tayar_profiles.user_id', Wallet::query()
                ->select('user_id')
                ->where('type', WalletType::Tayar)
                ->where('balance', '<', (string) BigDecimal::of(config('tayari.wallet.max_cash_owed'))->negated()))
            ->whereNotIn('tayar_profiles.user_id', OrderOffer::query()
                ->select('tayar_id')
                ->where('order_id', $order->id)
                ->where('status', OfferStatus::Rejected))
            ->whereHas('currentWorkSession', fn (Builder $sessions) => $sessions
                ->whereNull('ended_at')
                ->where('mode_id', $order->mode_id))
            ->with(['currentWorkSession.area', 'currentWorkSession.vehicle.vehicleType'])
            ->limit(self::CANDIDATE_LIMIT)
            ->get();

        $activeOrders = Order::query()
            ->whereIn('tayar_id', $profiles->pluck('user_id'))
            ->whereStatusIn(OrderStatus::withTayarCases())
            ->groupBy('tayar_id')
            ->selectRaw('tayar_id, count(*) as active_count')
            ->pluck('active_count', 'tayar_id');

        return $profiles->filter(fn (TayarProfile $profile): bool => (int) ($activeOrders[$profile->user_id] ?? 0) < $order->mode->code->maxActiveOrders()
            && $this->canCarry($order, $profile->currentWorkSession)
            && $this->fitsSession($order, $profile, $profile->currentWorkSession))
            ->values();
    }

    private function canCarry(Order $order, TayarWorkSession $session): bool
    {
        return $session->vehicle->canBeUsed() && (! $order->category->is_food || $session->vehicle->vehicleType->can_carry_food);
    }

    private function fitsSession(Order $order, TayarProfile $profile, TayarWorkSession $session): bool
    {
        $pickup = new GeoPoint((string) $order->pickup_lat, (string) $order->pickup_lng);
        $dropoff = new GeoPoint((string) $order->dropoff_lat, (string) $order->dropoff_lng);

        return match ($order->mode->code) {
            ModeCode::Zone => $session->radius_km !== null
                && self::within(new GeoPoint((string) $profile->current_lat, (string) $profile->current_lng), [$pickup, $dropoff], (string) $session->radius_km),
            ModeCode::Area => $session->area !== null
                && self::within(new GeoPoint((string) $session->area->center_lat, (string) $session->area->center_lng), [$pickup, $dropoff], (string) $session->area->radius_km),
            ModeCode::LongDistance => $session->destination_lat !== null && $session->destination_lng !== null
                && self::within(new GeoPoint((string) $session->destination_lat, (string) $session->destination_lng), [$dropoff], (string) config()->integer('tayari.matching.destination_radius_km')),
        };
    }

    /**
     * @param  list<GeoPoint>  $points
     */
    private static function within(GeoPoint $center, array $points, string $radiusKm): bool
    {
        foreach ($points as $point) {
            if (Distance::straightLineKm($center, $point)->isGreaterThan($radiusKm)) {
                return false;
            }
        }

        return true;
    }

    private function offer(Order $order, TayarProfile $profile, int $round): OrderOffer
    {
        $earning = $this->calculateTayarEarning->handle($order, (int) $profile->currentWorkSession->vehicle->vehicle_type_id);

        $offer = new OrderOffer;
        $offer->forceFill([
            'order_id' => $order->id,
            'tayar_id' => $profile->user_id,
            'work_session_id' => $profile->current_work_session_id,
            'round' => $round,
            'distance_km' => number_format((float) $profile->distance_km, 2, '.', ''),
            ...$earning->toAttributes(),
            'status' => OfferStatus::Offered,
            'expires_at' => now()->addSeconds(config()->integer('tayari.matching.offer_timeout_seconds')),
        ])->save();

        return $offer;
    }
}
