<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Orders\Models\Order;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListOrderOffersRequest;
use App\Http\Resources\Api\V1\Admin\OrderOfferResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderOfferController extends Controller
{
    /**
     * Every offer of one order, newest first: who got it, in which round, and what they did with it.
     */
    public function __invoke(ListOrderOffersRequest $request, Order $order): AnonymousResourceCollection
    {
        $offers = $order->offers()
            ->with('tayar')
            ->orderByDesc('id')
            ->paginate($request->perPage());

        return OrderOfferResource::collection($offers);
    }
}
