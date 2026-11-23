<?php

namespace App\Http\Requests\Api\V1\Client\Orders;

use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Orders\Enums\ShippingPaidBy;
use App\Domains\Orders\Models\Order;
use App\Http\Requests\Concerns\ValidatesOrderRoute;
use App\Rules\EgyptianMobileNumber;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    use ValidatesOrderRoute;

    /**
     * Keeps goods_value plus the shipping fee inside decimal(10,2).
     */
    private const string MAX_GOODS_VALUE = '1000000';

    /**
     * @var list<string>
     */
    private const array PHONE_FIELDS = ['receiver_phone', 'pickup_contact_phone'];

    public function authorize(): Response
    {
        return Gate::forUser($this->user())->inspect('create', Order::class);
    }

    protected function prepareForValidation(): void
    {
        foreach (self::PHONE_FIELDS as $field) {
            $phone = PhoneNumber::tryFrom($this->input($field));

            if ($phone !== null) {
                $this->merge([$field => $phone->value]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            ...$this->orderRouteRules(),
            'details' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'pickup_address' => ['required', 'string', 'max:255'],
            'pickup_contact_phone' => ['sometimes', 'nullable', new EgyptianMobileNumber],
            'dropoff_address' => ['required', 'string', 'max:255'],
            'receiver_name' => ['required', 'string', 'max:255'],
            'receiver_phone' => ['required', new EgyptianMobileNumber],
            'goods_value' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:'.self::MAX_GOODS_VALUE],
            'shipping_paid_by' => ['required', Rule::enum(ShippingPaidBy::class)],
            'shipping_fee' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
        ];
    }

    /**
     * The validated fields with amounts and coordinates as decimal strings, so no float reaches the Action.
     *
     * @return array{idempotency_key: string, category_id: int, details: string|null, pickup_address: string, pickup_lat: string, pickup_lng: string, pickup_contact_phone: string|null, dropoff_address: string, dropoff_lat: string, dropoff_lng: string, receiver_name: string, receiver_phone: string, goods_value: string, shipping_paid_by: string, shipping_fee: string}
     */
    public function orderAttributes(): array
    {
        return [
            'idempotency_key' => strtolower((string) $this->validated('idempotency_key')),
            'category_id' => (int) $this->validated('category_id'),
            'details' => $this->validated('details'),
            'pickup_address' => (string) $this->validated('pickup_address'),
            'pickup_lat' => $this->pickup()->lat,
            'pickup_lng' => $this->pickup()->lng,
            'pickup_contact_phone' => $this->validated('pickup_contact_phone'),
            'dropoff_address' => (string) $this->validated('dropoff_address'),
            'dropoff_lat' => $this->dropoff()->lat,
            'dropoff_lng' => $this->dropoff()->lng,
            'receiver_name' => (string) $this->validated('receiver_name'),
            'receiver_phone' => (string) $this->validated('receiver_phone'),
            'goods_value' => (string) $this->validated('goods_value'),
            'shipping_paid_by' => (string) $this->validated('shipping_paid_by'),
            'shipping_fee' => (string) $this->validated('shipping_fee'),
        ];
    }
}
