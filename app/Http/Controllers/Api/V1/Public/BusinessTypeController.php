<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Domains\Accounts\Models\BusinessType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\ListBusinessTypesRequest;
use App\Http\Resources\Api\V1\Public\BusinessTypeResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BusinessTypeController extends Controller
{
    /**
     * Active business types, with names in the request locale.
     *
     * @unauthenticated
     */
    public function index(ListBusinessTypesRequest $request): AnonymousResourceCollection
    {
        $businessTypes = BusinessType::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($request->perPage());

        return BusinessTypeResource::collection($businessTypes);
    }
}
