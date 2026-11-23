<?php

namespace Tests;

use App\Contracts\SmsSender;
use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Catalog\Enums\ModeCode;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Orders\Models\Order;
use App\Domains\Tayars\Actions\GoOnlineAction;
use App\Domains\Tayars\Data\WorkSessionSettings;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Wallet\Actions\PostToWalletsAction;
use App\Domains\Wallet\Data\PostingLine;
use App\Domains\Wallet\Enums\PostingType;
use App\Domains\Wallet\Enums\SystemWallet;
use App\Domains\Wallet\Enums\TransactionType;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletPosting;
use App\Jobs\SendReceiverCodes;
use App\Models\User;
use Brick\Math\BigDecimal;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

            public function send(PhoneNumber $to, #[\SensitiveParameter] string $message): void
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
     * An approved tayar online at the point through GoOnlineAction: the zone mode with a 5 km radius unless other
     * settings are given.
     */
    protected function onlineTayar(float $lat, float $lng, ?WorkSessionSettings $settings = null): TayarProfile
    {
        return app(GoOnlineAction::class)->handle(TayarProfile::factory()->approved()->create(), $lat, $lng, $settings ?? $this->zoneWorkSessionSettings());
    }

    /**
     * Runs SendReceiverCodes for the order now and reads the two codes from the SMS the receiver got.
     *
     * @return array{delivery: string, refusal: string}
     */
    protected function sendReceiverCodes(Order $order): array
    {
        $smsSender = $this->fakeSmsSender();
        (new SendReceiverCodes($order->id))->handle($smsSender);

        // The codes are the standalone digit runs; the order code (after "TY-") and the token are not.
        preg_match_all('/(?<![\w-])\d{'.config()->integer('tayari.tracking.code_length').'}(?!\w)/u', end($smsSender->messages)['message'], $matches);

        return ['delivery' => $matches[0][0], 'refusal' => $matches[0][1]];
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

    /**
     * Creates the orders, each with two pickup photos.
     *
     * @param  Factory<Order>  $orders
     */
    protected function createPickedUpOrdersWithPhotos(Factory $orders, int $count): void
    {
        $orders->count($count)->create()->each(function (Order $order): void {
            $order->addMedia(UploadedFile::fake()->image('front.jpg'))->toMediaCollection(Order::PICKUP_PHOTOS);
            $order->addMedia(UploadedFile::fake()->image('back.jpg'))->toMediaCollection(Order::PICKUP_PHOTOS);
        });
    }

    /**
     * How many queries the callback runs.
     */
    protected function countQueries(callable $callback): int
    {
        $count = 0;
        DB::listen(function () use (&$count): void {
            $count++;
        });
        $callback();

        return $count;
    }

    /**
     * Moves money into (positive) or out of (negative) the wallet through PostToWalletsAction, against Tayari's
     * adjustments wallet, as an entry of the given type available at once unless the type waits for the payout hour.
     */
    protected function postToWallet(Wallet $wallet, string $amount, TransactionType $type = TransactionType::Adjustment): WalletPosting
    {
        $amount = BigDecimal::of($amount);

        return app(PostToWalletsAction::class)->handle(PostingType::Adjustment, 'test:'.Str::uuid(), [
            new PostingLine($wallet, $type, $amount),
            new PostingLine(Wallet::system(SystemWallet::Adjustments), TransactionType::Adjustment, $amount->negated()),
        ]);
    }
}
