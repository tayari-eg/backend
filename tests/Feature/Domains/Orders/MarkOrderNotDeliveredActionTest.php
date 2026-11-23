<?php

namespace Tests\Feature\Domains\Orders;

use App\Domains\Orders\Actions\MarkOrderNotDeliveredAction;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderReason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MarkOrderNotDeliveredActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_refusal_code_marks_not_delivered_with_the_reason_and_note_and_the_delivery_code_does_not(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $user = User::query()->findOrFail($tayar->user_id);
        $order = Order::factory()->onTheWayWith($tayar)->create();
        $codes = $this->sendReceiverCodes($order);
        $reason = OrderReason::factory()->notDelivered()->create();
        $markNotDelivered = app(MarkOrderNotDeliveredAction::class);

        try {
            $markNotDelivered->handle($order, $user, $codes['delivery'], $reason, null, 30.06, 31.25);
            $this->fail('Expected the delivery code to be refused here.');
        } catch (ValidationException $exception) {
            $this->assertSame([__('orders.code_wrong', ['left' => config()->integer('tayari.tracking.code_max_attempts') - 1])], $exception->errors()['code']);
        }

        $notDelivered = $markNotDelivered->handle($order, $user, $codes['refusal'], $reason, 'Box was crushed.', 30.06, 31.25);

        $this->assertSame(OrderStatus::NotDelivered, $notDelivered->status);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'not_delivered_reason_id' => $reason->id,
            'not_delivered_note' => 'Box was crushed.',
            'delivery_code_hash' => null,
            'refusal_code_hash' => null,
        ]);
        $this->assertNotNull($notDelivered->not_delivered_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => OrderStatus::NotDelivered->value,
            'changed_by' => $tayar->user_id,
            'note' => 'Box was crushed.',
            'lat' => '30.0600000',
        ]);
    }

    public function test_the_tayar_cannot_mark_a_picked_up_order_as_not_delivered(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $order = Order::factory()->pickedUpBy($tayar)->create();
        $codes = $this->sendReceiverCodes($order);

        $this->expectException(ValidationException::class);

        app(MarkOrderNotDeliveredAction::class)->handle($order, User::query()->findOrFail($tayar->user_id), $codes['refusal'], OrderReason::factory()->notDelivered()->create(), null, 30.06, 31.25);
    }
}
