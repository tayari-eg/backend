<?php

namespace App\Domains\Tayars\Data;

/**
 * The lat/lng box around a point that contains every point within a radius. The box corners are farther than the
 * radius, so callers still filter by the real distance; the box only lets the index narrow the rows first.
 */
final readonly class BoundingBox
{
    private const float KM_PER_DEGREE_LAT = 111.32;

    private function __construct(
        public float $minLat,
        public float $maxLat,
        public float $minLng,
        public float $maxLng,
    ) {}

    public static function around(float $lat, float $lng, float $radiusKm): self
    {
        $deltaLat = $radiusKm / self::KM_PER_DEGREE_LAT;
        $cosLat = cos(deg2rad($lat));

        // Near a pole one degree of longitude is almost no distance, so the box spans every longitude.
        $deltaLng = $cosLat > 0.000001 ? $radiusKm / (self::KM_PER_DEGREE_LAT * $cosLat) : 180.0;

        return new self(
            max(-90.0, $lat - $deltaLat),
            min(90.0, $lat + $deltaLat),
            max(-180.0, $lng - $deltaLng),
            min(180.0, $lng + $deltaLng),
        );
    }
}
