<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Area>
 */
class AreaFactory extends Factory
{
    /**
     * Define the model's default state: an active area around Cairo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ['ar' => 'منطقة '.$name, 'en' => ucfirst($name)],
            'city' => ['ar' => 'القاهرة', 'en' => 'Cairo'],
            'center_lat' => fake()->randomFloat(7, 29.9, 30.2),
            'center_lng' => fake()->randomFloat(7, 31.1, 31.5),
            'radius_km' => fake()->randomFloat(2, 1, 10),
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
