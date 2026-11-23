<?php

namespace App\Domains\Wallet\Actions;

use App\Domains\Orders\Enums\ShippingPaidBy;
use App\Domains\Orders\Models\Order;
use App\Domains\Wallet\Data\PostingLine;
use App\Domains\Wallet\Enums\PostingType;
use App\Domains\Wallet\Enums\SystemWallet;
use App\Domains\Wallet\Enums\TransactionType;
use App\Domains\Wallet\Enums\WalletType;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletPosting;
use Brick\Math\BigDecimal;
use LogicException;

class PostOrderDeliveredAction
{
    public function __construct(private PostToWalletsAction $postToWallets) {}

    /**
     * Every order is cash for now: the tayar collected amount_to_collect and owes it. Tayari owes the business the goods
     * value, less the shipping fee when the business pays the shipping; the tayar earns tayar_earning and Tayari keeps
     * platform_fee. Call it in the delivery's transaction, on the locked order.
     */
    public function handle(Order $order): WalletPosting
    {
        $goodsValue = BigDecimal::of((string) $order->goods_value);
        $shippingFee = BigDecimal::of((string) $order->shipping_fee);
        $tayarEarning = BigDecimal::of((string) $order->tayar_earning);
        $platformFee = BigDecimal::of((string) $order->platform_fee);

        if (! $tayarEarning->plus($platformFee)->isEqualTo($shippingFee) || $order->tayar_id === null) {
            throw new LogicException("Order {$order->id} can't be posted: its fee split doesn't match the shipping fee or it has no tayar.");
        }

        $business = Wallet::ensureFor($order->sender_id, WalletType::Business);
        $tayar = Wallet::ensureFor($order->tayar_id, WalletType::Tayar);
        $line = fn (Wallet $wallet, TransactionType $type, BigDecimal $amount): PostingLine => new PostingLine($wallet, $type, $amount, $order->id);

        return $this->postToWallets->handle(PostingType::OrderDelivered, "order:{$order->id}:delivered", [
            $line($business, TransactionType::GoodsPayout, $goodsValue),
            $line($business, TransactionType::ShippingFee, $order->shipping_paid_by === ShippingPaidBy::Sender ? $shippingFee->negated() : BigDecimal::zero()),
            $line($tayar, TransactionType::DeliveryEarning, $tayarEarning),
            $line($tayar, TransactionType::CashCollected, BigDecimal::of((string) $order->amount_to_collect)->negated()),
            $line(Wallet::system(SystemWallet::Revenue), TransactionType::Commission, $platformFee),
        ], $order);
    }
}
