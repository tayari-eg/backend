<?php

namespace Database\Factories;

use App\Domains\Catalog\Enums\ModeCode;
use App\Domains\Catalog\Models\Mode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Codes are unique: one mode per code in a test, through the zone(), area() and longDistance() states.
 *
 * @extends Factory<Mode>
 */
class ModeFactory extends Factory
{
    /**
     * Define the model's default state: the zone mode.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'code' => ModeCode::Zone,
            'name' => ['ar' => 'وضع '.$name, 'en' => ucfirst($name)],
            'description' => ['ar' => 'وصف '.$name, 'en' => 'About '.$name],
            'allows_food' => true,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function zone(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => ModeCode::Zone,
        ]);
    }

    public function area(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => ModeCode::Area,
        ]);
    }

    public function longDistance(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => ModeCode::LongDistance,
            'allows_food' => false,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
