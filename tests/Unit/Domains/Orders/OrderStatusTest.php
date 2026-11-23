<?php

namespace Tests\Unit\Domains\Orders;

use App\Domains\Orders\Enums\OrderCanceller;
use App\Domains\Orders\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_the_full_lifecycle_from_the_design_is_listed(): void
    {
        $this->assertSame(
            ['looking_for_tayar', 'pending', 'picked_up', 'ongoing', 'delivered', 'not_delivered', 'cancelled'],
            OrderStatus::values(),
        );
    }

    public function test_only_the_designs_transitions_are_allowed(): void
    {
        $allowed = [
            'looking_for_tayar → pending', 'looking_for_tayar → cancelled',
            'pending → picked_up', 'pending → cancelled',
            'picked_up → ongoing', 'picked_up → not_delivered',
            'ongoing → delivered', 'ongoing → not_delivered',
        ];

        foreach (OrderStatus::cases() as $from) {
            foreach (OrderStatus::cases() as $to) {
                $transition = "{$from->value} → {$to->value}";

                $this->assertSame(in_array($transition, $allowed, true), $from->canTransitionTo($to), $transition);
            }
        }
    }

    public function test_senders_and_staff_cancel_until_pickup_and_the_system_only_before_a_tayar_accepts(): void
    {
        $beforePickup = [OrderStatus::LookingForTayar, OrderStatus::Pending];

        foreach (OrderStatus::cases() as $status) {
            $this->assertSame(in_array($status, $beforePickup, true), $status->canBeCancelledBy(OrderCanceller::Sender), $status->value);
            $this->assertSame(in_array($status, $beforePickup, true), $status->canBeCancelledBy(OrderCanceller::Staff), $status->value);
            $this->assertSame($status === OrderStatus::LookingForTayar, $status->canBeCancelledBy(OrderCanceller::System), $status->value);
        }
    }

    public function test_the_tayar_never_skips_a_step_and_only_staff_close_a_picked_up_order_as_not_delivered(): void
    {
        $tayarSteps = ['pending → picked_up', 'picked_up → ongoing', 'ongoing → delivered', 'ongoing → not_delivered'];

        foreach (OrderStatus::cases() as $from) {
            foreach (OrderStatus::cases() as $to) {
                $transition = "{$from->value} → {$to->value}";

                $this->assertSame(in_array($transition, $tayarSteps, true), $from->tayarCanMoveTo($to), $transition);
            }

            $this->assertSame(in_array($from, [OrderStatus::PickedUp, OrderStatus::Ongoing], true), $from->staffCanCloseAsNotDelivered(), $from->value);
            $this->assertSame(in_array($from, [OrderStatus::PickedUp, OrderStatus::Ongoing], true), $from->hasReceiverCodes(), $from->value);
        }
    }

    public function test_active_and_past_split_every_status_once(): void
    {
        $this->assertSame([OrderStatus::LookingForTayar, OrderStatus::Pending, OrderStatus::PickedUp, OrderStatus::Ongoing], OrderStatus::activeCases());
        $this->assertSame([OrderStatus::Delivered, OrderStatus::NotDelivered, OrderStatus::Cancelled], OrderStatus::pastCases());
        $this->assertEqualsCanonicalizing(OrderStatus::cases(), [...OrderStatus::activeCases(), ...OrderStatus::pastCases()]);
        $this->assertTrue(OrderStatus::Ongoing->isActive());
        $this->assertFalse(OrderStatus::Cancelled->isActive());
    }
}
