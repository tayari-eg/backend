<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Accounts\Actions\ActivateBusinessTypeAction;
use App\Domains\Accounts\Actions\CreateBusinessTypeAction;
use App\Domains\Accounts\Actions\DeactivateBusinessTypeAction;
use App\Domains\Accounts\Actions\DeleteBusinessTypeAction;
use App\Domains\Accounts\Actions\UpdateBusinessTypeAction;
use App\Domains\Accounts\Models\BusinessType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListBusinessTypesRequest;
use App\Http\Requests\Api\V1\Admin\StoreBusinessTypeRequest;
use App\Http\Requests\Api\V1\Admin\UpdateBusinessTypeRequest;
use App\Http\Resources\Api\V1\Admin\BusinessTypeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class BusinessTypeController extends Controller
{
    /**
     * Active and inactive business types, in sort order.
     */
    public function index(ListBusinessTypesRequest $request): AnonymousResourceCollection
    {
        $businessTypes = BusinessType::query()
            ->withCount('businessProfiles')
            ->when($request->isActive() !== null, fn ($query) => $query->where('is_active', $request->isActive()))
            ->when($request->search() !== null, fn ($query) => $query->whereNameLike($request->search()))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($request->perPage());

        return BusinessTypeResource::collection($businessTypes);
    }

    /**
     * Show one business type with its names in every language.
     */
    public function show(BusinessType $businessType): BusinessTypeResource
    {
        return BusinessTypeResource::make($businessType->loadCount('businessProfiles'));
    }

    /**
     * Create a business type.
     */
    public function store(StoreBusinessTypeRequest $request, CreateBusinessTypeAction $createBusinessType): JsonResponse
    {
        $businessType = $createBusinessType->handle(
            $request->validated('name'),
            $request->boolean('is_active', true),
            $request->has('sort_order') ? (int) $request->validated('sort_order') : null,
        );

        return BusinessTypeResource::make($businessType->loadCount('businessProfiles'))
            ->response()
            ->setStatusCode(HttpStatus::HTTP_CREATED);
    }

    /**
     * Update a business type; send only the fields that change.
     */
    public function update(UpdateBusinessTypeRequest $request, BusinessType $businessType, UpdateBusinessTypeAction $updateBusinessType): BusinessTypeResource
    {
        return BusinessTypeResource::make($updateBusinessType->handle($businessType, $request->validated())->loadCount('businessProfiles'));
    }

    /**
     * Show a business type in the public list again.
     */
    public function activate(BusinessType $businessType, ActivateBusinessTypeAction $activateBusinessType): BusinessTypeResource
    {
        return BusinessTypeResource::make($activateBusinessType->handle($businessType)->loadCount('businessProfiles'));
    }

    /**
     * Hide a business type from the public list; existing profiles keep it.
     */
    public function deactivate(BusinessType $businessType, DeactivateBusinessTypeAction $deactivateBusinessType): BusinessTypeResource
    {
        return BusinessTypeResource::make($deactivateBusinessType->handle($businessType)->loadCount('businessProfiles'));
    }

    /**
     * Delete a business type that no business profile uses.
     */
    public function destroy(BusinessType $businessType, DeleteBusinessTypeAction $deleteBusinessType): Response
    {
        $deleteBusinessType->handle($businessType);

        return response()->noContent();
    }
}
