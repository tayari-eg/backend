<?php

namespace Database\Factories;

use App\Domains\Incentives\Models\TayarRank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TayarRank>
 */
class TayarRankFactory extends Factory
{
    /**
     * Define the model's default state: a rank above every seeded level that needs nothing.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'code' => 'rank_'.fake()->unique()->numberBetween(1, 99999),
            'name' => ['ar' => 'رتبة '.$name, 'en' => ucfirst($name)],
            'level' => fake()->unique()->numberBetween(10, 250),
            'min_delivered_orders_30d' => 0,
            'min_rating' => null,
            'color' => '#607D8B',
        ];
    }

    /**
     * Needs the delivered orders and, when given, the average rating over the last 30 days.
     */
    public function requiring(int $level, int $deliveredOrders, ?string $rating = null): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => $level,
            'min_delivered_orders_30d' => $deliveredOrders,
            'min_rating' => $rating,
        ]);
    }
}
