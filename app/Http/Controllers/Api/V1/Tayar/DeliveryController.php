<?php

namespace App\Http\Controllers\Api\V1\Tayar;

use App\Domains\Orders\Actions\ConfirmPickupAction;
use App\Domains\Orders\Actions\ConfirmReceiverAction;
use App\Domains\Orders\Actions\DeliverOrderAction;
use App\Domains\Orders\Actions\MarkOrderNotDeliveredAction;
use App\Domains\Orders\Models\Order;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tayar\ConfirmPickupRequest;
use App\Http\Requests\Api\V1\Tayar\DeliverOrderRequest;
use App\Http\Requests\Api\V1\Tayar\MarkNotDeliveredRequest;
use App\Http\Requests\Api\V1\Tayar\TayarPositionRequest;
use App\Http\Resources\Api\V1\Tayar\OrderResource;
use Illuminate\Support\Facades\Gate;

class DeliveryController extends Controller
{
    /**
     * Confirm the pickup with 1 to 4 photos of the items; the receiver then gets the codes and the tracking link by SMS.
     */
    public function pickup(ConfirmPickupRequest $request, Order $order, ConfirmPickupAction $confirmPickup): OrderResource
    {
        Gate::authorize('viewAsTayar', $order);

        $order = $confirmPickup->handle($order, $request->user(), $request->photos(), $request->lat(), $request->lng());

        return OrderResource::make($order->load(OrderController::RELATIONS));
    }

    /**
     * The receiver confirmed by phone they are there today: the order is on the way.
     */
    public function receiverConfirmed(TayarPositionRequest $request, Order $order, ConfirmReceiverAction $confirmReceiver): OrderResource
    {
        Gate::authorize('viewAsTayar', $order);

        $order = $confirmReceiver->handle($order, $request->user(), $request->lat(), $request->lng());

        return OrderResource::make($order->load(OrderController::RELATIONS));
    }

    /**
     * Deliver with the receiver's delivery code.
     */
    public function deliver(DeliverOrderRequest $request, Order $order, DeliverOrderAction $deliverOrder): OrderResource
    {
        Gate::authorize('viewAsTayar', $order);

        $order = $deliverOrder->handle($order, $request->user(), $request->code(), $request->lat(), $request->lng());

        return OrderResource::make($order->load(OrderController::RELATIONS));
    }

    /**
     * The receiver refused: mark not delivered with the receiver's refusal code and a reason.
     */
    public function notDelivered(MarkNotDeliveredRequest $request, Order $order, MarkOrderNotDeliveredAction $markNotDelivered): OrderResource
    {
        Gate::authorize('viewAsTayar', $order);

        $order = $markNotDelivered->handle($order, $request->user(), $request->code(), $request->notDeliveredReason(), $request->note(), $request->lat(), $request->lng());

        return OrderResource::make($order->load(OrderController::RELATIONS));
    }
}
