<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Domains\Tayars\Actions\SubmitTayarApplicationAction;
use App\Domains\Tayars\Actions\UpdateTayarApplicationAction;
use App\Domains\Tayars\Models\TayarProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\StoreTayarProfileRequest;
use App\Http\Requests\Api\V1\Client\UpdateTayarProfileRequest;
use App\Http\Resources\Api\V1\TayarProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TayarProfileController extends Controller
{
    /**
     * @var list<string>
     */
    public const array RELATIONS = ['media', 'vehicle.vehicleType', 'vehicle.media'];

    /**
     * The logged-in user's tayar application and its status.
     */
    public function show(Request $request): TayarProfileResource
    {
        /** @var TayarProfile|null $profile */
        $profile = $request->user()->tayarProfile ?? abort(Response::HTTP_NOT_FOUND);

        return TayarProfileResource::make($profile->load(self::RELATIONS));
    }

    /**
     * Apply to be a tayar (multipart): national ID number, documents and the first vehicle.
     */
    public function store(StoreTayarProfileRequest $request, SubmitTayarApplicationAction $submitTayarApplication): JsonResponse
    {
        $profile = $submitTayarApplication->handle(
            $request->user(),
            $request->validated('national_id_number'),
            $request->vehicleAttributes(),
            $request->documents(),
            $request->vehicleDocuments(),
        );

        return TayarProfileResource::make($profile->load(self::RELATIONS))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Change the national ID number. A reviewed application goes back to pending.
     */
    public function update(UpdateTayarProfileRequest $request, UpdateTayarApplicationAction $updateTayarApplication): TayarProfileResource
    {
        $profile = $updateTayarApplication->handle($request->user()->tayarProfile, $request->validated());

        return TayarProfileResource::make($profile->load(self::RELATIONS));
    }
}
