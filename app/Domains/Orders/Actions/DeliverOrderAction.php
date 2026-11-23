<?php

namespace App\Domains\Orders\Actions;

use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Enums\ReceiverCode;
use App\Domains\Orders\Events\OrderStatusChanged;
use App\Domains\Orders\Models\Order;
use App\Domains\Wallet\Actions\EnsureWalletsAction;
use App\Domains\Wallet\Actions\PostOrderDeliveredAction;
use App\Domains\Wallet\Enums\SystemWallet;
use App\Domains\Wallet\Enums\WalletType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliverOrderAction
{
    public function __construct(private EnsureWalletsAction $ensureWallets, private PostOrderDeliveredAction $postOrderDelivered) {}

    /**
     * The receiver gave the delivery code: delivered, and the code hashes are cleared. A wrong code (the refusal code
     * included) counts against tayari.tracking.code_max_attempts, shared with the refusal step; at the limit the codes
     * lock until new ones are sent. Delivery does not check payment yet. The order's money is posted to the wallets in
     * the same transaction; the wallets are created before it opens. The order row is locked first, then the wallets.
     *
     * @throws ValidationException (errors.status) when the order is not this tayar's ongoing order, or (errors.code) when
     *                             the code is wrong, locked, expired or not sent yet
     */
    public function handle(Order $order, User $tayar, #[\SensitiveParameter] string $code, float $lat, float $lng): Order
    {
        if ($order->tayar_id === $tayar->id) {
            $this->ensureWallets->handle([[$order->sender_id, WalletType::Business], [$tayar->id, WalletType::Tayar]], [SystemWallet::Revenue]);
        }

        $result = DB::transaction(function () use ($order, $tayar, $code, $lat, $lng): Order|ValidationException {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->tayar_id !== $tayar->id || ! $order->status->tayarCanMoveTo(OrderStatus::Delivered)) {
                return ValidationException::withMessages(['status' => __('orders.wrong_step')]);
            }

            if (($problem = $order->receiverCodesProblem()) !== null) {
                return ValidationException::withMessages(['code' => __("orders.{$problem}")]);
            }

            if (! $order->matchesReceiverCode(ReceiverCode::Delivery, $code)) {
                // Committed with the refusal, so the attempt counts.
                $order->increment('code_attempts');

                return ValidationException::withMessages(['code' => __('orders.code_wrong', ['left' => $order->codeAttemptsLeft()])]);
            }

            $from = $order->status;
            $order->moveTo(OrderStatus::Delivered, [
                'delivered_at' => now(),
                'delivery_code_hash' => null,
                'refusal_code_hash' => null,
            ], $tayar->id, lat: $lat, lng: $lng);

            $this->postOrderDelivered->handle($order);

            OrderStatusChanged::dispatch($order, $from, OrderStatus::Delivered, $tayar);

            return $order;
        });

        if ($result instanceof ValidationException) {
            throw $result;
        }

        return $result;
    }
}
