<?php

namespace Database\Factories;

use App\Domains\Tayars\Enums\VehicleStatus;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\Vehicle;
use App\Domains\Tayars\Models\VehicleType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tayar_profile_id' => TayarProfile::factory(),
            'vehicle_type_id' => VehicleType::factory(),
            'plate_number' => fake()->unique()->bothify('??? ####'),
            'model' => 'Example Model',
            'color' => 'Red',
            'year' => 2020,
            'status' => VehicleStatus::Pending,
            'is_active' => false,
        ];
    }
}
