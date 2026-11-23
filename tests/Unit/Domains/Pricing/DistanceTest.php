<?php

namespace Tests\Unit\Domains\Pricing;

use App\Domains\Pricing\Data\Distance;
use App\Domains\Pricing\Data\GeoPoint;
use Tests\TestCase;

class DistanceTest extends TestCase
{
    public function test_the_straight_line_from_cairo_to_alexandria_is_about_180_km(): void
    {
        $kilometres = Distance::straightLineKm(new GeoPoint('30.0444000', '31.2357000'), new GeoPoint('31.2001000', '29.9187000'));

        $this->assertSame('179.984', (string) $kilometres);
    }

    public function test_the_same_point_is_zero_km(): void
    {
        $point = new GeoPoint('30.0444000', '31.2357000');

        $this->assertSame('0.000', (string) Distance::straightLineKm($point, $point));
        $this->assertSame('0.00', (string) Distance::roadKm($point, $point));
    }

    public function test_the_road_distance_is_the_straight_line_times_the_configured_multiplier_to_two_decimals(): void
    {
        $from = new GeoPoint('30.0000000', '31.2000000');
        $to = new GeoPoint('30.0359729', '31.2000000');

        config(['tayari.orders.road_distance_multiplier' => '1.30']);
        $this->assertSame('5.20', (string) Distance::roadKm($from, $to));

        config(['tayari.orders.road_distance_multiplier' => '1.00']);
        $this->assertSame('4.00', (string) Distance::roadKm($from, $to));
    }

    public function test_the_road_distance_rounds_half_up(): void
    {
        config(['tayari.orders.road_distance_multiplier' => '1.25']);

        // 179.984 × 1.25 = 224.98
        $this->assertSame('224.98', (string) Distance::roadKm(new GeoPoint('30.0444000', '31.2357000'), new GeoPoint('31.2001000', '29.9187000')));

        // 1.000 × 1.005 = 1.005 → 1.01
        config(['tayari.orders.road_distance_multiplier' => '1.005']);
        $this->assertSame('1.01', (string) Distance::roadKm(new GeoPoint('30.0000000', '31.2000000'), new GeoPoint('30.0089932', '31.2000000')));
    }
}
