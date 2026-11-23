<?php

namespace App\Http\Resources\Api\V1\Client;

use App\Domains\Pricing\Data\ShippingQuote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The price shown before an order is created. The mode is never shown: the customer doesn't pick one.
 *
 * @property ShippingQuote $resource
 */
class OrderEstimateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'distance_km' => (string) $this->resource->distanceKm,
            'food_extra' => (string) $this->resource->foodExtra,
            'shipping_fee' => (string) $this->resource->shippingFee,
        ];
    }
}
