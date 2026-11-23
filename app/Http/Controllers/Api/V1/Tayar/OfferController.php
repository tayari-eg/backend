<?php

namespace App\Http\Controllers\Api\V1\Tayar;

use App\Domains\Orders\Actions\AcceptOfferAction;
use App\Domains\Orders\Actions\RejectOfferAction;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\OrderOffer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tayar\ListOffersRequest;
use App\Http\Resources\Api\V1\Tayar\OfferResource;
use App\Http\Resources\Api\V1\Tayar\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class OfferController extends Controller
{
    /**
     * My live offers for the current work session, newest first. Cursor pages.
     */
    public function index(ListOffersRequest $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $profile = $user->tayarProfile()->firstOrFail();
        $workSessionId = $profile->is_online ? $profile->current_work_session_id : null;

        $offers = OrderOffer::query()
            ->live()
            ->where('tayar_id', $user->id)
            // Offline means no current session, so no offers.
            ->when($workSessionId === null, fn ($query) => $query->whereKey([]), fn ($query) => $query->where('work_session_id', $workSessionId))
            ->whereHas('order', fn ($orders) => $orders->where('status', OrderStatus::LookingForTayar))
            ->with(['order.mode', 'order.category'])
            ->orderByDesc('id')
            ->cursorPaginate($request->perPage());

        return OfferResource::collection($offers);
    }

    /**
     * Accept an offer: the order becomes mine (pending) if no one else took it first.
     */
    public function accept(Request $request, OrderOffer $offer, AcceptOfferAction $acceptOffer): OrderResource
    {
        Gate::authorize('respond', $offer);

        $order = $acceptOffer->handle($offer, $request->user());

        return OrderResource::make($order->load(OrderController::RELATIONS));
    }

    /**
     * Reject an offer; this order is not offered to me again.
     */
    public function reject(OrderOffer $offer, RejectOfferAction $rejectOffer): Response
    {
        Gate::authorize('respond', $offer);

        $rejectOffer->handle($offer);

        return response()->noContent();
    }
}
