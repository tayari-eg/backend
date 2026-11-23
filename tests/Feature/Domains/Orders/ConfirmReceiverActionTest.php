<?php

namespace Tests\Feature\Domains\Orders;

use App\Domains\Orders\Actions\ConfirmReceiverAction;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ConfirmReceiverActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_after_the_call_the_picked_up_order_is_on_the_way(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $order = Order::factory()->pickedUpBy($tayar)->create();

        $ongoing = app(ConfirmReceiverAction::class)->handle($order, User::query()->findOrFail($tayar->user_id), 30.05, 31.24);

        $this->assertSame(OrderStatus::Ongoing, $ongoing->status);
        $this->assertNotNull($ongoing->receiver_confirmed_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::PickedUp->value,
            'to_status' => OrderStatus::Ongoing->value,
            'changed_by' => $tayar->user_id,
            'lat' => '30.0500000',
        ]);
    }

    public function test_an_order_not_picked_up_yet_or_another_tayars_is_refused(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $user = User::query()->findOrFail($tayar->user_id);

        foreach ([Order::factory()->acceptedBy($tayar)->create(), Order::factory()->pickedUpBy($this->onlineTayar(30.0460, 31.2370))->create()] as $order) {
            try {
                app(ConfirmReceiverAction::class)->handle($order, $user, 30.05, 31.24);
                $this->fail('Expected the step to be refused.');
            } catch (ValidationException $exception) {
                $this->assertSame([__('orders.wrong_step')], $exception->errors()['status']);
            }

            $this->assertNotSame(OrderStatus::Ongoing, $order->refresh()->status);
        }
    }
}
