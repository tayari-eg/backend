<?php

namespace App\Domains\Orders\Actions;

use App\Domains\Orders\Enums\OfferStatus;
use App\Domains\Orders\Enums\OrderCanceller;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Events\OrderCancelled;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderOffer;
use App\Domains\Orders\Models\OrderReason;
use App\Domains\Wallet\Actions\EnsureWalletsAction;
use App\Domains\Wallet\Actions\PostCancelFeeAction;
use App\Domains\Wallet\Enums\WalletType;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelOrderAction
{
    public function __construct(private EnsureWalletsAction $ensureWallets, private PostCancelFeeAction $postCancelFee) {}

    /**
     * Cancels an order by its sender or by staff, with a cancel reason and an optional note (kept on the status history
     * row), or by the system (no actor, no reason) when no tayar accepted it. Senders and staff cancel until pickup; an
     * accepted order keeps its tayar. A sender cancelling an accepted order after tayari.matching.free_cancel_seconds
     * owes the tayar a cancel fee: it is recorded on the order (cancel_fee, cancel_fee_owed_to_tayar_id) and posted to
     * the tayar's and the business's wallets in the same transaction, after the offers; when a fee is possible, those
     * wallets are created before it opens. Staff never cause a fee. Offers still open are withdrawn. The order row is
     * locked first and the fee is decided on the locked row against now(), so a cancel can't cross an accept or the end
     * of the free period.
     *
     * @throws ValidationException (errors.status) when the order can no longer be cancelled
     */
    public function handle(Order $order, ?User $actor, ?OrderReason $reason, ?string $note = null): Order
    {
        $canceller = match (true) {
            $actor === null => OrderCanceller::System,
            $actor->isStaff() => OrderCanceller::Staff,
            default => OrderCanceller::Sender,
        };

        if ($canceller === OrderCanceller::Sender && $order->tayar_id !== null) {
            $this->ensureWallets->handle([[$order->tayar_id, WalletType::Tayar], [$order->sender_id, WalletType::Business]]);
        }

        return DB::transaction(function () use ($order, $actor, $reason, $note, $canceller): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $order->canBeCancelledBy($canceller)) {
                throw ValidationException::withMessages(['status' => __('orders.cannot_cancel')]);
            }

            $from = $order->status;
            $cancelFee = $canceller === OrderCanceller::Sender ? $order->senderCancelFeeAt(now()) : BigDecimal::zero()->toScale(2);

            $order->forceFill([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $actor?->id,
                'cancel_reason_id' => $reason?->id,
                'cancel_fee' => (string) $cancelFee,
                'cancel_fee_owed_to_tayar_id' => $cancelFee->isPositive() ? $order->tayar_id : null,
            ])->save();

            $order->statusHistories()->create([
                'from_status' => $from,
                'to_status' => OrderStatus::Cancelled,
                'changed_by' => $actor?->id,
                'note' => $note,
            ]);

            // Bulk update: Offered is the only non-final offer status, so every matched row may move to Withdrawn.
            OrderOffer::query()
                ->where('order_id', $order->id)
                ->where('status', OfferStatus::Offered)
                ->update(['status' => OfferStatus::Withdrawn, 'updated_at' => now()]);

            $this->postCancelFee->handle($order);

            OrderCancelled::dispatch($order, $from, $canceller);

            return $order;
        });
    }
}
