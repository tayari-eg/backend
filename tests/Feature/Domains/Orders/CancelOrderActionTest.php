<?php

namespace Tests\Feature\Domains\Orders;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Orders\Actions\CancelOrderAction;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
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
            ->reject(fn (OrderStatus $status): bool => $status === OrderStatus::LookingForTayar)
            ->mapWithKeys(fn (OrderStatus $status): array => [$status->value => [$status]])
            ->all();
    }

    #[DataProvider('statusesThatCannotBeCancelled')]
    public function test_only_an_order_looking_for_a_tayar_can_be_cancelled(OrderStatus $status): void
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

    public function test_the_status_is_read_again_under_the_lock_not_from_the_stale_model(): void
    {
        $order = Order::factory()->create();
        Order::query()->whereKey($order->id)->update(['status' => OrderStatus::Pending->value]);

        $this->expectException(ValidationException::class);

        app(CancelOrderAction::class)->handle($order, $order->sender()->firstOrFail(), OrderReason::factory()->create());
    }
}
