<?php

namespace App\Domains\Wallet\Actions;

use App\Domains\Orders\Models\Order;
use App\Domains\Wallet\Data\PostingLine;
use App\Domains\Wallet\Enums\PostingType;
use App\Domains\Wallet\Enums\TransactionType;
use App\Domains\Wallet\Enums\WalletType;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletPosting;
use Brick\Math\BigDecimal;

class PostCancelFeeAction
{
    public function __construct(private PostToWalletsAction $postToWallets) {}

    /**
     * The whole cancel fee goes to the tayar it is owed to, charged to the business. Nothing to post without a fee. Call
     * it in the cancel's transaction, on the locked order.
     */
    public function handle(Order $order): ?WalletPosting
    {
        $fee = BigDecimal::of((string) $order->cancel_fee);

        if (! $fee->isPositive() || $order->cancel_fee_owed_to_tayar_id === null) {
            return null;
        }

        return $this->postToWallets->handle(PostingType::CancelFee, "order:{$order->id}:cancel_fee", [
            new PostingLine(Wallet::ensureFor($order->cancel_fee_owed_to_tayar_id, WalletType::Tayar), TransactionType::CancelFeeEarned, $fee, $order->id),
            new PostingLine(Wallet::ensureFor($order->sender_id, WalletType::Business), TransactionType::CancelFeeCharged, $fee->negated(), $order->id),
        ], $order);
    }
}
