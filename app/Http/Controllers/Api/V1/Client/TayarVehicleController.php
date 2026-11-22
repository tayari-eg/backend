<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Domains\Tayars\Actions\UpdateTayarVehicleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\UpdateTayarVehicleRequest;
use App\Http\Resources\Api\V1\TayarProfileResource;

class TayarVehicleController extends Controller
{
    /**
     * Update the vehicle; send only the fields that change. Changing the type or plate sends a reviewed application back to pending.
     */
    public function update(UpdateTayarVehicleRequest $request, UpdateTayarVehicleAction $updateTayarVehicle): TayarProfileResource
    {
        $profile = $request->user()->tayarProfile;

        $updateTayarVehicle->handle($profile->vehicle, $request->vehicleAttributes());

        return TayarProfileResource::make($profile->refresh()->load(TayarProfileController::RELATIONS));
    }
}
