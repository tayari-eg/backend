<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Domains\Catalog\Models\Mode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\ListModesRequest;
use App\Http\Resources\Api\V1\Public\ModeResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ModeController extends Controller
{
    /**
     * Active modes for the tayar's mode picker, with what Go online needs for each.
     *
     * @unauthenticated
     */
    public function index(ListModesRequest $request): AnonymousResourceCollection
    {
        $modes = Mode::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($request->perPage());

        return ModeResource::collection($modes);
    }
}
