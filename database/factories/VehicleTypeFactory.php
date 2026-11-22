<?php

namespace Database\Factories;

use App\Domains\Tayars\Models\VehicleType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleType>
 */
class VehicleTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ['ar' => 'مركبة '.$name, 'en' => ucfirst($name)],
            'requires_license' => true,
            'can_carry_food' => true,
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

    /**
     * A bicycle: no plate, no licences.
     */
    public function withoutLicense(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_license' => false,
        ]);
    }
}
