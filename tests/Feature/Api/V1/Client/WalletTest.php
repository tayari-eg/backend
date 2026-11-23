<?php

namespace Tests\Feature\Api\V1\Client;

use App\Domains\Accounts\Models\BusinessProfile;
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

    private User $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = BusinessProfile::factory()->approved()->create()->user;
        Sanctum::actingAs($this->business);
    }

    public function test_my_business_wallet_shows_what_tayari_owes_me_without_tayar_fields(): void
    {
        $wallet = Wallet::ensureFor($this->business->id, WalletType::Business);
        $this->postToWallet($wallet, '150.00', TransactionType::GoodsPayout);
        $this->postToWallet($wallet, '-40.00', TransactionType::ShippingFee);

        $this->getJson(route('api.v1.client.wallet.show'))
            ->assertOk()
            ->assertJsonPath('data.type', 'business')
            ->assertJsonPath('data.balance', '110.00')
            ->assertJsonPath('data.payable_balance', '110.00')
            ->assertJsonMissingPath('data.cash_to_hand_in')
            ->assertJsonMissingPath('data.locked_bonus');

        $this->getJson(route('api.v1.client.wallet.transactions'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'shipping_fee')
            ->assertJsonPath('data.0.amount', '-40.00')
            ->assertJsonMissingPath('data.0.posting.note');
    }

    public function test_a_new_business_sees_an_empty_wallet(): void
    {
        $this->getJson(route('api.v1.client.wallet.show'))
            ->assertOk()
            ->assertJsonPath('data.balance', '0.00')
            ->assertJsonPath('data.payable_balance', '0.00');
    }

    public function test_an_account_that_is_not_a_business_has_no_client_wallet(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('api.v1.client.wallet.show'))
            ->assertForbidden()
            ->assertJsonPath('message', __('wallet.business_only'));
        $this->getJson(route('api.v1.client.wallet.transactions'))->assertForbidden();
    }

    public function test_staff_cannot_use_the_client_wallet(): void
    {
        Sanctum::actingAs($this->createStaffUser());

        $this->getJson(route('api.v1.client.wallet.show'))->assertForbidden();
    }
}
