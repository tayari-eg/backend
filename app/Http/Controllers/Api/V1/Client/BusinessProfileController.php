<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Domains\Accounts\Actions\CreateBusinessProfileAction;
use App\Domains\Accounts\Actions\UpdateBusinessProfileAction;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\StoreBusinessProfileRequest;
use App\Http\Requests\Api\V1\Client\UpdateBusinessProfileRequest;
use App\Http\Resources\Api\V1\Client\BusinessProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class BusinessProfileController extends Controller
{
    /**
     * @var list<string>
     */
    private const array RELATIONS = ['businessType', 'links', 'media'];

    /**
     * The logged-in business account's profile.
     */
    public function show(Request $request): BusinessProfileResource
    {
        $profile = $request->user()->businessProfile ?? abort(Response::HTTP_NOT_FOUND);

        Gate::authorize('view', $profile);

        return BusinessProfileResource::make($profile->load(self::RELATIONS));
    }

    /**
     * Create the business profile (multipart), with the national ID front and back and the optional tax documents.
     */
    public function store(StoreBusinessProfileRequest $request, CreateBusinessProfileAction $createBusinessProfile): JsonResponse
    {
        $profile = $createBusinessProfile->handle(
            $request->user(),
            $request->profileAttributes(),
            $request->documents(),
            $request->links(),
        );

        return BusinessProfileResource::make($profile->load(self::RELATIONS))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update the business profile. Changing the name, type or tax number sends a reviewed profile back to pending.
     */
    public function update(UpdateBusinessProfileRequest $request, UpdateBusinessProfileAction $updateBusinessProfile): BusinessProfileResource
    {
        /** @var BusinessProfile $profile */
        $profile = $request->user()->businessProfile;

        $profile = $updateBusinessProfile->handle($profile, $request->profileAttributes(), $request->links());

        return BusinessProfileResource::make($profile->load(self::RELATIONS));
    }
}
