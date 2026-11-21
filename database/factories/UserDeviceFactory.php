<?php

namespace Database\Factories;

use App\Domains\Accounts\Enums\Platform;
use App\Domains\Accounts\Models\UserDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserDevice>
 */
class UserDeviceFactory extends Factory
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
            'platform' => Platform::Android,
            'push_token' => Str::random(152),
            'app_version' => '1.0.0',
            'last_seen_at' => now(),
        ];
    }
}
