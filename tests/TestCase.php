<?php

namespace Tests;

use App\Contracts\SmsSender;
use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Accounts\Enums\StaffRole;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Swaps the SMS driver for one that keeps the messages, so tests can read the OTP.
     */
    protected function fakeSmsSender(): SmsSender
    {
        $smsSender = new class implements SmsSender
        {
            /** @var list<array{to: string, message: string}> */
            public array $messages = [];

            public function send(PhoneNumber $to, string $message): void
            {
                $this->messages[] = ['to' => $to->value, 'message' => $message];
            }

            public function lastCode(): ?string
            {
                $message = end($this->messages)['message'] ?? '';

                return preg_match('/\d{4,}/', $message, $matches) === 1 ? $matches[0] : null;
            }
        };

        $this->app->instance(SmsSender::class, $smsSender);

        return $smsSender;
    }

    protected function createStaffUser(StaffRole $role = StaffRole::Admin): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return User::factory()->create()->assignRole($role->value);
    }
}
