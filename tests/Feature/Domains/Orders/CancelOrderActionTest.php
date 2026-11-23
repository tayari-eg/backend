<?php

namespace Tests\Feature\Domains\Orders;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Orders\Actions\CancelOrderAction;
use App\Domains\Orders\Enums\OfferStatus;
use App\Domains\Orders\Enums\OrderCanceller;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderOffer;
use App\Domains\Orders\Models\OrderReason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CancelOrderActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_sender_cancels_with_a_reason_and_a_history_row_names_them(): void
    {
        $order = Order::factory()->create();
        $reason = OrderReason::factory()->create();

        $cancelled = app(CancelOrderAction::class)->handle($order, $order->sender()->firstOrFail(), $reason, 'Changed plans.');

        $this->assertSame(OrderStatus::Cancelled, $cancelled->status);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Cancelled->value,
            'cancelled_by' => $order->sender_id,
            'cancel_reason_id' => $reason->id,
        ]);
        $this->assertNotNull($cancelled->cancelled_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::LookingForTayar->value,
            'to_status' => OrderStatus::Cancelled->value,
            'changed_by' => $order->sender_id,
            'note' => 'Changed plans.',
        ]);
    }

    public function test_staff_cancel_is_recorded_as_the_staff_member(): void
    {
        $order = Order::factory()->create();
        $staff = $this->createStaffUser(StaffRole::Ops);

        app(CancelOrderAction::class)->handle($order, $staff, OrderReason::factory()->create());

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'cancelled_by' => $staff->id]);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'changed_by' => $staff->id, 'note' => null]);
    }

    /**
     * @return array<string, array{OrderStatus}>
     */
    public static function statusesThatCannotBeCancelled(): array
    {
        return collect(OrderStatus::cases())
            ->reject(fn (OrderStatus $status): bool => in_array($status, [OrderStatus::LookingForTayar, OrderStatus::Pending], true))
            ->mapWithKeys(fn (OrderStatus $status): array => [$status->value => [$status]])
            ->all();
    }

    #[DataProvider('statusesThatCannotBeCancelled')]
    public function test_the_sender_can_cancel_only_before_pickup(OrderStatus $status): void
    {
        $order = Order::factory()->withStatus($status)->create();

        try {
            app(CancelOrderAction::class)->handle($order, $order->sender()->firstOrFail(), OrderReason::factory()->create());
            $this->fail('Expected the cancel to be refused.');
        } catch (ValidationException $exception) {
            $this->assertSame([__('orders.cannot_cancel')], $exception->errors()['status']);
        }

        $this->assertSame($status, $order->refresh()->status);
        $this->assertDatabaseCount('order_status_histories', 0);
    }

    public function test_staff_cancel_an_accepted_order_and_it_keeps_its_tayar(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $order = Order::factory()->acceptedBy($tayar)->create();
        $staff = $this->createStaffUser(StaffRole::Ops);

        app(CancelOrderAction::class)->handle($order, $staff, OrderReason::factory()->create(), 'The tayar stopped answering.');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Cancelled->value,
            'cancelled_by' => $staff->id,
            'tayar_id' => $tayar->user_id,
            'work_session_id' => $tayar->current_work_session_id,
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::Pending->value,
            'to_status' => OrderStatus::Cancelled->value,
            'changed_by' => $staff->id,
            'note' => 'The tayar stopped answering.',
        ]);
    }

    public function test_the_sender_cancels_an_accepted_order_for_free_up_to_the_last_second_of_the_free_period(): void
    {
        $this->freezeSecond();
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $order = Order::factory()->acceptedBy($tayar)->create();

        $this->travel(config()->integer('tayari.matching.free_cancel_seconds'))->seconds();
        app(CancelOrderAction::class)->handle($order, $order->sender()->firstOrFail(), OrderReason::factory()->create());

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Cancelled->value,
            'cancel_fee' => '0.00',
            'cancel_fee_owed_to_tayar_id' => null,
            'tayar_id' => $tayar->user_id,
        ]);
        $this->assertSame(OrderCanceller::Sender, $order->refresh()->cancelledByType());
    }

    public function test_after_the_free_period_the_sender_owes_the_tayar_the_cancel_fee_capped_at_the_shipping_fee(): void
    {
        config(['tayari.matching.cancel_fee' => '20.00']);
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $order = Order::factory()->acceptedBy($tayar)->create();
        $cheap = Order::factory()->acceptedBy($tayar)->create(['shipping_fee' => '15.50']);

        $this->travel(config()->integer('tayari.matching.free_cancel_seconds') + 1)->seconds();
        app(CancelOrderAction::class)->handle($order, $order->sender()->firstOrFail(), OrderReason::factory()->create());
        app(CancelOrderAction::class)->handle($cheap, $cheap->sender()->firstOrFail(), OrderReason::factory()->create());

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'cancel_fee' => '20.00', 'cancel_fee_owed_to_tayar_id' => $tayar->user_id]);
        $this->assertDatabaseHas('orders', ['id' => $cheap->id, 'cancel_fee' => '15.50', 'cancel_fee_owed_to_tayar_id' => $tayar->user_id]);
    }

    public function test_staff_never_cause_a_cancel_fee(): void
    {
        $order = Order::factory()->acceptedBy($this->onlineTayar(30.0450, 31.2360))->create(['accepted_at' => now()->subHour()]);

        app(CancelOrderAction::class)->handle($order, $this->createStaffUser(StaffRole::Ops), OrderReason::factory()->create());

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Cancelled->value, 'cancel_fee' => '0.00', 'cancel_fee_owed_to_tayar_id' => null]);
    }

    public function test_the_fee_is_decided_on_the_locked_row_not_on_the_model_the_caller_holds(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        // The caller still holds the order as just accepted (free), but it was accepted an hour ago.
        $order = Order::factory()->acceptedBy($tayar)->create();
        Order::query()->whereKey($order->id)->update(['accepted_at' => now()->subHour()]);
        $this->assertSame('0.00', (string) $order->senderCancelFeeAt(now()));

        app(CancelOrderAction::class)->handle($order, $order->sender()->firstOrFail(), OrderReason::factory()->create());

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'cancel_fee' => '20.00', 'cancel_fee_owed_to_tayar_id' => $tayar->user_id]);
    }

    public function test_cancelling_an_order_still_looking_for_a_tayar_is_free(): void
    {
        $order = Order::factory()->create(['price_accepted_at' => now()->subHour()]);

        app(CancelOrderAction::class)->handle($order, $order->sender()->firstOrFail(), OrderReason::factory()->create());

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Cancelled->value, 'cancel_fee' => '0.00']);
    }

    public function test_staff_cannot_cancel_once_the_order_is_picked_up(): void
    {
        $order = Order::factory()->withStatus(OrderStatus::PickedUp)->create();

        $this->expectException(ValidationException::class);

        app(CancelOrderAction::class)->handle($order, $this->createStaffUser(StaffRole::Ops), OrderReason::factory()->create());
    }

    public function test_the_system_cancels_without_an_actor_or_a_reason_and_open_offers_are_withdrawn(): void
    {
        $order = Order::factory()->create();
        $offer = OrderOffer::factory()->forTayar($this->onlineTayar(30.0450, 31.2360))->create(['order_id' => $order->id]);
        $rejected = OrderOffer::factory()->forTayar($this->onlineTayar(30.0460, 31.2370))->withStatus(OfferStatus::Rejected)->create(['order_id' => $order->id]);

        app(CancelOrderAction::class)->handle($order, null, null);

        $this->assertSame(OrderCanceller::System, $order->refresh()->cancelledByType());
        $this->assertNull($order->cancel_reason_id);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'to_status' => OrderStatus::Cancelled->value, 'changed_by' => null]);
        $this->assertSame(OfferStatus::Withdrawn, $offer->refresh()->status);
        $this->assertSame(OfferStatus::Rejected, $rejected->refresh()->status);
    }

    public function test_the_status_is_read_again_under_the_lock_not_from_the_stale_model(): void
    {
        $order = Order::factory()->create();
        Order::query()->whereKey($order->id)->update(['status' => OrderStatus::PickedUp->value]);

        $this->expectException(ValidationException::class);

        app(CancelOrderAction::class)->handle($order, $order->sender()->firstOrFail(), OrderReason::factory()->create());
    }
}
