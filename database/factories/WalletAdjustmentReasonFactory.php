<?php

namespace Database\Factories;

use App\Domains\Wallet\Models\WalletAdjustmentReason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WalletAdjustmentReason>
 */
class WalletAdjustmentReasonFactory extends Factory
{
    /**
     * Define the model's default state: an active reason.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
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
}
