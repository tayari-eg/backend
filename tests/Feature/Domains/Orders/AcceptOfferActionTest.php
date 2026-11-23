<?php

namespace Tests\Feature\Domains\Orders;

use App\Domains\Catalog\Models\Mode;
use App\Domains\Orders\Actions\AcceptOfferAction;
use App\Domains\Orders\Actions\CancelOrderAction;
use App\Domains\Orders\Actions\SweepOrderOffersAction;
use App\Domains\Orders\Enums\OfferStatus;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Events\OrderAccepted;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderOffer;
use App\Domains\Orders\Models\OrderReason;
use App\Domains\Tayars\Actions\GoOfflineAction;
use App\Domains\Tayars\Data\WorkSessionSettings;
use App\Domains\Tayars\Models\TayarProfile;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AcceptOfferActionTest extends TestCase
{
    use RefreshDatabase;

    private TayarProfile $tayar;

    private Order $order;

    private OrderOffer $offer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tayar = $this->onlineTayar(30.0450, 31.2360);
        $this->order = Order::factory()->create();
        $this->offer = OrderOffer::factory()->forTayar($this->tayar)->create([
            'order_id' => $this->order->id,
            'tayar_share_percent' => '90.00',
            'tayar_earning' => '36.00',
            'platform_fee' => '4.00',
        ]);
    }

    private function user(TayarProfile $profile): User
    {
        return User::query()->findOrFail($profile->user_id);
    }

    private function accept(?OrderOffer $offer = null, ?TayarProfile $tayar = null): Order
    {
        return app(AcceptOfferAction::class)->handle($offer ?? $this->offer, $this->user($tayar ?? $this->tayar));
    }

    private function assertRefused(string $messageKey, ?OrderOffer $offer = null, ?TayarProfile $tayar = null): void
    {
        try {
            $this->accept($offer, $tayar);
            $this->fail('Expected the accept to be refused.');
        } catch (ValidationException $exception) {
            $this->assertSame([__("orders.{$messageKey}")], $exception->errors()['offer']);
        }
    }

    public function test_the_tayar_gets_the_order_with_the_earning_from_the_offer_and_the_other_offers_are_withdrawn(): void
    {
        Event::fake([OrderAccepted::class]);
        $other = OrderOffer::factory()->create(['order_id' => $this->order->id]);

        $order = $this->accept();

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => OrderStatus::Pending->value,
            'tayar_id' => $this->tayar->user_id,
            'work_session_id' => $this->tayar->current_work_session_id,
            'shipping_fee' => '40.00',
            'tayar_share_percent' => '90.00',
            'tayar_earning' => '36.00',
            'platform_fee' => '4.00',
        ]);
        $this->assertNotNull($order->accepted_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $this->order->id,
            'from_status' => OrderStatus::LookingForTayar->value,
            'to_status' => OrderStatus::Pending->value,
            'changed_by' => $this->tayar->user_id,
        ]);
        $this->assertSame(OfferStatus::Accepted, $this->offer->refresh()->status);
        $this->assertNotNull($this->offer->responded_at);
        $this->assertSame(OfferStatus::Withdrawn, $other->refresh()->status);
        Event::assertDispatched(OrderAccepted::class, fn (OrderAccepted $event): bool => $event->order->is($this->order));
    }

    public function test_a_second_tap_returns_the_same_order(): void
    {
        $this->accept();

        $order = $this->accept();

        $this->assertSame($this->tayar->user_id, $order->tayar_id);
        $this->assertDatabaseCount('order_status_histories', 1);
    }

    public function test_an_offer_past_its_time_is_refused_and_recorded_as_expired_even_before_the_sweep(): void
    {
        $this->travel(61)->seconds();
        $this->assertSame(OfferStatus::Offered, $this->offer->refresh()->status);

        $this->assertRefused('offer_expired');

        $this->assertSame(OfferStatus::Expired, $this->offer->refresh()->status);
        $this->assertSame(OrderStatus::LookingForTayar, $this->order->refresh()->status);
    }

    public function test_cancel_first_then_accept_the_accept_is_refused(): void
    {
        app(CancelOrderAction::class)->handle($this->order, $this->order->sender()->firstOrFail(), OrderReason::factory()->create());

        $this->assertRefused('offer_unavailable');

        $this->assertSame(OrderStatus::Cancelled, $this->order->refresh()->status);
        $this->assertNull($this->order->tayar_id);
        $this->assertSame(OfferStatus::Withdrawn, $this->offer->refresh()->status);
    }

    public function test_accept_first_then_cancel_by_the_sender_cancels_the_accepted_order_and_keeps_its_tayar(): void
    {
        $this->accept();

        app(CancelOrderAction::class)->handle($this->order, $this->order->sender()->firstOrFail(), OrderReason::factory()->create());

        $this->assertSame(OrderStatus::Cancelled, $this->order->refresh()->status);
        $this->assertSame($this->tayar->user_id, $this->order->tayar_id);
        $this->assertSame('0.00', $this->order->cancel_fee);
    }

    public function test_the_order_taken_by_another_tayar_is_no_longer_available(): void
    {
        $other = $this->onlineTayar(30.0460, 31.2370);
        $otherOffer = OrderOffer::factory()->forTayar($other)->create(['order_id' => $this->order->id]);

        $this->accept($otherOffer, $other);

        $this->assertRefused('offer_unavailable');
        $this->assertSame($other->user_id, $this->order->refresh()->tayar_id);
    }

    public function test_an_offer_for_a_work_session_that_ended_is_refused(): void
    {
        app(GoOfflineAction::class)->handle($this->tayar);

        $this->assertRefused('offer_session_changed');
        $this->assertSame(OrderStatus::LookingForTayar, $this->order->refresh()->status);
    }

    public function test_a_tayar_at_the_limit_of_the_mode_is_refused(): void
    {
        Order::factory()->acceptedBy($this->tayar)->create();

        $this->assertRefused('tayar_busy');
        $this->assertSame(OfferStatus::Offered, $this->offer->refresh()->status);
    }

    public function test_reaching_the_limit_withdraws_the_tayars_other_offers_and_the_next_sweep_offers_that_order_to_others(): void
    {
        $orderB = Order::factory()->create();
        $offerOnB = OrderOffer::factory()->forTayar($this->tayar)->create(['order_id' => $orderB->id]);
        $other = $this->onlineTayar(30.0460, 31.2370);

        $this->accept();

        $this->assertSame(OfferStatus::Withdrawn, $offerOnB->refresh()->status);

        app(SweepOrderOffersAction::class)->handle();

        $this->assertSame(
            [[$other->user_id, 2]],
            OrderOffer::query()->where('order_id', $orderB->id)->where('status', OfferStatus::Offered)->get()->map(fn (OrderOffer $offer): array => [$offer->tayar_id, $offer->round])->all(),
        );
    }

    public function test_a_long_distance_tayar_below_the_limit_keeps_the_other_offers(): void
    {
        $longDistance = Mode::factory()->longDistance()->create();
        $tayar = $this->onlineTayar(30.0450, 31.2360, new WorkSessionSettings($longDistance, destinationAddress: 'Alexandria', destinationLat: '31.2100000', destinationLng: '29.9300000'));
        $orderA = Order::factory()->create(['mode_id' => $longDistance->id]);
        $orderB = Order::factory()->create(['mode_id' => $longDistance->id]);
        $offerOnA = OrderOffer::factory()->forTayar($tayar)->create(['order_id' => $orderA->id]);
        $offerOnB = OrderOffer::factory()->forTayar($tayar)->create(['order_id' => $orderB->id]);

        $this->accept($offerOnA, $tayar);

        $this->assertSame(OfferStatus::Offered, $offerOnB->refresh()->status);
    }

    public function test_a_second_tap_after_the_order_was_cancelled_is_no_longer_available(): void
    {
        $this->accept();
        app(CancelOrderAction::class)->handle($this->order, $this->order->sender()->firstOrFail(), OrderReason::factory()->create());

        $this->assertRefused('offer_unavailable');
        $this->assertSame(OfferStatus::Accepted, $this->offer->refresh()->status);
    }

    public function test_it_locks_the_order_then_the_tayar_profile_then_the_offer(): void
    {
        $lockedTables = [];
        DB::listen(function (QueryExecuted $query) use (&$lockedTables): void {
            if (str_contains($query->sql, 'for update') && preg_match('/from [`"]?(\w+)[`"]?/', $query->sql, $match) === 1) {
                $lockedTables[] = $match[1];
            }
        });

        $this->accept();

        $this->assertSame(['orders', 'tayar_profiles', 'order_offers'], $lockedTables);
    }
}
