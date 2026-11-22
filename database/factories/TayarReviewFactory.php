<?php

namespace Database\Factories;

use App\Domains\Tayars\Enums\ReviewDecision;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\TayarReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TayarReview>
 */
class TayarReviewFactory extends Factory
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
            'decision' => ReviewDecision::Rejected,
            'reason' => 'The criminal record is not readable.',
            'reviewed_by' => User::factory(),
        ];
    }
}
