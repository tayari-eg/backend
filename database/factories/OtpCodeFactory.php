<?php

namespace Database\Factories;

use App\Domains\Accounts\Enums\OtpPurpose;
use App\Domains\Accounts\Models\OtpCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<OtpCode>
 */
class OtpCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone' => '+2010'.fake()->unique()->numerify('########'),
            'purpose' => OtpPurpose::VerifyPhone,
            'code_hash' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
            'used_at' => null,
            'created_at' => now(),
        ];
    }

    public function withCode(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'code_hash' => Hash::make($code),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinute(),
        ]);
    }

    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'used_at' => now(),
        ]);
    }
}
