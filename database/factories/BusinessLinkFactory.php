<?php

namespace Database\Factories;

use App\Domains\Accounts\Enums\BusinessLinkType;
use App\Domains\Accounts\Models\BusinessLink;
use App\Domains\Accounts\Models\BusinessProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessLink>
 */
class BusinessLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_profile_id' => BusinessProfile::factory(),
            'type' => BusinessLinkType::Website,
            'url' => fake()->url(),
        ];
    }
}
