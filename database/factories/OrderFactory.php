<?php

namespace Database\Factories;

use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Catalog\Enums\ModeCode;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Enums\ShippingPaidBy;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderReason;
use App\Domains\Tayars\Models\TayarProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Amounts are fixed and consistent: a 4 km zone trip at 20 + 5 × 4 = 40.00, 80% to the tayar.
 *
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state: a non-food order looking for a tayar, from an approved business.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Order::newCode(),
            'tracking_token' => Order::newTrackingToken(),
            'sender_id' => fn (): int => BusinessProfile::factory()->approved()->create()->user_id,
            'idempotency_key' => (string) Str::uuid(),
            'category_id' => fn (): int => Category::factory()->childOf(Category::factory()->create())->create()->id,
            'mode_id' => fn (): int => Mode::query()->where('code', ModeCode::Zone)->value('id') ?? Mode::factory()->zone()->create()->id,
            'details' => fake()->sentence(),
            'pickup_address' => fake()->streetAddress(),
            'pickup_lat' => '30.0444000',
            'pickup_lng' => '31.2357000',
            'pickup_contact_phone' => null,
            'dropoff_address' => fake()->streetAddress(),
            'dropoff_lat' => '30.0600000',
            'dropoff_lng' => '31.2500000',
            'receiver_name' => fake()->name(),
            'receiver_phone' => '+2010'.fake()->unique()->numerify('########'),
            'receiver_user_id' => null,
            'goods_value' => '150.00',
            'shipping_paid_by' => ShippingPaidBy::Sender,
            'amount_to_collect' => '150.00',
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
            'status' => OrderStatus::LookingForTayar,
            'price_accepted_at' => now(),
        ];
    }

    public function forSender(User $sender): static
    {
        return $this->state(fn (array $attributes) => [
            'sender_id' => $sender->id,
        ]);
    }

    public function food(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => fn (): int => Category::factory()->food()->childOf(Category::factory()->food()->create())->create()->id,
            'food_extra' => '10.00',
            'shipping_fee' => '50.00',
            'tayar_earning' => '40.00',
            'platform_fee' => '10.00',
        ]);
    }

    public function paidByReceiver(): static
    {
        return $this->state(fn (array $attributes) => [
            'shipping_paid_by' => ShippingPaidBy::Receiver,
            'amount_to_collect' => '190.00',
        ]);
    }

    public function cancelled(?User $by = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $by?->id ?? fn (array $attributes): int => $attributes['sender_id'],
            'cancel_reason_id' => OrderReason::factory(),
        ]);
    }

    /**
     * Accepted (pending) by an online tayar, in the tayar's current work session.
     */
    public function acceptedBy(TayarProfile $profile): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Pending,
            'tayar_id' => $profile->user_id,
            'work_session_id' => $profile->current_work_session_id,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Picked up by the tayar (no codes yet: run SendReceiverCodes for them).
     */
    public function pickedUpBy(TayarProfile $profile): static
    {
        return $this->acceptedBy($profile)->state(fn (array $attributes) => [
            'status' => OrderStatus::PickedUp,
            'picked_up_at' => now(),
        ]);
    }

    /**
     * On the way: picked up and the receiver confirmed (no codes yet: run SendReceiverCodes for them).
     */
    public function onTheWayWith(TayarProfile $profile): static
    {
        return $this->pickedUpBy($profile)->state(fn (array $attributes) => [
            'status' => OrderStatus::Ongoing,
            'receiver_confirmed_at' => now(),
        ]);
    }

    public function withStatus(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
