<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Domains\Catalog\Models\Area;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\ListAreasRequest;
use App\Http\Resources\Api\V1\Public\AreaResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AreaController extends Controller
{
    /**
     * Active areas for the area mode picker, with names in the request locale.
     *
     * @unauthenticated
     */
    public function index(ListAreasRequest $request): AnonymousResourceCollection
    {
        $areas = Area::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($request->perPage());

        return AreaResource::collection($areas);
    }
}
