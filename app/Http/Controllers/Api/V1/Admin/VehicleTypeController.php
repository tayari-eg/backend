<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Tayars\Actions\ActivateVehicleTypeAction;
use App\Domains\Tayars\Actions\CreateVehicleTypeAction;
use App\Domains\Tayars\Actions\DeactivateVehicleTypeAction;
use App\Domains\Tayars\Actions\DeleteVehicleTypeAction;
use App\Domains\Tayars\Actions\UpdateVehicleTypeAction;
use App\Domains\Tayars\Models\VehicleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListVehicleTypesRequest;
use App\Http\Requests\Api\V1\Admin\StoreVehicleTypeRequest;
use App\Http\Requests\Api\V1\Admin\UpdateVehicleTypeRequest;
use App\Http\Resources\Api\V1\Admin\VehicleTypeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class VehicleTypeController extends Controller
{
    /**
     * Active and inactive vehicle types, in sort order.
     */
    public function index(ListVehicleTypesRequest $request): AnonymousResourceCollection
    {
        $vehicleTypes = VehicleType::query()
            ->withCount('vehicles')
            ->when($request->isActive() !== null, fn ($query) => $query->where('is_active', $request->isActive()))
            ->when($request->search() !== null, fn ($query) => $query->whereNameLike($request->search()))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($request->perPage());

        return VehicleTypeResource::collection($vehicleTypes);
    }

    /**
     * Show one vehicle type with its names in every language.
     */
    public function show(VehicleType $vehicleType): VehicleTypeResource
    {
        return VehicleTypeResource::make($vehicleType->loadCount('vehicles'));
    }

    /**
     * Create a vehicle type.
     */
    public function store(StoreVehicleTypeRequest $request, CreateVehicleTypeAction $createVehicleType): JsonResponse
    {
        $vehicleType = $createVehicleType->handle(
            $request->validated('name'),
            $request->boolean('requires_license', true),
            $request->boolean('can_carry_food', true),
            $request->boolean('is_active', true),
            $request->has('sort_order') ? (int) $request->validated('sort_order') : null,
        );

        return VehicleTypeResource::make($vehicleType->loadCount('vehicles'))
            ->response()
            ->setStatusCode(HttpStatus::HTTP_CREATED);
    }

    /**
     * Update a vehicle type; send only the fields that change.
     */
    public function update(UpdateVehicleTypeRequest $request, VehicleType $vehicleType, UpdateVehicleTypeAction $updateVehicleType): VehicleTypeResource
    {
        return VehicleTypeResource::make($updateVehicleType->handle($vehicleType, $request->validated())->loadCount('vehicles'));
    }

    /**
     * Show a vehicle type in the public list again.
     */
    public function activate(VehicleType $vehicleType, ActivateVehicleTypeAction $activateVehicleType): VehicleTypeResource
    {
        return VehicleTypeResource::make($activateVehicleType->handle($vehicleType)->loadCount('vehicles'));
    }

    /**
     * Hide a vehicle type from the public list; existing vehicles keep it.
     */
    public function deactivate(VehicleType $vehicleType, DeactivateVehicleTypeAction $deactivateVehicleType): VehicleTypeResource
    {
        return VehicleTypeResource::make($deactivateVehicleType->handle($vehicleType)->loadCount('vehicles'));
    }

    /**
     * Delete a vehicle type that no vehicle uses.
     */
    public function destroy(VehicleType $vehicleType, DeleteVehicleTypeAction $deleteVehicleType): Response
    {
        $deleteVehicleType->handle($vehicleType);

        return response()->noContent();
    }
}
