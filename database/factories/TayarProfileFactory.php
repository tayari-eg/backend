<?php

namespace Database\Factories;

use App\Domains\Accounts\Enums\AppMode;
use App\Domains\Tayars\Enums\TayarStatus;
use App\Domains\Tayars\Enums\VehicleStatus;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\Vehicle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TayarProfile>
 */
class TayarProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            // Century digit 2, a real birth date (YYMMDD) in the 1900s, then 7 digits: passes EgyptianNationalIdNumber.
            'national_id_number' => '2'.fake()->dateTimeBetween('1960-01-01', '1999-12-31')->format('ymd').fake()->unique()->numerify('#######'),
            'status' => TayarStatus::Pending,
        ];
    }

    /**
     * Approved, with an approved and active vehicle and the app in tayar mode, so the tayar can go online.
     */
    public function approved(?User $reviewer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TayarStatus::Approved,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
        ])
            ->has(Vehicle::factory()->state(['status' => VehicleStatus::Approved, 'is_active' => true]))
            ->afterCreating(fn (TayarProfile $profile) => $profile->user->forceFill(['last_mode' => AppMode::Tayar])->save());
    }

    public function rejected(?User $reviewer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TayarStatus::Rejected,
            'rejection_reason' => 'The criminal record is not readable.',
            'can_reapply_at' => now()->addHours(config('tayari.tayars.reapply_after_hours')),
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Online at a point, with a fresh location; pair with approved().
     */
    public function onlineAt(float $lat, float $lng): static
    {
        return $this->state(fn (array $attributes) => [
            'is_online' => true,
            'current_lat' => $lat,
            'current_lng' => $lng,
            'location_updated_at' => now(),
        ]);
    }
}
