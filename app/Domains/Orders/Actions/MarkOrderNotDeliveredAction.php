<?php

namespace App\Domains\Orders\Actions;

use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Enums\ReceiverCode;
use App\Domains\Orders\Events\OrderStatusChanged;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderReason;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkOrderNotDeliveredAction
{
    /**
     * The receiver refused at the door and gave the refusal code: not_delivered with the reason and note, and the code
     * hashes are cleared. Final for now: returning the goods comes later. Wrong codes count like in DeliverOrderAction.
     *
     * @throws ValidationException (errors.status) when the order is not this tayar's ongoing order, or (errors.code) when
     *                             the code is wrong, locked, expired or not sent yet
     */
    public function handle(Order $order, User $tayar, #[\SensitiveParameter] string $code, OrderReason $reason, ?string $note, float $lat, float $lng): Order
    {
        $result = DB::transaction(function () use ($order, $tayar, $code, $reason, $note, $lat, $lng): Order|ValidationException {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->tayar_id !== $tayar->id || ! $order->status->tayarCanMoveTo(OrderStatus::NotDelivered)) {
                return ValidationException::withMessages(['status' => __('orders.wrong_step')]);
            }

            if (($problem = $order->receiverCodesProblem()) !== null) {
                return ValidationException::withMessages(['code' => __("orders.{$problem}")]);
            }

            if (! $order->matchesReceiverCode(ReceiverCode::Refusal, $code)) {
                // Committed with the refusal, so the attempt counts.
                $order->increment('code_attempts');

                return ValidationException::withMessages(['code' => __('orders.code_wrong', ['left' => $order->codeAttemptsLeft()])]);
            }

            $from = $order->status;
            $order->moveTo(OrderStatus::NotDelivered, [
                'not_delivered_at' => now(),
                'not_delivered_reason_id' => $reason->id,
                'not_delivered_note' => $note,
                'delivery_code_hash' => null,
                'refusal_code_hash' => null,
            ], $tayar->id, $note, $lat, $lng);

            OrderStatusChanged::dispatch($order, $from, OrderStatus::NotDelivered, $tayar);

            return $order;
        });

        if ($result instanceof ValidationException) {
            throw $result;
        }

        return $result;
    }
}
