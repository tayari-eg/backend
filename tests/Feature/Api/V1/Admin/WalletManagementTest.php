<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Wallet\Actions\PostSignupBonusAction;
use App\Domains\Wallet\Enums\SystemWallet;
use App\Domains\Wallet\Enums\TransactionType;
use App\Domains\Wallet\Enums\WalletType;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletAdjustmentReason;
use App\Domains\Wallet\Models\WalletPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WalletManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now(config('app.business_timezone'))->setTime(23, 30));
        $this->staff = $this->createStaffUser(StaffRole::Admin);
        Sanctum::actingAs($this->staff);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function settlement(array $overrides = []): array
    {
        return ['idempotency_key' => (string) Str::uuid(), 'kind' => 'payout', 'amount' => '40.00', 'reference' => 'INSTAPAY-123', 'note' => 'Weekly payout', ...$overrides];
    }

    public function test_the_list_shows_every_wallet_with_its_owner_and_filters_by_type_owing_and_search(): void
    {
        $tayar = Wallet::factory()->create(['user_id' => User::factory()->create(['name' => 'Karim Tayar', 'phone' => '+201011112222'])]);
        $business = Wallet::factory()->business()->create();
        $this->postToWallet($tayar, '-50.00', TransactionType::CashCollected);

        $this->getJson(route('api.v1.admin.wallets.index'))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.type', 'system')
            ->assertJsonPath('data.0.system_code', 'adjustments')
            ->assertJsonPath('data.0.owner', null)
            ->assertJsonPath('data.1.id', $business->id)
            ->assertJsonPath('data.2.owner', ['id' => $tayar->user_id, 'name' => 'Karim Tayar', 'phone' => '+201011112222'])
            ->assertJsonMissingPath('data.2.summary');

        $this->getJson(route('api.v1.admin.wallets.index', ['type' => 'business']))->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $business->id);
        $this->getJson(route('api.v1.admin.wallets.index', ['owing' => 'true']))->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $tayar->id);
        $this->getJson(route('api.v1.admin.wallets.index', ['search' => 'karim']))->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $tayar->id);
        $this->getJson(route('api.v1.admin.wallets.index', ['search' => '01011112222']))->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $tayar->id);
        $this->getJson(route('api.v1.admin.wallets.index', ['sort' => 'balance']))->assertJsonPath('data.0.id', $tayar->id);
        $this->getJson(route('api.v1.admin.wallets.index', ['type' => 'bank']))->assertUnprocessable()->assertJsonValidationErrors('type');
    }

    public function test_the_owing_filter_never_lists_a_system_wallet(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        app(PostSignupBonusAction::class)->handle($tayar);
        $business = Wallet::factory()->business()->create();
        $this->postToWallet($business, '-20.00', TransactionType::ShippingFee);

        $this->assertSame('-100.00', Wallet::system(SystemWallet::BonusExpense)->balance);
        $this->getJson(route('api.v1.admin.wallets.index', ['owing' => 'true']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $business->id);
    }

    public function test_one_wallet_shows_its_payout_summary_and_its_transactions_with_the_posting_details(): void
    {
        $wallet = Wallet::factory()->create();
        $this->postToWallet($wallet, '-190.00', TransactionType::CashCollected);

        $this->getJson(route('api.v1.admin.wallets.show', $wallet))
            ->assertOk()
            ->assertJsonPath('data.balance', '-190.00')
            ->assertJsonPath('data.summary.cash_to_hand_in', '190.00')
            ->assertJsonPath('data.summary.payable_balance', '0.00');

        $posting = $this->postJson(route('api.v1.admin.wallets.settlements.store', $wallet), $this->settlement(['kind' => 'cash_handed_in', 'amount' => '90.00']))
            ->assertCreated()
            ->json('data');

        $this->getJson(route('api.v1.admin.wallets.transactions', $wallet))
            ->assertOk()
            ->assertJsonPath('data.0.type', 'cash_handed_in')
            ->assertJsonPath('data.0.amount', '90.00')
            ->assertJsonPath('data.0.posting.id', $posting['id'])
            ->assertJsonPath('data.0.posting.note', 'Weekly payout')
            ->assertJsonPath('data.0.posting.reference', 'INSTAPAY-123')
            ->assertJsonPath('data.0.posting.created_by', ['id' => $this->staff->id, 'name' => $this->staff->name])
            ->assertJsonPath('data.0.posting.can_reverse', true);
    }

    public function test_a_payout_is_recorded_once_per_idempotency_key_and_capped_at_the_payable_balance(): void
    {
        $wallet = Wallet::factory()->business()->create();
        $this->postToWallet($wallet, '100.00', TransactionType::GoodsPayout);
        $body = $this->settlement();

        $this->postJson(route('api.v1.admin.wallets.settlements.store', $wallet), $body)
            ->assertCreated()
            ->assertJsonPath('data.type', 'payout')
            ->assertJsonPath('data.transactions.0.wallet_id', $wallet->id)
            ->assertJsonPath('data.transactions.0.amount', '-40.00')
            ->assertJsonPath('data.transactions.1.wallet_id', Wallet::system(SystemWallet::Treasury)->id);
        $this->postJson(route('api.v1.admin.wallets.settlements.store', $wallet), $body)->assertOk();

        $this->assertSame('60.00', $wallet->refresh()->balance);

        $this->postJson(route('api.v1.admin.wallets.settlements.store', $wallet), $this->settlement(['amount' => '60.01']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.amount.0', __('wallet.payout_too_much', ['amount' => '60.00']));
    }

    public function test_a_settlement_is_validated(): void
    {
        $wallet = Wallet::factory()->create();

        $this->postJson(route('api.v1.admin.wallets.settlements.store', $wallet), ['kind' => 'refund', 'amount' => '1.234', 'idempotency_key' => 'x'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['kind', 'amount', 'idempotency_key', 'reference']);
        $this->postJson(route('api.v1.admin.wallets.settlements.store', $wallet), $this->settlement(['amount' => '0']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');
    }

    public function test_an_adjustment_needs_an_active_reason_and_a_note_and_may_take_the_balance_below_zero(): void
    {
        $wallet = Wallet::factory()->create();
        $reason = WalletAdjustmentReason::factory()->create();
        $inactive = WalletAdjustmentReason::factory()->inactive()->create();
        $body = ['idempotency_key' => (string) Str::uuid(), 'amount' => '-15.50', 'reason_id' => $reason->id, 'note' => 'Late delivery penalty'];

        $this->postJson(route('api.v1.admin.wallets.adjustments.store', $wallet), $body)
            ->assertCreated()
            ->assertJsonPath('data.type', 'adjustment')
            ->assertJsonPath('data.reason.id', $reason->id)
            ->assertJsonPath('data.note', 'Late delivery penalty');

        $this->assertSame('-15.50', $wallet->refresh()->balance);

        $this->postJson(route('api.v1.admin.wallets.adjustments.store', $wallet), [...$body, 'idempotency_key' => (string) Str::uuid(), 'amount' => '0.00', 'reason_id' => $inactive->id, 'note' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount', 'reason_id', 'note']);
        $this->postJson(route('api.v1.admin.wallets.adjustments.store', Wallet::system(SystemWallet::Revenue)), [...$body, 'idempotency_key' => (string) Str::uuid()])
            ->assertUnprocessable()
            ->assertJsonPath('errors.wallet.0', __('wallet.system_wallet'));
    }

    public function test_a_settlement_is_reversed_once_and_an_order_posting_cannot_be(): void
    {
        $wallet = Wallet::factory()->create();
        $this->postToWallet($wallet, '100.00');
        $reason = WalletAdjustmentReason::factory()->create();
        $payoutId = $this->postJson(route('api.v1.admin.wallets.settlements.store', $wallet), $this->settlement())->json('data.id');

        $this->postJson(route('api.v1.admin.wallet-postings.reverse', $payoutId), ['reason_id' => $reason->id, 'note' => 'Wrong wallet'])
            ->assertCreated()
            ->assertJsonPath('data.type', 'reversal')
            ->assertJsonPath('data.reversal_of_id', $payoutId)
            ->assertJsonPath('data.can_reverse', false);
        $this->postJson(route('api.v1.admin.wallet-postings.reverse', $payoutId), ['reason_id' => $reason->id, 'note' => 'Again'])->assertOk();

        $this->assertSame('100.00', $wallet->refresh()->balance);
        $this->getJson(route('api.v1.admin.wallets.transactions', $wallet))->assertJsonPath('data.1.posting.can_reverse', false);

        $reversalId = WalletPosting::query()->where('reversal_of_id', $payoutId)->value('id');
        $this->postJson(route('api.v1.admin.wallet-postings.reverse', $reversalId), ['reason_id' => $reason->id, 'note' => 'Undo'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.posting.0', __('wallet.cannot_reverse'));
    }

    public function test_support_only_view_ops_also_settle_and_only_admins_adjust(): void
    {
        $wallet = Wallet::factory()->create();
        $this->postToWallet($wallet, '100.00');
        $reason = WalletAdjustmentReason::factory()->create();
        $adjustment = ['idempotency_key' => (string) Str::uuid(), 'amount' => '5.00', 'reason_id' => $reason->id, 'note' => 'Bonus'];

        Sanctum::actingAs(User::factory()->create()->assignRole(StaffRole::Support->value));
        $this->getJson(route('api.v1.admin.wallets.show', $wallet))->assertOk();
        $this->postJson(route('api.v1.admin.wallets.settlements.store', $wallet), $this->settlement())->assertForbidden();

        Sanctum::actingAs(User::factory()->create()->assignRole(StaffRole::Ops->value));
        $this->postJson(route('api.v1.admin.wallets.settlements.store', $wallet), $this->settlement())->assertCreated();
        $this->postJson(route('api.v1.admin.wallets.adjustments.store', $wallet), $adjustment)->assertForbidden();
        $this->postJson(route('api.v1.admin.wallet-adjustment-reasons.store'), ['name' => ['ar' => 'أ', 'en' => 'A']])->assertForbidden();

        Sanctum::actingAs(Wallet::ensureFor($wallet->user_id, WalletType::Tayar)->user);
        $this->getJson(route('api.v1.admin.wallets.index'))->assertForbidden();
    }
}
