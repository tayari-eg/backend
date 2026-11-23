<?php

namespace Database\Factories;

use App\Domains\Catalog\Enums\ModeCode;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Pricing\Models\PricingRule;
use App\Domains\Tayars\Models\VehicleType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingRule>
 */
class PricingRuleFactory extends Factory
{
    /**
     * Define the model's default state: an active rule for every vehicle in the zone mode.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mode_id' => fn (): int => Mode::query()->where('code', ModeCode::Zone)->value('id') ?? Mode::factory()->zone()->create()->id,
            'vehicle_type_id' => null,
            'base_fare' => '20.00',
            'price_per_km' => '5.00',
            'minimum_fare' => '30.00',
            'food_extra' => null,
            'tayar_share_percent' => '80.00',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function forVehicleType(?VehicleType $vehicleType = null): static
    {
        return $this->state(fn (array $attributes) => [
            'vehicle_type_id' => $vehicleType?->id ?? VehicleType::factory(),
        ]);
    }
}
