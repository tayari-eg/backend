<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Accounts\Actions\ApproveBusinessProfileAction;
use App\Domains\Accounts\Actions\RejectBusinessProfileAction;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ApproveBusinessProfileRequest;
use App\Http\Requests\Api\V1\Admin\ListBusinessProfilesRequest;
use App\Http\Requests\Api\V1\Admin\RejectBusinessProfileRequest;
use App\Http\Resources\Api\V1\Client\BusinessProfileResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BusinessProfileReviewController extends Controller
{
    /**
     * @var list<string>
     */
    private const array RELATIONS = ['user', 'businessType', 'links', 'media'];

    /**
     * Business profiles by verification status (pending by default), oldest first.
     */
    public function index(ListBusinessProfilesRequest $request): AnonymousResourceCollection
    {
        $profiles = BusinessProfile::query()
            ->with(self::RELATIONS)
            ->where('verification_status', $request->status())
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate($request->perPage());

        return BusinessProfileResource::collection($profiles);
    }

    public function approve(ApproveBusinessProfileRequest $request, BusinessProfile $businessProfile, ApproveBusinessProfileAction $approveBusinessProfile): BusinessProfileResource
    {
        $profile = $approveBusinessProfile->handle($businessProfile, $request->user());

        return BusinessProfileResource::make($profile->load(self::RELATIONS));
    }

    public function reject(RejectBusinessProfileRequest $request, BusinessProfile $businessProfile, RejectBusinessProfileAction $rejectBusinessProfile): BusinessProfileResource
    {
        $profile = $rejectBusinessProfile->handle($businessProfile, $request->user(), $request->validated('reason'));

        return BusinessProfileResource::make($profile->load(self::RELATIONS));
    }
}
