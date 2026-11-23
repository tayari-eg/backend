<?php

namespace Database\Factories;

use App\Domains\Catalog\Enums\ModeCode;
use App\Domains\Catalog\Models\Mode;
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
     * Define the model's default state: an open session in the zone mode.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tayar_profile_id' => TayarProfile::factory(),
            'vehicle_id' => Vehicle::factory(),
            // Mode codes are unique, so sessions share the one zone mode.
            'mode_id' => fn (): int => Mode::query()->where('code', ModeCode::Zone)->value('id') ?? Mode::factory()->zone()->create()->id,
            'radius_km' => '5.00',
            'started_at' => now(),
            'ended_at' => null,
        ];
    }
}
