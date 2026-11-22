<?php

namespace Database\Factories;

use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\TayarWorkSession;
use App\Domains\Tayars\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TayarWorkSession>
 */
class TayarWorkSessionFactory extends Factory
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
            'vehicle_id' => Vehicle::factory(),
            'started_at' => now(),
            'ended_at' => null,
        ];
    }
}
