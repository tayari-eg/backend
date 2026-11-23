<?php

namespace Tests\Feature\Domains\Wallet;

use App\Domains\Accounts\Models\UserDevice;
use App\Domains\Orders\Actions\DeliverOrderAction;
use App\Domains\Orders\Models\Order;
use App\Domains\Wallet\Actions\AdjustWalletAction;
use App\Domains\Wallet\Actions\PostSignupBonusAction;
use App\Domains\Wallet\Actions\RecordSettlementAction;
use App\Domains\Wallet\Enums\SettlementKind;
use App\Domains\Wallet\Enums\WalletType;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletAdjustmentReason;
use App\Jobs\SendPushToDevice;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class WalletPushesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<array{int, array<string, string>, string}>
     */
    private function pushed(): array
    {
        return Queue::pushed(SendPushToDevice::class)->map(fn (SendPushToDevice $job): array => [$job->userId, $job->data, $job->body])->values()->all();
    }

    public function test_the_owner_hears_of_a_payout_cash_handed_in_an_adjustment_and_the_signup_bonus_with_the_amount(): void
    {
        $this->travelTo(now(config('app.business_timezone'))->setTime(23, 30));
        $staff = $this->createStaffUser();
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $user = User::query()->findOrFail($tayar->user_id);
        UserDevice::factory()->for($user)->create();
        $wallet = Wallet::ensureFor($user->id, WalletType::Tayar);
        $this->postToWallet($wallet, '-300.00');
        Queue::fake();

        $bonus = app(PostSignupBonusAction::class)->handle($tayar);
        $cash = app(RecordSettlementAction::class)->handle($wallet, SettlementKind::CashHandedIn, BigDecimal::of('150.00'), 'R-1', null, (string) Str::uuid(), $staff);
        $adjustment = app(AdjustWalletAction::class)->handle($wallet, BigDecimal::of('-5.50'), WalletAdjustmentReason::factory()->create(), 'Late', (string) Str::uuid(), $staff);

        $data = fn (string $type, int $postingId): array => ['type' => $type, 'wallet_id' => (string) $wallet->id, 'posting_id' => (string) $postingId];
        $this->assertSame([
            [$user->id, $data('wallet_signup_bonus', $bonus->id), __('push.wallet_signup_bonus.body', ['amount' => '100.00'], 'ar')],
            [$user->id, $data('wallet_cash_received', $cash->id), __('push.wallet_cash_received.body', ['amount' => '150.00'], 'ar')],
            [$user->id, $data('wallet_adjusted', $adjustment->id), __('push.wallet_adjusted.body', ['amount' => '5.50'], 'ar')],
        ], $this->pushed());
    }

    public function test_a_business_hears_of_its_payout(): void
    {
        $business = Wallet::factory()->business()->create();
        UserDevice::factory()->for($business->user)->create();
        $this->postToWallet($business, '80.00');
        Queue::fake();

        $payout = app(RecordSettlementAction::class)->handle($business, SettlementKind::Payout, BigDecimal::of('80.00'), 'R-2', null, (string) Str::uuid(), $this->createStaffUser());

        $this->assertSame([[$business->user_id, ['type' => 'wallet_payout_recorded', 'wallet_id' => (string) $business->id, 'posting_id' => (string) $payout->id]]], array_map(fn (array $push): array => [$push[0], $push[1]], $this->pushed()));
    }

    public function test_a_delivered_order_sends_no_wallet_push(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        UserDevice::factory()->for(User::query()->findOrFail($tayar->user_id))->create();
        $order = Order::factory()->onTheWayWith($tayar)->create();
        $codes = $this->sendReceiverCodes($order);
        Queue::fake();

        app(DeliverOrderAction::class)->handle($order, User::query()->findOrFail($tayar->user_id), $codes['delivery'], 30.0, 31.0);

        $this->assertSame([], array_values(array_filter($this->pushed(), fn (array $push): bool => str_starts_with($push[1]['type'], 'wallet_'))));
    }
}
