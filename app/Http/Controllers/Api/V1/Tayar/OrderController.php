<?php

namespace App\Http\Controllers\Api\V1\Tayar;

use App\Domains\Orders\Models\Order;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tayar\ListOrdersRequest;
use App\Http\Resources\Api\V1\Tayar\OrderResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    /**
     * @var list<string>
     */
    public const array RELATIONS = ['mode', 'category', 'sender.businessProfile', 'notDeliveredReason', 'media'];

    /**
     * The orders I accepted, newest first: active (default) or past. Cursor pages.
     */
    public function index(ListOrdersRequest $request): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->where('tayar_id', $request->user()->id)
            ->whereStatusIn($request->statuses())
            ->with(self::RELATIONS)
            ->orderByDesc('id')
            ->cursorPaginate($request->perPage());

        return OrderResource::collection($orders);
    }

    /**
     * One order I accepted.
     */
    public function show(Order $order): OrderResource
    {
        Gate::authorize('viewAsTayar', $order);

        return OrderResource::make($order->load(self::RELATIONS));
    }
}
