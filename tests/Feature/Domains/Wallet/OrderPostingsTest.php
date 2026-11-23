<?php

namespace Tests\Feature\Domains\Wallet;

use App\Domains\Orders\Actions\CancelOrderAction;
use App\Domains\Orders\Actions\DeliverOrderAction;
use App\Domains\Orders\Actions\MarkOrderNotDeliveredAction;
use App\Domains\Orders\Enums\OrderReasonType;
use App\Domains\Orders\Enums\ShippingPaidBy;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderReason;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Wallet\Enums\PostingType;
use App\Domains\Wallet\Enums\SystemWallet;
use App\Domains\Wallet\Enums\TransactionType;
use App\Domains\Wallet\Enums\WalletType;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletPosting;
use App\Domains\Wallet\Models\WalletTransaction;
use App\Models\User;
use Brick\Math\BigDecimal;
use Closure;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderPostingsTest extends TestCase
{
    use RefreshDatabase;

    private TayarProfile $tayar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tayar = $this->onlineTayar(30.0450, 31.2360);
    }

    private function deliver(Order $order): void
    {
        $codes = $this->sendReceiverCodes($order);
        app(DeliverOrderAction::class)->handle($order, User::query()->findOrFail($this->tayar->user_id), $codes['delivery'], 30.06, 31.25);
    }

    /**
     * @return array<string, string> "{wallet type or system code}:{transaction type}" => amount
     */
    private function lines(WalletPosting $posting): array
    {
        return $posting->transactions()->with('wallet')->orderBy('id')->get()
            ->mapWithKeys(fn (WalletTransaction $line): array => [($line->wallet->system_code->value ?? $line->wallet->type->value).':'.$line->type->value => $line->amount])
            ->all();
    }

    /**
     * @return list<string> the wallets (system code or type) that existed when the action opened its first transaction
     */
    private function walletsWhenTheTransactionOpened(Closure $action): array
    {
        $wallets = null;
        Event::listen(TransactionBeginning::class, function () use (&$wallets): void {
            $wallets ??= Wallet::query()->orderBy('id')->get()->map(fn (Wallet $wallet): string => $wallet->system_code->value ?? $wallet->type->value)->all();
        });

        $action();

        return $wallets ?? [];
    }

    private function assertLedgerBalances(): void
    {
        $this->assertSame('0.00', (string) BigDecimal::of((string) Wallet::query()->sum('balance'))->toScale(2));
    }

    public function test_a_delivered_cash_order_paid_by_the_receiver_posts_the_goods_the_earning_the_cash_and_the_commission(): void
    {
        $order = Order::factory()->onTheWayWith($this->tayar)->paidByReceiver()->create([
            'goods_value' => '150.00', 'shipping_fee' => '40.00', 'amount_to_collect' => '190.00', 'tayar_earning' => '32.00', 'platform_fee' => '8.00',
        ]);

        $this->deliver($order);

        $posting = WalletPosting::query()->where('idempotency_key', "order:{$order->id}:delivered")->firstOrFail();
        $this->assertSame(PostingType::OrderDelivered, $posting->type);
        $this->assertSame([
            'business:goods_payout' => '150.00',
            'tayar:delivery_earning' => '32.00',
            'tayar:cash_collected' => '-190.00',
            'revenue:commission' => '8.00',
        ], $this->lines($posting));
        $this->assertSame('150.00', Wallet::ensureFor($order->sender_id, WalletType::Business)->balance);
        $this->assertSame('-158.00', Wallet::ensureFor($this->tayar->user_id, WalletType::Tayar)->balance);
        $this->assertSame('8.00', Wallet::system(SystemWallet::Revenue)->balance);
        $this->assertDatabaseHas('wallet_transactions', ['posting_id' => $posting->id, 'order_id' => $order->id, 'type' => TransactionType::GoodsPayout->value]);
        $this->assertLedgerBalances();
    }

    public function test_when_the_business_pays_the_shipping_it_is_taken_from_the_business_and_the_tayar_collects_only_the_goods(): void
    {
        $order = Order::factory()->onTheWayWith($this->tayar)->create([
            'shipping_paid_by' => ShippingPaidBy::Sender, 'goods_value' => '150.00', 'shipping_fee' => '40.00', 'amount_to_collect' => '150.00',
        ]);

        $this->deliver($order);

        $this->assertSame([
            'business:goods_payout' => '150.00',
            'business:shipping_fee' => '-40.00',
            'tayar:delivery_earning' => '32.00',
            'tayar:cash_collected' => '-150.00',
            'revenue:commission' => '8.00',
        ], $this->lines(WalletPosting::query()->where('idempotency_key', "order:{$order->id}:delivered")->firstOrFail()));
        $this->assertSame('110.00', Wallet::ensureFor($order->sender_id, WalletType::Business)->balance);
        $this->assertLedgerBalances();
    }

    public function test_an_order_with_no_goods_value_and_shipping_on_the_business_can_leave_the_business_owing(): void
    {
        $order = Order::factory()->onTheWayWith($this->tayar)->create(['goods_value' => '0.00', 'amount_to_collect' => '0.00']);

        $this->deliver($order);

        $this->assertSame([
            'business:shipping_fee' => '-40.00',
            'tayar:delivery_earning' => '32.00',
            'revenue:commission' => '8.00',
        ], $this->lines(WalletPosting::query()->where('idempotency_key', "order:{$order->id}:delivered")->firstOrFail()));
        $this->assertSame('-40.00', Wallet::ensureFor($order->sender_id, WalletType::Business)->balance);
    }

    public function test_a_not_delivered_order_posts_nothing(): void
    {
        $order = Order::factory()->onTheWayWith($this->tayar)->create();
        $codes = $this->sendReceiverCodes($order);
        $reason = OrderReason::factory()->create(['type' => OrderReasonType::NotDelivered]);

        app(MarkOrderNotDeliveredAction::class)->handle($order, User::query()->findOrFail($this->tayar->user_id), $codes['refusal'], $reason, null, 30.06, 31.25);

        $this->assertDatabaseCount('wallet_postings', 0);
    }

    public function test_a_cancel_fee_goes_to_the_tayar_and_is_charged_to_the_business(): void
    {
        config(['tayari.matching.cancel_fee' => '20.00']);
        $order = Order::factory()->acceptedBy($this->tayar)->create();
        $this->travel(config()->integer('tayari.matching.free_cancel_seconds') + 1)->seconds();

        app(CancelOrderAction::class)->handle($order, $order->sender()->firstOrFail(), OrderReason::factory()->create());

        $posting = WalletPosting::query()->where('idempotency_key', "order:{$order->id}:cancel_fee")->firstOrFail();
        $this->assertSame(PostingType::CancelFee, $posting->type);
        $this->assertSame(['tayar:cancel_fee_earned' => '20.00', 'business:cancel_fee_charged' => '-20.00'], $this->lines($posting));
        $this->assertLedgerBalances();
    }

    public function test_a_free_cancel_or_a_staff_cancel_posts_nothing(): void
    {
        $free = Order::factory()->acceptedBy($this->tayar)->create();
        $byStaff = Order::factory()->acceptedBy($this->tayar)->create(['accepted_at' => now()->subHour()]);

        app(CancelOrderAction::class)->handle($free, $free->sender()->firstOrFail(), OrderReason::factory()->create());
        app(CancelOrderAction::class)->handle($byStaff, $this->createStaffUser(), OrderReason::factory()->create());

        $this->assertDatabaseCount('wallet_postings', 0);
    }

    public function test_delivering_creates_the_missing_wallets_before_the_transaction_opens(): void
    {
        $order = Order::factory()->onTheWayWith($this->tayar)->create();
        $codes = $this->sendReceiverCodes($order);
        $this->assertDatabaseCount('wallets', 0);

        $wallets = $this->walletsWhenTheTransactionOpened(fn () => app(DeliverOrderAction::class)->handle($order, User::query()->findOrFail($this->tayar->user_id), $codes['delivery'], 30.06, 31.25));

        $this->assertSame(['business', 'tayar', 'revenue'], $wallets);
        $this->assertDatabaseHas('wallet_postings', ['idempotency_key' => "order:{$order->id}:delivered"]);
    }

    public function test_cancelling_with_a_fee_creates_the_missing_wallets_before_the_transaction_opens(): void
    {
        config(['tayari.matching.cancel_fee' => '20.00']);
        $order = Order::factory()->acceptedBy($this->tayar)->create();
        $reason = OrderReason::factory()->create();
        $this->travel(config()->integer('tayari.matching.free_cancel_seconds') + 1)->seconds();
        $this->assertDatabaseCount('wallets', 0);

        $wallets = $this->walletsWhenTheTransactionOpened(fn () => app(CancelOrderAction::class)->handle($order, $order->sender()->firstOrFail(), $reason));

        $this->assertSame(['tayar', 'business'], $wallets);
        $this->assertDatabaseHas('wallet_postings', ['idempotency_key' => "order:{$order->id}:cancel_fee"]);
    }
}
