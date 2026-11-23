<?php

namespace Tests\Feature\Domains\Pricing;

use App\Domains\Catalog\Enums\ModeCode;
use App\Domains\Catalog\Models\Area;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Pricing\Actions\CalculateShippingFeeAction;
use App\Domains\Pricing\Data\GeoPoint;
use App\Domains\Pricing\Data\ShippingQuote;
use App\Domains\Pricing\Models\PricingRule;
use App\Domains\Tayars\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CalculateShippingFeeActionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Points due north of the origin, so the straight line is exactly the given km.
     */
    private const string ORIGIN_LAT = '30.0000000';

    private const string LNG = '31.2000000';

    private const array LAT_AT_KM = [
        1 => '30.0089932',
        4 => '30.0359729',
        20 => '30.1798643',
        1300 => '41.6911809',
    ];

    private Mode $zone;

    private Mode $longDistance;

    private Mode $area;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tayari.orders.road_distance_multiplier' => '1.00', 'tayari.orders.zone_max_distance_km' => 15, 'tayari.orders.max_distance_km' => 1200, 'tayari.orders.min_distance_km' => '0.1']);

        $this->zone = Mode::factory()->zone()->create();
        $this->longDistance = Mode::factory()->longDistance()->create();
        $this->area = Mode::factory()->area()->create();

        PricingRule::factory()->create(['mode_id' => $this->zone->id, 'base_fare' => '20.00', 'price_per_km' => '5.00', 'minimum_fare' => '30.00', 'food_extra' => '10.00', 'tayar_share_percent' => '80.00']);
        PricingRule::factory()->create(['mode_id' => $this->longDistance->id, 'base_fare' => '50.00', 'price_per_km' => '3.50', 'minimum_fare' => '80.00', 'food_extra' => null, 'tayar_share_percent' => '75.00']);
        PricingRule::factory()->create(['mode_id' => $this->area->id, 'base_fare' => '25.00', 'price_per_km' => '4.00', 'minimum_fare' => '35.00', 'food_extra' => '8.00', 'tayar_share_percent' => '80.00']);
    }

    private function quote(int $kilometres, bool $isFood = false): ShippingQuote
    {
        $parent = Category::factory()->state(['is_food' => $isFood])->create();
        $category = Category::factory()->state(['is_food' => $isFood])->childOf($parent)->create();

        return app(CalculateShippingFeeAction::class)->handle(
            new GeoPoint(self::ORIGIN_LAT, self::LNG),
            new GeoPoint(self::LAT_AT_KM[$kilometres], self::LNG),
            $category,
        );
    }

    /**
     * @param  callable(): mixed  $callback
     */
    private function assertRouteError(callable $callback, string $message): void
    {
        try {
            $callback();
            $this->fail('Expected a validation error on route.');
        } catch (ValidationException $exception) {
            $this->assertSame([$message], $exception->errors()['route']);
        }
    }

    public function test_a_short_trip_uses_the_zone_rule_with_exact_decimals(): void
    {
        $quote = $this->quote(4);

        $this->assertSame(ModeCode::Zone, $quote->mode->code);
        $this->assertSame([
            'mode_id' => $this->zone->id,
            'distance_km' => '4.00',
            'base_fare' => '20.00',
            'price_per_km' => '5.00',
            'distance_fare' => '20.00',
            'minimum_fare' => '30.00',
            'food_extra' => '0.00',
            'shipping_fee' => '40.00',
            'tayar_share_percent' => '80.00',
            'tayar_earning' => '32.00',
            'platform_fee' => '8.00',
        ], $quote->toOrderAttributes());
    }

    public function test_the_minimum_fare_applies_when_the_trip_is_cheaper(): void
    {
        $quote = $this->quote(1);

        $this->assertSame('5.00', (string) $quote->distanceFare);
        $this->assertSame('30.00', (string) $quote->shippingFee);
    }

    public function test_food_adds_the_food_extra_after_the_minimum(): void
    {
        $this->assertSame('50.00', (string) $this->quote(4, isFood: true)->shippingFee);
        $this->assertSame('40.00', (string) $this->quote(1, isFood: true)->shippingFee);
    }

    public function test_the_tayar_earning_rounds_half_up_and_the_platform_fee_takes_the_rest(): void
    {
        PricingRule::query()->where('mode_id', $this->zone->id)->update(['base_fare' => '40.20', 'price_per_km' => '0.00', 'minimum_fare' => '0.00', 'tayar_share_percent' => '12.50']);

        $quote = $this->quote(4);

        // 40.20 × 12.5% = 5.025
        $this->assertSame('40.20', (string) $quote->shippingFee);
        $this->assertSame('5.03', (string) $quote->tayarEarning);
        $this->assertSame('35.17', (string) $quote->platformFee);
    }

    public function test_a_trip_longer_than_the_zone_limit_uses_long_distance(): void
    {
        $quote = $this->quote(20);

        $this->assertSame(ModeCode::LongDistance, $quote->mode->code);
        $this->assertSame('120.00', (string) $quote->shippingFee);
        $this->assertSame('90.00', (string) $quote->tayarEarning);
        $this->assertSame('30.00', (string) $quote->platformFee);
    }

    public function test_food_too_far_for_a_food_mode_is_refused(): void
    {
        $this->assertRouteError(fn () => $this->quote(20, isFood: true), __('orders.food_too_far'));
    }

    public function test_a_trip_inside_one_active_area_uses_the_area_rule(): void
    {
        Area::factory()->create(['center_lat' => '30.0180000', 'center_lng' => self::LNG, 'radius_km' => '3.00']);

        $quote = $this->quote(4);

        $this->assertSame(ModeCode::Area, $quote->mode->code);
        $this->assertSame('41.00', (string) $quote->shippingFee);
    }

    public function test_an_area_is_skipped_when_a_point_is_outside_it_or_it_is_inactive(): void
    {
        Area::factory()->create(['center_lat' => self::ORIGIN_LAT, 'center_lng' => self::LNG, 'radius_km' => '2.00']);
        Area::factory()->inactive()->create(['center_lat' => '30.0180000', 'center_lng' => self::LNG, 'radius_km' => '3.00']);

        $this->assertSame(ModeCode::Zone, $this->quote(4)->mode->code);
    }

    public function test_an_inactive_mode_or_a_mode_without_an_active_all_vehicles_rule_is_skipped(): void
    {
        $this->zone->update(['is_active' => false]);
        $this->assertSame(ModeCode::LongDistance, $this->quote(4)->mode->code);

        $this->zone->update(['is_active' => true]);
        PricingRule::query()->where('mode_id', $this->zone->id)->update(['is_active' => false]);
        PricingRule::factory()->forVehicleType(VehicleType::factory()->create())->create(['mode_id' => $this->zone->id]);
        $this->assertSame(ModeCode::LongDistance, $this->quote(4)->mode->code);
    }

    public function test_a_vehicle_type_rule_never_prices_an_order(): void
    {
        PricingRule::factory()->forVehicleType(VehicleType::factory()->create())->create(['mode_id' => $this->zone->id, 'base_fare' => '1.00', 'price_per_km' => '1.00', 'minimum_fare' => '1.00']);

        $this->assertSame('40.00', (string) $this->quote(4)->shippingFee);
    }

    public function test_a_trip_longer_than_the_maximum_is_refused(): void
    {
        $this->assertRouteError(fn () => $this->quote(1300), __('orders.route_too_long', ['max' => 1200]));
    }

    public function test_points_closer_than_the_minimum_in_a_straight_line_are_refused(): void
    {
        $category = Category::factory()->childOf(Category::factory()->create())->create();
        $pickup = new GeoPoint(self::ORIGIN_LAT, self::LNG);
        $calculate = fn (string $dropoffLat) => app(CalculateShippingFeeAction::class)->handle($pickup, new GeoPoint($dropoffLat, self::LNG), $category);

        // 50 m apart, then the same point.
        $this->assertRouteError(fn () => $calculate('30.0004497'), __('orders.route_too_short', ['min' => '100']));
        $this->assertRouteError(fn () => $calculate(self::ORIGIN_LAT), __('orders.route_too_short', ['min' => '100']));
        $this->assertSame('1.00', (string) $calculate(self::LAT_AT_KM[1])->distanceKm);

        config(['tayari.orders.min_distance_km' => '2']);
        $this->assertRouteError(fn () => $calculate(self::LAT_AT_KM[1]), __('orders.route_too_short', ['min' => '2000']));
    }

    public function test_no_usable_mode_is_refused(): void
    {
        Mode::query()->update(['is_active' => false]);

        $this->assertRouteError(fn () => $this->quote(4), __('orders.route_unavailable'));
    }
}
