<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Domains\Orders\Actions\CancelOrderAction;
use App\Domains\Orders\Actions\CreateOrderAction;
use App\Domains\Orders\Models\Order;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\Orders\CancelOrderRequest;
use App\Http\Requests\Api\V1\Client\Orders\ListOrdersRequest;
use App\Http\Requests\Api\V1\Client\Orders\StoreOrderRequest;
use App\Http\Resources\Api\V1\Client\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    /**
     * @var list<string>
     */
    private const array RELATIONS = ['category', 'cancelReason', 'notDeliveredReason', 'tayar.tayarProfile', 'workSession.vehicle.vehicleType', 'media', 'ratings', 'sender'];

    /**
     * My orders, newest first: active (default) or past. Cursor pages.
     */
    public function index(ListOrdersRequest $request): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->where('sender_id', $request->user()->id)
            ->whereStatusIn($request->statuses())
            ->with(self::RELATIONS)
            ->orderByDesc('id')
            ->cursorPaginate($request->perPage());

        return OrderResource::collection($orders);
    }

    /**
     * One of my orders.
     */
    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        return OrderResource::make($order->load(self::RELATIONS));
    }

    /**
     * Create an order at the shipping fee from Estimate price. Sending the same idempotency_key again returns the
     * order already created (200) instead of a second one (201).
     */
    public function store(StoreOrderRequest $request, CreateOrderAction $createOrder): JsonResponse
    {
        $order = $createOrder->handle($request->user(), $request->orderAttributes());

        return OrderResource::make($order->load(self::RELATIONS))
            ->response()
            ->setStatusCode($order->wasRecentlyCreated ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    /**
     * Cancel one of my orders before pickup, with a cancel reason. After a tayar accepted, it is free for a short time;
     * later the cancel fee in cancel_fee_now is recorded as owed to the tayar.
     */
    public function cancel(CancelOrderRequest $request, Order $order, CancelOrderAction $cancelOrder): OrderResource
    {
        $order = $cancelOrder->handle($order, $request->user(), $request->cancelReason(), $request->note());

        return OrderResource::make($order->load(self::RELATIONS));
    }
}
