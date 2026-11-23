<?php

namespace Database\Factories;

use App\Domains\Orders\Enums\OrderReasonType;
use App\Domains\Orders\Models\OrderReason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderReason>
 */
class OrderReasonFactory extends Factory
{
    /**
     * Define the model's default state: an active cancel reason.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'type' => OrderReasonType::Cancel,
            'name' => ['ar' => 'سبب '.$name, 'en' => ucfirst($name)],
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function notDelivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => OrderReasonType::NotDelivered,
        ]);
    }
}
