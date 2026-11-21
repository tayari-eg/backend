<?php

namespace Database\Factories;

use App\Domains\Accounts\Enums\VerificationStatus;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Accounts\Models\BusinessType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessProfile>
 */
class BusinessProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->business(),
            'business_type_id' => BusinessType::factory(),
            'business_name' => fake()->company(),
            'description' => fake()->sentence(),
            'avg_daily_orders' => fake()->numberBetween(1, 200),
            'tax_registration_number' => fake()->numerify('#########'),
            'verification_status' => VerificationStatus::Pending,
        ];
    }

    public function approved(?User $reviewer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::Approved,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(?User $reviewer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::Rejected,
            'rejection_reason' => fake()->sentence(),
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
        ]);
    }
}
