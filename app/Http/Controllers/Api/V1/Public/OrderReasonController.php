<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Domains\Orders\Models\OrderReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\ListOrderReasonsRequest;
use App\Http\Resources\Api\V1\Public\OrderReasonResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderReasonController extends Controller
{
    /**
     * Active order reasons of one type (cancel), in display order, with names in the request locale.
     *
     * @unauthenticated
     */
    public function index(ListOrderReasonsRequest $request): AnonymousResourceCollection
    {
        $reasons = OrderReason::query()
            ->active()
            ->ofType($request->type())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($request->perPage());

        return OrderReasonResource::collection($reasons);
    }
}
