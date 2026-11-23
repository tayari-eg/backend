<?php

namespace Tests\Feature\Api\V1\Tayar;

use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Wallet\Actions\PostOrderDeliveredAction;
use App\Domains\Wallet\Actions\PostSignupBonusAction;
use App\Domains\Wallet\Enums\TransactionType;
use App\Domains\Wallet\Enums\WalletType;
use App\Domains\Wallet\Models\Wallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    private TayarProfile $tayar;

    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tayari.wallet.signup_bonus' => '100.00', 'tayari.wallet.bonus_unlock_delivered_orders' => 5, 'tayari.wallet.max_cash_owed' => '3000.00']);
        $this->travelTo(now(config('app.business_timezone'))->setTime(23, 30));
        $this->tayar = $this->onlineTayar(30.0450, 31.2360);
        $this->wallet = Wallet::ensureFor($this->tayar->user_id, WalletType::Tayar);
        Sanctum::actingAs(User::query()->findOrFail($this->tayar->user_id));
    }

    public function test_my_wallet_shows_the_balance_what_can_be_paid_out_the_locked_bonus_and_the_cash_i_owe(): void
    {
        app(PostSignupBonusAction::class)->handle($this->tayar);
        $this->postToWallet($this->wallet, '-190.00', TransactionType::CashCollected);
        $this->postToWallet($this->wallet, '32.00', TransactionType::DeliveryEarning);

        $this->getJson(route('api.v1.tayar.wallet.show'))
            ->assertOk()
            ->assertJsonPath('data.id', $this->wallet->id)
            ->assertJsonPath('data.type', 'tayar')
            ->assertJsonPath('data.balance', '-58.00')
            ->assertJsonPath('data.available_balance', '-58.00')
            ->assertJsonPath('data.payable_balance', '0.00')
            ->assertJsonPath('data.locked_bonus', '100.00')
            ->assertJsonPath('data.bonus_unlock_delivered_orders', 5)
            ->assertJsonPath('data.cash_to_hand_in', '58.00')
            ->assertJsonPath('data.max_cash_owed', '3000.00')
            ->assertJsonPath('data.blocked_from_offers', false);
    }

    public function test_a_tayar_owing_more_than_the_limit_is_shown_as_blocked_from_offers(): void
    {
        $this->postToWallet($this->wallet, '-3000.01', TransactionType::CashCollected);

        $this->getJson(route('api.v1.tayar.wallet.show'))
            ->assertOk()
            ->assertJsonPath('data.blocked_from_offers', true);
    }

    public function test_my_transactions_list_newest_first_with_the_order_code_filtered_by_type_with_cursor_pages(): void
    {
        app(PostSignupBonusAction::class)->handle($this->tayar);
        $order = Order::factory()->acceptedBy($this->tayar)->withStatus(OrderStatus::Delivered)->create();
        app(PostOrderDeliveredAction::class)->handle($order);

        $this->getJson(route('api.v1.tayar.wallet.transactions', ['per_page' => 2]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'cash_collected')
            ->assertJsonPath('data.0.amount', '-150.00')
            ->assertJsonPath('data.0.balance_after', '-18.00')
            ->assertJsonPath('data.0.order', ['id' => $order->id, 'code' => $order->code])
            ->assertJsonPath('data.0.posting.type', 'order_delivered')
            ->assertJsonPath('data.1.type', 'delivery_earning')
            ->assertJsonPath('data.1.amount', '32.00')
            ->assertJsonStructure(['meta' => ['next_cursor']]);

        $this->getJson(route('api.v1.tayar.wallet.transactions', ['type' => 'bonus']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.amount', '100.00')
            ->assertJsonPath('data.0.order', null)
            ->assertJsonPath('data.0.posting.type', 'signup_bonus');

        $this->getJson(route('api.v1.tayar.wallet.transactions', ['type' => 'nope']))->assertUnprocessable()->assertJsonValidationErrors('type');
    }

    public function test_i_only_see_my_own_transactions(): void
    {
        $this->postToWallet(Wallet::factory()->create(), '10.00');

        $this->getJson(route('api.v1.tayar.wallet.transactions'))->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_a_client_who_is_not_an_approved_tayar_cannot_see_a_tayar_wallet(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('api.v1.tayar.wallet.show'))->assertForbidden();
    }
}
