<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Orders\Actions\CancelOrderAction;
use App\Domains\Orders\Models\Order;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\CancelOrderRequest;
use App\Http\Requests\Api\V1\Admin\ListOrdersRequest;
use App\Http\Resources\Api\V1\Admin\OrderResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    /**
     * @var list<string>
     */
    private const array LIST_RELATIONS = ['sender.businessProfile', 'category', 'mode', 'tayar', 'workSession.vehicle.vehicleType', 'cancelFeeOwedTo'];

    /**
     * @var list<string>
     */
    private const array DETAIL_RELATIONS = ['sender.businessProfile', 'category', 'mode', 'tayar', 'workSession.vehicle.vehicleType', 'cancelFeeOwedTo', 'canceller', 'cancelReason', 'statusHistories.changer'];

    /**
     * Every order, newest first, with filters and a search by order code or phone.
     */
    public function index(ListOrdersRequest $request): AnonymousResourceCollection
    {
        $phone = $request->searchPhone();
        $code = $request->searchCode();
        // Looked up first so both sides of the OR use an index (receiver_phone, and sender_id).
        $senderIds = $phone !== null ? User::query()->where('phone', $phone->value)->pluck('id') : collect();

        $orders = Order::query()
            ->with(self::LIST_RELATIONS)
            ->when($request->status(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->integerFilter('sender_id'), fn ($query, int $senderId) => $query->where('sender_id', $senderId))
            ->when($request->integerFilter('tayar_id'), fn ($query, int $tayarId) => $query->where('tayar_id', $tayarId))
            ->when($request->integerFilter('category_id'), fn ($query, int $categoryId) => $query->where('category_id', $categoryId))
            ->when($request->integerFilter('mode_id'), fn ($query, int $modeId) => $query->where('mode_id', $modeId))
            ->when($request->createdFrom(), fn ($query, $from) => $query->where('created_at', '>=', $from))
            ->when($request->createdTo(), fn ($query, $to) => $query->where('created_at', '<=', $to))
            ->when($phone !== null, fn ($query) => $query->where(fn ($matches) => $matches
                ->where('receiver_phone', $phone->value)
                ->orWhereIn('sender_id', $senderIds)))
            ->when($code !== null, fn ($query) => $query->where('code', $code))
            ->orderByDesc('id')
            ->paginate($request->perPage());

        return OrderResource::collection($orders);
    }

    /**
     * One order with its fee breakdown and full status history.
     */
    public function show(Order $order): OrderResource
    {
        return OrderResource::make($order->load(self::DETAIL_RELATIONS));
    }

    /**
     * Cancel an order before pickup, with a cancel reason and an optional note, never with a fee; an accepted order keeps its tayar.
     */
    public function cancel(CancelOrderRequest $request, Order $order, CancelOrderAction $cancelOrder): OrderResource
    {
        $order = $cancelOrder->handle($order, $request->user(), $request->cancelReason(), $request->note());

        return OrderResource::make($order->load(self::DETAIL_RELATIONS));
    }
}
