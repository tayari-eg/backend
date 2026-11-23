<?php

namespace Tests\Feature\Domains\Wallet;

use App\Domains\Wallet\Actions\AdjustWalletAction;
use App\Domains\Wallet\Actions\PostSignupBonusAction;
use App\Domains\Wallet\Actions\RecordSettlementAction;
use App\Domains\Wallet\Actions\ReversePostingAction;
use App\Domains\Wallet\Enums\PostingType;
use App\Domains\Wallet\Enums\SettlementKind;
use App\Domains\Wallet\Enums\SystemWallet;
use App\Domains\Wallet\Enums\TransactionType;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletAdjustmentReason;
use App\Domains\Wallet\Models\WalletPosting;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SettlementActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = $this->createStaffUser();
        // After today's payout hour, so earnings count as available at once unless a test travels back.
        $this->travelTo(now(config('app.business_timezone'))->setTime(23, 30));
    }

    private function settle(Wallet $wallet, SettlementKind $kind, string $amount, ?string $key = null): WalletPosting
    {
        return app(RecordSettlementAction::class)->handle($wallet, $kind, BigDecimal::of($amount), 'REF-1', null, $key ?? (string) Str::uuid(), $this->staff);
    }

    private function assertRefused(string $field, string $message, callable $action): void
    {
        try {
            $action();
            $this->fail('Expected the action to be refused.');
        } catch (ValidationException $exception) {
            $this->assertSame([$message], $exception->errors()[$field]);
        }
    }

    public function test_a_payout_takes_money_from_the_wallet_into_the_treasury_with_the_staff_reference(): void
    {
        $wallet = Wallet::factory()->business()->create();
        $this->postToWallet($wallet, '300.00', TransactionType::GoodsPayout);

        $posting = $this->settle($wallet, SettlementKind::Payout, '120.00');

        $this->assertSame(PostingType::Payout, $posting->type);
        $this->assertSame('180.00', $wallet->refresh()->balance);
        $this->assertSame('120.00', Wallet::system(SystemWallet::Treasury)->balance);
        $this->assertDatabaseHas('wallet_postings', ['id' => $posting->id, 'created_by' => $this->staff->id, 'reference' => 'REF-1']);
    }

    public function test_a_payout_is_capped_at_what_is_payable_now(): void
    {
        // 22:00 business time: today's earning still waits for 23:00.
        $this->travelTo(now(config('app.business_timezone'))->setTime(22, 0));
        $wallet = Wallet::factory()->create();
        $this->postToWallet($wallet, '50.00');
        $this->postToWallet($wallet, '40.00', TransactionType::DeliveryEarning);

        $this->assertRefused('amount', __('wallet.payout_too_much', ['amount' => '50.00']), fn () => $this->settle($wallet, SettlementKind::Payout, '50.01'));
        $this->settle($wallet, SettlementKind::Payout, '50.00');

        $this->assertSame('40.00', $wallet->refresh()->balance);
    }

    public function test_cash_handed_in_is_capped_at_what_the_tayar_owes(): void
    {
        $wallet = Wallet::factory()->create();
        $this->postToWallet($wallet, '-190.00', TransactionType::CashCollected);
        $this->postToWallet($wallet, '32.00', TransactionType::DeliveryEarning);

        $this->assertRefused('amount', __('wallet.cash_handed_in_too_much', ['amount' => '158.00']), fn () => $this->settle($wallet, SettlementKind::CashHandedIn, '158.01'));
        $this->settle($wallet, SettlementKind::CashHandedIn, '158.00');

        $this->assertSame('0.00', $wallet->refresh()->balance);
        $this->assertSame('-158.00', Wallet::system(SystemWallet::Treasury)->balance);
    }

    public function test_only_tayars_hand_in_cash_and_system_wallets_are_never_settled_or_adjusted(): void
    {
        $business = Wallet::factory()->business()->create();
        $treasury = Wallet::system(SystemWallet::Treasury);

        $this->assertRefused('wallet', __('wallet.cash_handed_in_wrong_wallet'), fn () => $this->settle($business, SettlementKind::CashHandedIn, '1.00'));
        $this->assertRefused('wallet', __('wallet.payout_wrong_wallet'), fn () => $this->settle($treasury, SettlementKind::Payout, '1.00'));
        $this->assertRefused('wallet', __('wallet.system_wallet'), fn () => app(AdjustWalletAction::class)->handle($treasury, BigDecimal::of('1.00'), WalletAdjustmentReason::factory()->create(), 'Test', (string) Str::uuid(), $this->staff));
    }

    public function test_the_same_request_key_records_the_settlement_once(): void
    {
        $wallet = Wallet::factory()->create();
        $this->postToWallet($wallet, '100.00');
        $key = (string) Str::uuid();

        $first = $this->settle($wallet, SettlementKind::Payout, '60.00', $key);
        $retry = $this->settle($wallet, SettlementKind::Payout, '60.00', $key);

        $this->assertTrue($first->is($retry));
        $this->assertSame('40.00', $wallet->refresh()->balance);
    }

    public function test_an_adjustment_can_take_a_wallet_below_zero_and_records_its_reason(): void
    {
        $wallet = Wallet::factory()->create();
        $reason = WalletAdjustmentReason::factory()->create();

        $posting = app(AdjustWalletAction::class)->handle($wallet, BigDecimal::of('-25.00'), $reason, 'Damaged box', (string) Str::uuid(), $this->staff);

        $this->assertSame('-25.00', $wallet->refresh()->balance);
        $this->assertSame('25.00', Wallet::system(SystemWallet::Adjustments)->balance);
        $this->assertDatabaseHas('wallet_postings', ['id' => $posting->id, 'reason_id' => $reason->id, 'note' => 'Damaged box', 'created_by' => $this->staff->id]);
    }

    public function test_a_reversal_mirrors_the_posting_once_and_keeps_the_original(): void
    {
        $wallet = Wallet::factory()->create();
        $this->postToWallet($wallet, '100.00');
        $payout = $this->settle($wallet, SettlementKind::Payout, '70.00');
        $reason = WalletAdjustmentReason::factory()->create();

        $reversal = app(ReversePostingAction::class)->handle($payout, $reason, 'Paid to the wrong tayar', $this->staff);
        $again = app(ReversePostingAction::class)->handle($payout, $reason, 'Twice', $this->staff);

        $this->assertTrue($reversal->is($again));
        $this->assertSame($payout->id, $reversal->reversal_of_id);
        $this->assertSame('100.00', $wallet->refresh()->balance);
        $this->assertSame('0.00', Wallet::system(SystemWallet::Treasury)->balance);
        $this->assertDatabaseHas('wallet_transactions', ['posting_id' => $reversal->id, 'wallet_id' => $wallet->id, 'type' => TransactionType::Payout->value, 'amount' => '70.00']);
        $this->assertDatabaseCount('wallet_postings', 3);
    }

    public function test_order_bonus_and_reversal_postings_cannot_be_reversed(): void
    {
        $wallet = Wallet::factory()->create();
        $this->postToWallet($wallet, '100.00');
        $payout = $this->settle($wallet, SettlementKind::Payout, '10.00');
        $reason = WalletAdjustmentReason::factory()->create();
        $reversal = app(ReversePostingAction::class)->handle($payout, $reason, 'Mistake', $this->staff);

        $bonus = app(PostSignupBonusAction::class)->handle($this->onlineTayar(30.0450, 31.2360));

        $this->assertRefused('posting', __('wallet.cannot_reverse'), fn () => app(ReversePostingAction::class)->handle($reversal, $reason, 'Undo the undo', $this->staff));
        $this->assertRefused('posting', __('wallet.cannot_reverse'), fn () => app(ReversePostingAction::class)->handle($bonus, $reason, 'No bonus', $this->staff));
    }
}
