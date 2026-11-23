<?php

namespace Tests;

use App\Contracts\SmsSender;
use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Catalog\Enums\ModeCode;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Tayars\Data\WorkSessionSettings;
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

    /**
     * The zone mode, created once per test (mode codes are unique).
     */
    protected function zoneMode(): Mode
    {
        return Mode::query()->where('code', ModeCode::Zone)->first() ?? Mode::factory()->zone()->create();
    }

    protected function zoneWorkSessionSettings(string $radiusKm = '5'): WorkSessionSettings
    {
        return new WorkSessionSettings($this->zoneMode(), radiusKm: $radiusKm);
    }

    /**
     * A Go online body in the zone mode.
     *
     * @return array{lat: float, lng: float, mode_id: int, radius_km: int}
     */
    protected function zoneOnlinePayload(float $lat = 30.0444, float $lng = 31.2357): array
    {
        return ['lat' => $lat, 'lng' => $lng, 'mode_id' => $this->zoneMode()->id, 'radius_km' => 5];
    }
}
