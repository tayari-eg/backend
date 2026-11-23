<?php

namespace Database\Factories;

use App\Domains\Wallet\Enums\WalletType;
use App\Domains\Wallet\Models\Wallet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * An empty wallet. Balances only change through PostToWalletsAction, so tests post money instead of setting it.
 *
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
{
    /**
     * Define the model's default state: a tayar wallet.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => WalletType::Tayar,
        ];
    }

    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => WalletType::Business,
        ]);
    }
}
