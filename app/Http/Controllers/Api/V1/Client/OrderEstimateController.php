<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Domains\Pricing\Actions\CalculateShippingFeeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\Orders\EstimateOrderRequest;
use App\Http\Resources\Api\V1\Client\OrderEstimateResource;

class OrderEstimateController extends Controller
{
    /**
     * Price a trip before creating the order; nothing is saved.
     */
    public function __invoke(EstimateOrderRequest $request, CalculateShippingFeeAction $calculateShippingFee): OrderEstimateResource
    {
        return OrderEstimateResource::make($calculateShippingFee->handle($request->pickup(), $request->dropoff(), $request->category()));
    }
}
