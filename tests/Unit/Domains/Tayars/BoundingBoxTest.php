<?php

namespace Tests\Unit\Domains\Tayars;

use App\Domains\Tayars\Data\BoundingBox;
use PHPUnit\Framework\TestCase;

class BoundingBoxTest extends TestCase
{
    public function test_the_box_around_cairo_spans_the_radius_in_each_direction(): void
    {
        $box = BoundingBox::around(30.0444, 31.2357, 10);

        // 10 km is about 0.0898° of latitude everywhere, and about 0.1038° of longitude at Cairo's latitude.
        $this->assertEqualsWithDelta(30.0444 - 0.0898, $box->minLat, 0.0001);
        $this->assertEqualsWithDelta(30.0444 + 0.0898, $box->maxLat, 0.0001);
        $this->assertEqualsWithDelta(31.2357 - 0.1038, $box->minLng, 0.0001);
        $this->assertEqualsWithDelta(31.2357 + 0.1038, $box->maxLng, 0.0001);
    }

    public function test_the_box_stays_inside_valid_coordinates(): void
    {
        $nearPole = BoundingBox::around(89.99, 179.99, 50);

        $this->assertSame(90.0, $nearPole->maxLat);
        $this->assertSame(180.0, $nearPole->maxLng);
        $this->assertGreaterThanOrEqual(-180.0, $nearPole->minLng);
    }
}
