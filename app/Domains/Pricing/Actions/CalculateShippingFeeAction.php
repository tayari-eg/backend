<?php

namespace App\Domains\Pricing\Actions;

use App\Domains\Catalog\Enums\ModeCode;
use App\Domains\Catalog\Models\Area;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Pricing\Data\Distance;
use App\Domains\Pricing\Data\GeoPoint;
use App\Domains\Pricing\Data\ShippingQuote;
use App\Domains\Pricing\Models\PricingRule;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class CalculateShippingFeeAction
{
    private const int MONEY_SCALE = 2;

    /**
     * Prices a trip before any tayar is chosen. The mode comes from the trip: area when both points are in one active
     * area, then zone up to tayari.orders.zone_max_distance_km, then long distance. A mode that is inactive or has no
     * active "all vehicles" rule is skipped; a food order only takes a mode that allows food.
     *
     * fee = max(minimum_fare, base_fare + price_per_km × km) + food_extra (food categories only);
     * tayar_earning = fee × tayar_share_percent ÷ 100, rounded half up; platform_fee = fee − tayar_earning.
     *
     * @throws ValidationException (errors.route) when the points are closer than tayari.orders.min_distance_km in a straight line,
     *                             the trip is too long, no mode serves it, or it is too far for food
     */
    public function handle(GeoPoint $pickup, GeoPoint $dropoff, Category $category): ShippingQuote
    {
        $minDistanceKm = BigDecimal::of((string) config('tayari.orders.min_distance_km'));

        if (Distance::straightLineKm($pickup, $dropoff)->isLessThan($minDistanceKm)) {
            throw ValidationException::withMessages(['route' => __('orders.route_too_short', ['min' => (string) $minDistanceKm->multipliedBy(1000)->strippedOfTrailingZeros()])]);
        }

        $distanceKm = Distance::roadKm($pickup, $dropoff);
        $maxDistanceKm = config()->integer('tayari.orders.max_distance_km');

        if ($distanceKm->isGreaterThan($maxDistanceKm)) {
            throw ValidationException::withMessages(['route' => __('orders.route_too_long', ['max' => $maxDistanceKm])]);
        }

        $candidates = $this->candidateModes($pickup, $dropoff, $distanceKm);

        if ($candidates === []) {
            throw ValidationException::withMessages(['route' => __('orders.route_unavailable')]);
        }

        $mode = $category->is_food
            ? collect($candidates)->first(fn (Mode $mode): bool => $mode->allows_food)
            : $candidates[0];

        if ($mode === null) {
            throw ValidationException::withMessages(['route' => __('orders.food_too_far')]);
        }

        return $this->quote($mode, $mode->pricingRules->firstOrFail(), $distanceKm, $category->is_food);
    }

    /**
     * The modes that can serve the trip, in the order they are preferred.
     *
     * @return list<Mode>
     */
    private function candidateModes(GeoPoint $pickup, GeoPoint $dropoff, BigDecimal $distanceKm): array
    {
        /** @var Collection<string, Mode> $modes */
        $modes = Mode::query()
            ->active()
            ->with(['pricingRules' => fn ($query) => $query->where('is_active', true)->whereNull('vehicle_type_id')])
            ->get()
            ->filter(fn (Mode $mode): bool => $mode->pricingRules->isNotEmpty())
            ->keyBy(fn (Mode $mode): string => $mode->code->value);

        $candidates = [];

        if ($modes->has(ModeCode::Area->value) && $this->isInsideOneArea($pickup, $dropoff)) {
            $candidates[] = $modes->get(ModeCode::Area->value);
        }

        if ($modes->has(ModeCode::Zone->value) && $distanceKm->isLessThanOrEqualTo(config()->integer('tayari.orders.zone_max_distance_km'))) {
            $candidates[] = $modes->get(ModeCode::Zone->value);
        }

        if ($modes->has(ModeCode::LongDistance->value)) {
            $candidates[] = $modes->get(ModeCode::LongDistance->value);
        }

        return $candidates;
    }

    private function isInsideOneArea(GeoPoint $pickup, GeoPoint $dropoff): bool
    {
        return Area::query()
            ->where('is_active', true)
            ->get(['id', 'center_lat', 'center_lng', 'radius_km'])
            ->contains(function (Area $area) use ($pickup, $dropoff): bool {
                $center = new GeoPoint((string) $area->center_lat, (string) $area->center_lng);

                return Distance::straightLineKm($center, $pickup)->isLessThanOrEqualTo($area->radius_km)
                    && Distance::straightLineKm($center, $dropoff)->isLessThanOrEqualTo($area->radius_km);
            });
    }

    private function quote(Mode $mode, PricingRule $rule, BigDecimal $distanceKm, bool $isFood): ShippingQuote
    {
        $baseFare = BigDecimal::of($rule->base_fare);
        $pricePerKm = BigDecimal::of($rule->price_per_km);
        $minimumFare = BigDecimal::of($rule->minimum_fare);
        $foodExtra = $isFood && $rule->food_extra !== null ? BigDecimal::of($rule->food_extra) : BigDecimal::zero()->toScale(self::MONEY_SCALE);
        $sharePercent = BigDecimal::of($rule->tayar_share_percent);

        $distanceFare = $pricePerKm->multipliedBy($distanceKm)->toScale(self::MONEY_SCALE, RoundingMode::HalfUp);
        $shippingFee = BigDecimal::max($minimumFare, $baseFare->plus($distanceFare))->plus($foodExtra)->toScale(self::MONEY_SCALE);
        $tayarEarning = $shippingFee->multipliedBy($sharePercent)->dividedBy(100, self::MONEY_SCALE, RoundingMode::HalfUp);

        return new ShippingQuote(
            mode: $mode,
            distanceKm: $distanceKm,
            baseFare: $baseFare,
            pricePerKm: $pricePerKm,
            distanceFare: $distanceFare,
            minimumFare: $minimumFare,
            foodExtra: $foodExtra,
            shippingFee: $shippingFee,
            tayarSharePercent: $sharePercent,
            tayarEarning: $tayarEarning,
            platformFee: $shippingFee->minus($tayarEarning),
        );
    }
}
