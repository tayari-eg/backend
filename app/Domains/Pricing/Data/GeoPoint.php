<?php

namespace App\Domains\Pricing\Data;

/**
 * A latitude and longitude as decimal strings (as validated and stored), never rounded through a float.
 */
final readonly class GeoPoint
{
    public function __construct(public string $lat, public string $lng) {}
}
