<?php

namespace App\Domains\Orders\Actions;

use App\Domains\Catalog\Models\Category;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Enums\ShippingPaidBy;
use App\Domains\Orders\Models\Order;
use App\Domains\Pricing\Actions\CalculateShippingFeeAction;
use App\Domains\Pricing\Data\GeoPoint;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrderAction
{
    /**
     * A new code is drawn when a code is already taken; one clash in a million codes is the expected rate.
     */
    private const int MAX_ATTEMPTS = 3;

    public function __construct(private CalculateShippingFeeAction $calculateShippingFee) {}

    /**
     * Creates the order in looking_for_tayar at the fee the sender saw. The same idempotency key from the same sender
     * returns the order it already created (wasRecentlyCreated is false), even for two taps racing each other.
     * Amounts and coordinates arrive as decimal strings.
     *
     * @param  array{idempotency_key: string, category_id: int, details?: string|null, pickup_address: string, pickup_lat: string, pickup_lng: string, pickup_contact_phone?: string|null, dropoff_address: string, dropoff_lat: string, dropoff_lng: string, receiver_name: string, receiver_phone: string, goods_value: string, shipping_paid_by: string, shipping_fee: string}  $attributes
     *
     * @throws ValidationException when the route can't be priced, the fee changed (errors.shipping_fee), or the key's order was deleted (errors.idempotency_key)
     */
    public function handle(User $sender, array $attributes): Order
    {
        $existing = $this->existingOrder($sender, $attributes['idempotency_key']);

        if ($existing !== null) {
            return $this->unlessDeleted($existing);
        }

        $category = Category::query()->findOrFail($attributes['category_id']);
        $quote = $this->calculateShippingFee->handle(
            new GeoPoint($attributes['pickup_lat'], $attributes['pickup_lng']),
            new GeoPoint($attributes['dropoff_lat'], $attributes['dropoff_lng']),
            $category,
        );

        if (! $quote->shippingFee->isEqualTo($attributes['shipping_fee'])) {
            throw ValidationException::withMessages(['shipping_fee' => __('orders.price_changed', ['fee' => (string) $quote->shippingFee])]);
        }

        $goodsValue = BigDecimal::of($attributes['goods_value'])->toScale(2);
        $shippingPaidBy = ShippingPaidBy::from($attributes['shipping_paid_by']);
        $amountToCollect = $shippingPaidBy === ShippingPaidBy::Receiver ? $goodsValue->plus($quote->shippingFee) : $goodsValue;

        $orderAttributes = [
            ...$attributes,
            ...$quote->toOrderAttributes(),
            'goods_value' => (string) $goodsValue,
            'amount_to_collect' => (string) $amountToCollect,
            'receiver_user_id' => User::query()->where('phone', $attributes['receiver_phone'])->value('id'),
        ];

        for ($attempt = 1; ; $attempt++) {
            try {
                return $this->insert($sender, $orderAttributes);
            } catch (UniqueConstraintViolationException $exception) {
                // Caught outside the transaction: PostgreSQL aborts a transaction after a failed insert.
                $existing = $this->existingOrder($sender, $attributes['idempotency_key']);

                if ($existing !== null) {
                    return $this->unlessDeleted($existing);
                }

                if ($attempt === self::MAX_ATTEMPTS) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $orderAttributes
     */
    private function insert(User $sender, array $orderAttributes): Order
    {
        return DB::transaction(function () use ($sender, $orderAttributes): Order {
            $order = new Order($orderAttributes);
            $order->forceFill([
                'code' => Order::newCode(),
                'sender_id' => $sender->id,
                'status' => OrderStatus::LookingForTayar,
                'price_accepted_at' => now(),
            ])->save();

            $order->statusHistories()->create([
                'from_status' => null,
                'to_status' => OrderStatus::LookingForTayar,
                'changed_by' => $sender->id,
            ]);

            return $order;
        });
    }

    /**
     * Includes deleted orders: the unique index still holds their key.
     */
    protected function existingOrder(User $sender, string $idempotencyKey): ?Order
    {
        return Order::withTrashed()->where('sender_id', $sender->id)->where('idempotency_key', $idempotencyKey)->first();
    }

    /**
     * @throws ValidationException (errors.idempotency_key) when the order for this key was deleted
     */
    private function unlessDeleted(Order $order): Order
    {
        if ($order->trashed()) {
            throw ValidationException::withMessages(['idempotency_key' => __('orders.idempotency_key_deleted')]);
        }

        return $order;
    }
}
