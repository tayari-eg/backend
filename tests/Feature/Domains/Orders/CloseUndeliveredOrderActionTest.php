<?php

namespace Tests\Feature\Domains\Orders;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Orders\Actions\CloseUndeliveredOrderAction;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderReason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CloseUndeliveredOrderActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_close_a_picked_up_and_an_ongoing_order_without_a_code(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $staff = $this->createStaffUser(StaffRole::Ops);
        $reason = OrderReason::factory()->notDelivered()->create();

        foreach ([Order::factory()->pickedUpBy($tayar)->create(), Order::factory()->onTheWayWith($tayar)->create()] as $order) {
            $from = $order->status;

            $closed = app(CloseUndeliveredOrderAction::class)->handle($order, $staff, $reason, 'Receiver unreachable after 3 calls.');

            $this->assertSame(OrderStatus::NotDelivered, $closed->status);
            $this->assertDatabaseHas('orders', ['id' => $order->id, 'not_delivered_reason_id' => $reason->id, 'not_delivered_note' => 'Receiver unreachable after 3 calls.', 'tayar_id' => $tayar->user_id]);
            $this->assertDatabaseHas('order_status_histories', [
                'order_id' => $order->id,
                'from_status' => $from->value,
                'to_status' => OrderStatus::NotDelivered->value,
                'changed_by' => $staff->id,
                'lat' => null,
                'lng' => null,
            ]);
        }

        $this->assertFalse($tayar->refresh()->holdsActiveOrders());
    }

    /**
     * @return array<string, array{OrderStatus}>
     */
    public static function statusesStaffCannotClose(): array
    {
        return collect(OrderStatus::cases())
            ->reject(fn (OrderStatus $status): bool => in_array($status, [OrderStatus::PickedUp, OrderStatus::Ongoing], true))
            ->mapWithKeys(fn (OrderStatus $status): array => [$status->value => [$status]])
            ->all();
    }

    #[DataProvider('statusesStaffCannotClose')]
    public function test_any_other_status_is_refused(OrderStatus $status): void
    {
        $order = Order::factory()->withStatus($status)->create();

        try {
            app(CloseUndeliveredOrderAction::class)->handle($order, $this->createStaffUser(StaffRole::Ops), OrderReason::factory()->notDelivered()->create(), 'Note.');
            $this->fail('Expected the close to be refused.');
        } catch (ValidationException $exception) {
            $this->assertSame([__('orders.cannot_close_not_delivered')], $exception->errors()['status']);
        }

        $this->assertSame($status, $order->refresh()->status);
    }
}
