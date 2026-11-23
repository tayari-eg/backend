<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Catalog\Actions\ActivateAreaAction;
use App\Domains\Catalog\Actions\CreateAreaAction;
use App\Domains\Catalog\Actions\DeactivateAreaAction;
use App\Domains\Catalog\Actions\DeleteAreaAction;
use App\Domains\Catalog\Actions\UpdateAreaAction;
use App\Domains\Catalog\Models\Area;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListAreasRequest;
use App\Http\Requests\Api\V1\Admin\StoreAreaRequest;
use App\Http\Requests\Api\V1\Admin\UpdateAreaRequest;
use App\Http\Resources\Api\V1\Admin\AreaResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class AreaController extends Controller
{
    /**
     * Active and inactive areas, in sort order.
     */
    public function index(ListAreasRequest $request): AnonymousResourceCollection
    {
        $areas = Area::query()
            ->withCount('workSessions')
            ->when($request->isActive() !== null, fn ($query) => $query->where('is_active', $request->isActive()))
            ->when($request->search() !== null, fn ($query) => $query->whereNameLike($request->search()))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($request->perPage());

        return AreaResource::collection($areas);
    }

    /**
     * Show one area with its name and city in every language.
     */
    public function show(Area $area): AreaResource
    {
        return AreaResource::make($area->loadCount('workSessions'));
    }

    /**
     * Create an area: a centre point and a radius.
     */
    public function store(StoreAreaRequest $request, CreateAreaAction $createArea): JsonResponse
    {
        return AreaResource::make($createArea->handle($request->validated())->loadCount('workSessions'))
            ->response()
            ->setStatusCode(HttpStatus::HTTP_CREATED);
    }

    /**
     * Update an area; send only the fields that change.
     */
    public function update(UpdateAreaRequest $request, Area $area, UpdateAreaAction $updateArea): AreaResource
    {
        return AreaResource::make($updateArea->handle($area, $request->validated())->loadCount('workSessions'));
    }

    /**
     * Show an area in the tayar's area picker again.
     */
    public function activate(Area $area, ActivateAreaAction $activateArea): AreaResource
    {
        return AreaResource::make($activateArea->handle($area)->loadCount('workSessions'));
    }

    /**
     * Hide an area from the area picker; running sessions keep it.
     */
    public function deactivate(Area $area, DeactivateAreaAction $deactivateArea): AreaResource
    {
        return AreaResource::make($deactivateArea->handle($area)->loadCount('workSessions'));
    }

    /**
     * Delete an area no work session has used.
     */
    public function destroy(Area $area, DeleteAreaAction $deleteArea): Response
    {
        $deleteArea->handle($area);

        return response()->noContent();
    }
}
