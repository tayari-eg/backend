<?php

namespace Tests\Unit\Domains\Orders;

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

    public function test_only_looking_for_tayar_to_cancelled_is_allowed_for_now(): void
    {
        foreach (OrderStatus::cases() as $from) {
            foreach (OrderStatus::cases() as $to) {
                $this->assertSame(
                    $from === OrderStatus::LookingForTayar && $to === OrderStatus::Cancelled,
                    $from->canTransitionTo($to),
                    "{$from->value} → {$to->value}",
                );
            }
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
