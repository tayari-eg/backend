<?php

namespace Tests\Feature\Domains\Orders;

use App\Domains\Catalog\Models\Area;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Orders\Actions\DispatchOrderOffersAction;
use App\Domains\Orders\Enums\OfferStatus;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Events\OrderOffersCreated;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderOffer;
use App\Domains\Pricing\Models\PricingRule;
use App\Domains\Tayars\Data\WorkSessionSettings;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\TayarWorkSession;
use App\Domains\Tayars\Models\Vehicle;
use App\Domains\Tayars\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DispatchOrderOffersActionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Points due north of the OrderFactory pickup (30.0444, 31.2357), about the given km away. The drop-off
     * (30.0600, 31.2500) is within 5 km of each of them.
     */
    private const array AT_KM = [
        '0.1' => [30.0453, 31.2357],
        '1' => [30.0534, 31.2357],
        '2' => [30.0624, 31.2357],
        '4.5' => [30.0849, 31.2357],
        '12' => [30.1523, 31.2357],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config(['tayari.matching' => [
            'offer_timeout_seconds' => 60,
            'offers_per_round' => 5,
            'first_radius_km' => 3,
            'radius_step_km' => 3,
            'max_radius_km' => 10,
            'search_timeout_minutes' => 15,
            'destination_radius_km' => 20,
            'max_active_orders' => 1,
            'max_active_orders_long_distance' => 5,
        ]]);
    }

    private function tayarAt(string $km, ?WorkSessionSettings $settings = null): TayarProfile
    {
        return $this->onlineTayar(...[...self::AT_KM[$km], $settings]);
    }

    /**
     * @return list<OrderOffer>
     */
    private function dispatch(Order $order): array
    {
        return app(DispatchOrderOffersAction::class)->handle($order);
    }

    /**
     * @return list<int>
     */
    private function offeredTayarIds(Order $order): array
    {
        return OrderOffer::query()->where('order_id', $order->id)->where('status', OfferStatus::Offered)->orderBy('distance_km')->pluck('tayar_id')->all();
    }

    private function vehicleOf(TayarProfile $profile): Vehicle
    {
        return Vehicle::query()->findOrFail(TayarWorkSession::query()->whereKey($profile->current_work_session_id)->value('vehicle_id'));
    }

    public function test_the_nearest_eligible_tayars_get_live_offers_with_the_earning_for_their_vehicle(): void
    {
        $this->freezeSecond();
        Event::fake([OrderOffersCreated::class]);
        config(['tayari.matching.offers_per_round' => 2]);
        $order = Order::factory()->create();
        $second = $this->tayarAt('1');
        $nearest = $this->tayarAt('0.1');
        $this->tayarAt('2');
        PricingRule::factory()->forVehicleType(VehicleType::query()->findOrFail($this->vehicleOf($nearest)->vehicle_type_id))
            ->create(['mode_id' => $order->mode_id, 'tayar_share_percent' => '90.00']);

        $offers = $this->dispatch($order);

        $this->assertSame([$nearest->user_id, $second->user_id], array_map(fn (OrderOffer $offer): int => $offer->tayar_id, $offers));
        $this->assertDatabaseHas('order_offers', [
            'order_id' => $order->id,
            'tayar_id' => $nearest->user_id,
            'work_session_id' => $nearest->current_work_session_id,
            'round' => 1,
            'distance_km' => '0.10',
            'tayar_share_percent' => '90.00',
            'tayar_earning' => '36.00',
            'platform_fee' => '4.00',
            'status' => OfferStatus::Offered->value,
        ]);
        // No vehicle rule for the second tayar: the order's own share.
        $this->assertDatabaseHas('order_offers', ['tayar_id' => $second->user_id, 'tayar_earning' => '32.00', 'platform_fee' => '8.00']);
        $this->assertTrue($offers[0]->expires_at->equalTo(now()->addSeconds(60)));
        Event::assertDispatched(OrderOffersCreated::class, fn (OrderOffersCreated $event): bool => count($event->offers) === 2);
        $this->assertSame(OrderStatus::LookingForTayar, $order->refresh()->status);
    }

    public function test_tayars_who_do_not_fit_the_order_get_nothing(): void
    {
        $order = Order::factory()->food()->create();
        $eligible = $this->tayarAt('1');

        TayarProfile::factory()->approved()->create(['current_lat' => self::AT_KM['0.1'][0], 'current_lng' => self::AT_KM['0.1'][1]]);
        $this->tayarAt('0.1', new WorkSessionSettings(Mode::factory()->area()->create(), Area::factory()->create(['center_lat' => '30.0450000', 'center_lng' => '31.2400000', 'radius_km' => '5.00'])));
        $this->tayarAt('0.1', $this->zoneWorkSessionSettings('2'));
        $cannotCarryFood = $this->tayarAt('0.1');
        VehicleType::query()->whereKey($this->vehicleOf($cannotCarryFood)->vehicle_type_id)->update(['can_carry_food' => false]);
        $inactiveVehicle = $this->tayarAt('0.1');
        $this->vehicleOf($inactiveVehicle)->forceFill(['is_active' => false])->save();
        $busy = $this->tayarAt('0.1');
        Order::factory()->acceptedBy($busy)->create();
        $rejectedBefore = $this->tayarAt('0.1');
        OrderOffer::factory()->forTayar($rejectedBefore)->withStatus(OfferStatus::Rejected)->create(['order_id' => $order->id]);
        $this->tayarAt('12');

        $this->dispatch($order);

        $this->assertSame([$eligible->user_id], $this->offeredTayarIds($order));
    }

    public function test_the_sender_never_gets_their_own_order(): void
    {
        $tayar = $this->tayarAt('0.1');
        $order = Order::factory()->forSender($tayar->user()->firstOrFail())->create();

        $this->assertSame([], $this->dispatch($order));
    }

    public function test_an_area_order_goes_only_to_tayars_whose_own_area_holds_both_points(): void
    {
        $areaMode = Mode::factory()->area()->create();
        $order = Order::factory()->create(['mode_id' => $areaMode->id]);
        // Both areas hold the pickup; only the first also holds the drop-off.
        $holdsBoth = Area::factory()->create(['center_lat' => '30.0520000', 'center_lng' => '31.2430000', 'radius_km' => '3.00']);
        $holdsPickupOnly = Area::factory()->create(['center_lat' => '30.0400000', 'center_lng' => '31.2300000', 'radius_km' => '1.50']);
        $inside = $this->tayarAt('0.1', new WorkSessionSettings($areaMode, $holdsBoth));
        $this->tayarAt('1', new WorkSessionSettings($areaMode, $holdsPickupOnly));

        $this->dispatch($order);

        $this->assertSame([$inside->user_id], $this->offeredTayarIds($order));
    }

    public function test_a_long_distance_order_goes_to_tayars_heading_near_the_drop_off_even_with_other_orders(): void
    {
        $longDistance = Mode::factory()->longDistance()->create();
        // Cairo to Alexandria.
        $order = Order::factory()->create(['mode_id' => $longDistance->id, 'dropoff_lat' => '31.2001000', 'dropoff_lng' => '29.9187000']);
        $toAlexandria = $this->tayarAt('0.1', new WorkSessionSettings($longDistance, destinationAddress: 'Alexandria', destinationLat: '31.2100000', destinationLng: '29.9300000'));
        Order::factory()->acceptedBy($toAlexandria)->create(['mode_id' => $longDistance->id]);
        $this->tayarAt('1', new WorkSessionSettings($longDistance, destinationAddress: 'Suez', destinationLat: '29.9668000', destinationLng: '32.5498000'));

        $this->dispatch($order);

        $this->assertSame([$toAlexandria->user_id], $this->offeredTayarIds($order));
    }

    public function test_nothing_happens_while_an_offer_is_live(): void
    {
        $order = Order::factory()->create();
        OrderOffer::factory()->forTayar($this->tayarAt('1'))->create(['order_id' => $order->id]);
        $this->tayarAt('0.1');

        $this->assertSame([], $this->dispatch($order));
        $this->assertDatabaseCount('order_offers', 1);
    }

    public function test_overdue_offers_expire_and_the_next_round_searches_wider_and_offers_again_to_who_let_it_expire(): void
    {
        $order = Order::factory()->create();
        $near = $this->tayarAt('1');
        $farther = $this->tayarAt('4.5');

        $this->assertSame([$near->user_id], array_map(fn (OrderOffer $offer): int => $offer->tayar_id, $this->dispatch($order)));

        $this->travel(61)->seconds();
        $secondRound = $this->dispatch($order);

        $this->assertSame([[$near->user_id, 2], [$farther->user_id, 2]], array_map(fn (OrderOffer $offer): array => [$offer->tayar_id, $offer->round], $secondRound));
        $this->assertDatabaseHas('order_offers', ['tayar_id' => $near->user_id, 'round' => 1, 'status' => OfferStatus::Expired->value]);
    }

    public function test_a_round_with_nobody_nearby_widens_at_once_up_to_the_largest_radius(): void
    {
        $order = Order::factory()->create();
        $farther = $this->tayarAt('4.5');

        $offers = $this->dispatch($order);

        $this->assertSame([[$farther->user_id, 1]], array_map(fn (OrderOffer $offer): array => [$offer->tayar_id, $offer->round], $offers));
    }

    public function test_after_the_search_time_the_system_cancels_the_order(): void
    {
        $order = Order::factory()->create(['price_accepted_at' => now()->subMinutes(15)]);
        $overdue = OrderOffer::factory()->forTayar($this->tayarAt('1'))->overdue()->create(['order_id' => $order->id]);

        $this->assertSame([], $this->dispatch($order));

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Cancelled->value, 'cancelled_by' => null, 'cancel_reason_id' => null]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::LookingForTayar->value,
            'to_status' => OrderStatus::Cancelled->value,
            'changed_by' => null,
        ]);
        $this->assertSame(OfferStatus::Expired, $overdue->refresh()->status);
    }

    public function test_a_live_offer_holds_the_cancel_until_it_ends(): void
    {
        $order = Order::factory()->create(['price_accepted_at' => now()->subMinutes(20)]);
        OrderOffer::factory()->forTayar($this->tayarAt('1'))->create(['order_id' => $order->id]);

        $this->dispatch($order);

        $this->assertSame(OrderStatus::LookingForTayar, $order->refresh()->status);
    }

    public function test_an_order_that_is_no_longer_looking_for_a_tayar_is_left_alone(): void
    {
        $this->tayarAt('0.1');
        $cancelled = Order::factory()->cancelled()->create();
        $deleted = Order::factory()->create();
        $deleted->delete();

        $this->assertSame([], $this->dispatch($cancelled));
        $this->assertSame([], $this->dispatch($deleted));
        $this->assertDatabaseCount('order_offers', 0);
    }
}
