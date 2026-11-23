<?php

namespace App\Domains\Pricing\Data;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Straight-line (haversine) distances. Only the trigonometry uses floats; results leave as BigDecimal.
 */
final class Distance
{
    private const int EARTH_RADIUS_KM = 6371;

    /**
     * Kilometres to three decimals, so the road distance rounds from a precise value.
     */
    public static function straightLineKm(GeoPoint $from, GeoPoint $to): BigDecimal
    {
        $fromLat = deg2rad((float) $from->lat);
        $toLat = deg2rad((float) $to->lat);
        $deltaLat = $toLat - $fromLat;
        $deltaLng = deg2rad((float) $to->lng - (float) $from->lng);

        $a = sin($deltaLat / 2) ** 2 + cos($fromLat) * cos($toLat) * sin($deltaLng / 2) ** 2;
        $kilometres = 2 * self::EARTH_RADIUS_KM * asin(min(1.0, sqrt($a)));

        return BigDecimal::of(number_format($kilometres, 3, '.', ''));
    }

    /**
     * The straight line times config('tayari.orders.road_distance_multiplier'), to two decimals. A maps API replaces
     * this later without changing callers.
     */
    public static function roadKm(GeoPoint $from, GeoPoint $to): BigDecimal
    {
        return self::straightLineKm($from, $to)
            ->multipliedBy(BigDecimal::of((string) config('tayari.orders.road_distance_multiplier')))
            ->toScale(2, RoundingMode::HalfUp);
    }
}
