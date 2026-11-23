<?php

namespace Tests\Feature\Domains\Wallet;

use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Tayars\Actions\ApproveTayarApplicationAction;
use App\Domains\Tayars\Enums\TayarStatus;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\Vehicle;
use App\Domains\Wallet\Actions\PostSignupBonusAction;
use App\Domains\Wallet\Data\WalletSummary;
use App\Domains\Wallet\Enums\PostingType;
use App\Domains\Wallet\Enums\SystemWallet;
use App\Domains\Wallet\Enums\TransactionType;
use App\Domains\Wallet\Enums\WalletType;
use App\Domains\Wallet\Models\Wallet;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SignupBonusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tayari.wallet.signup_bonus' => '100.00', 'tayari.wallet.bonus_unlock_delivered_orders' => 5]);
    }

    public function test_the_first_approval_posts_the_bonus_from_tayaris_bonus_budget(): void
    {
        $profile = TayarProfile::factory()->has(Vehicle::factory())->create();

        app(ApproveTayarApplicationAction::class)->handle($profile, $this->createStaffUser());

        $this->assertSame('100.00', Wallet::ensureFor($profile->user_id, WalletType::Tayar)->balance);
        $this->assertSame('-100.00', Wallet::system(SystemWallet::BonusExpense)->balance);
        $this->assertDatabaseHas('wallet_postings', ['idempotency_key' => "signup_bonus:{$profile->user_id}", 'type' => PostingType::SignupBonus->value]);
    }

    public function test_a_tayar_sent_back_to_review_and_approved_again_gets_no_second_bonus(): void
    {
        $profile = TayarProfile::factory()->has(Vehicle::factory())->create();
        $reviewer = $this->createStaffUser();
        app(ApproveTayarApplicationAction::class)->handle($profile, $reviewer);

        $profile->refresh()->forceFill(['status' => TayarStatus::Pending])->save();
        app(ApproveTayarApplicationAction::class)->handle($profile, $reviewer);
        app(PostSignupBonusAction::class)->handle($profile);

        $this->assertDatabaseCount('wallet_postings', 1);
        $this->assertSame('100.00', Wallet::ensureFor($profile->user_id, WalletType::Tayar)->refresh()->balance);
    }

    public function test_a_tayar_approved_before_the_wallet_existed_gets_no_bonus_when_approved_again(): void
    {
        $reviewer = $this->createStaffUser();
        $profile = TayarProfile::factory()->approved($reviewer)->create();
        $profile->reviews()->create(['decision' => 'approved', 'reviewed_by' => $reviewer->id]);
        $profile->forceFill(['status' => TayarStatus::Pending])->save();

        app(ApproveTayarApplicationAction::class)->handle($profile, $reviewer);

        $this->assertDatabaseCount('wallet_postings', 0);
    }

    public function test_the_bonus_counts_in_the_balance_but_cannot_be_paid_out_until_enough_delivered_orders(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        // After today's payout hour, so only the bonus rule holds money back.
        $this->travelTo(now(config('app.business_timezone'))->setTime(23, 30));
        app(PostSignupBonusAction::class)->handle($tayar);
        $wallet = Wallet::ensureFor($tayar->user_id, WalletType::Tayar);
        $this->postToWallet($wallet, '60.00', TransactionType::DeliveryEarning);
        Order::factory()->count(4)->acceptedBy($tayar)->withStatus(OrderStatus::Delivered)->create();

        $locked = WalletSummary::of($wallet->refresh());

        $this->assertSame('160.00', (string) $locked->balance);
        $this->assertSame('100.00', (string) $locked->lockedBonus);
        $this->assertSame('60.00', (string) $locked->payable);

        Order::factory()->acceptedBy($tayar)->withStatus(OrderStatus::Delivered)->create();
        $unlocked = WalletSummary::of($wallet);

        $this->assertSame('0.00', (string) $unlocked->lockedBonus);
        $this->assertSame('160.00', (string) $unlocked->payable);
    }

    public function test_approving_creates_the_missing_wallets_before_the_transaction_opens(): void
    {
        $profile = TayarProfile::factory()->has(Vehicle::factory())->create();
        $reviewer = $this->createStaffUser();
        $this->assertDatabaseCount('wallets', 0);
        $wallets = null;
        Event::listen(TransactionBeginning::class, function () use (&$wallets): void {
            $wallets ??= Wallet::query()->orderBy('id')->get()->map(fn (Wallet $wallet): string => $wallet->system_code->value ?? $wallet->type->value)->all();
        });

        app(ApproveTayarApplicationAction::class)->handle($profile, $reviewer);

        $this->assertSame(['tayar', 'bonus_expense'], $wallets);
        $this->assertDatabaseHas('wallet_postings', ['idempotency_key' => "signup_bonus:{$profile->user_id}"]);
    }

    public function test_with_no_signup_bonus_configured_the_approval_posts_nothing(): void
    {
        config(['tayari.wallet.signup_bonus' => '0']);
        $profile = TayarProfile::factory()->has(Vehicle::factory())->create();

        app(ApproveTayarApplicationAction::class)->handle($profile, $this->createStaffUser());

        $this->assertSame(TayarStatus::Approved, $profile->refresh()->status);
        $this->assertNull(app(PostSignupBonusAction::class)->handle($profile));
        $this->assertDatabaseCount('wallet_postings', 0);
    }
}
