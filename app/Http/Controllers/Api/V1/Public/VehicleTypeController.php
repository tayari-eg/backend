<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Domains\Tayars\Models\VehicleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\ListVehicleTypesRequest;
use App\Http\Resources\Api\V1\Public\VehicleTypeResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VehicleTypeController extends Controller
{
    /**
     * Active vehicle types, with names in the request locale.
     *
     * @unauthenticated
     */
    public function index(ListVehicleTypesRequest $request): AnonymousResourceCollection
    {
        $vehicleTypes = VehicleType::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($request->perPage());

        return VehicleTypeResource::collection($vehicleTypes);
    }
}
