<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Domains\Tayars\Actions\ReplaceVehicleDocumentAction;
use App\Domains\Tayars\Enums\VehicleDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\StoreVehicleDocumentRequest;
use App\Http\Resources\Api\V1\TayarProfileResource;

class VehicleDocumentController extends Controller
{
    /**
     * Replace the vehicle licence, or all the vehicle photos. A reviewed application goes back to pending.
     */
    public function __invoke(StoreVehicleDocumentRequest $request, VehicleDocument $collection, ReplaceVehicleDocumentAction $replaceVehicleDocument): TayarProfileResource
    {
        $profile = $request->user()->tayarProfile;

        $replaceVehicleDocument->handle($profile->vehicle, $collection, $request->uploadedFiles());

        return TayarProfileResource::make($profile->refresh()->load(TayarProfileController::RELATIONS));
    }
}
