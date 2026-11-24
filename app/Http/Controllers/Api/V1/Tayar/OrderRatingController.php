<?php

namespace App\Http\Controllers\Api\V1\Tayar;

use App\Domains\Orders\Actions\RateOrderAction;
use App\Domains\Orders\Enums\RaterRole;
use App\Domains\Orders\Models\Order;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tayar\RateOrderRequest;
use App\Http\Resources\Api\V1\OrderRatingResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OrderRatingController extends Controller
{
    /**
     * Rate the business of an order I delivered, or change my rating, within the rating window. The business sees it
     * only once the window ends.
     */
    public function update(RateOrderRequest $request, Order $order, RateOrderAction $rateOrder): JsonResponse
    {
        $rating = $rateOrder->handle($order, $request->user(), RaterRole::Tayar, $request->stars(), $request->comment());

        return OrderRatingResource::make($rating)
            ->response()
            ->setStatusCode($rating->wasRecentlyCreated ? Response::HTTP_CREATED : Response::HTTP_OK);
    }
}
