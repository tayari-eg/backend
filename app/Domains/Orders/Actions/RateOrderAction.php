<?php

namespace App\Domains\Orders\Actions;

use App\Domains\Orders\Enums\RaterRole;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderRating;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RateOrderAction
{
    /**
     * Saves the rater's rating of the other party on a delivered order: the business rates the tayar, the tayar rates the
     * business. A second call within the rating window changes it; a comment staff hid stays hidden, even when the rater
     * changes it, until staff unhide it. The rating stays hidden from the rated party until revealed_at, the end of the
     * window.
     *
     * @throws ValidationException (errors.order) when the order is not delivered, the window is over, or the sender is
     *                             not a business account
     */
    public function handle(Order $order, User $rater, RaterRole $role, int $stars, ?string $comment): OrderRating
    {
        $result = DB::transaction(function () use ($order, $rater, $role, $stars, $comment): OrderRating|ValidationException {
            $order = Order::query()->with('sender')->lockForUpdate()->findOrFail($order->id);

            if (($problem = $order->ratingProblemAt(now())) !== null) {
                return ValidationException::withMessages(['order' => __("orders.{$problem}")]);
            }

            $rating = OrderRating::query()
                ->where('order_id', $order->id)
                ->where('rater_id', $rater->id)
                ->lockForUpdate()
                ->first() ?? new OrderRating;

            $rating->forceFill([
                'order_id' => $order->id,
                'rater_id' => $rater->id,
                'rated_user_id' => $role === RaterRole::Business ? $order->tayar_id : $order->sender_id,
                'rater_role' => $role,
                'stars' => $stars,
                'comment' => $comment,
                'revealed_at' => $order->ratingEndsAt(),
            ]);

            $rating->save();

            return $rating;
        });

        if ($result instanceof ValidationException) {
            throw $result;
        }

        return $result;
    }
}
