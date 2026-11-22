<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Tayars\Actions\ApproveTayarApplicationAction;
use App\Domains\Tayars\Actions\RejectTayarApplicationAction;
use App\Domains\Tayars\Models\TayarProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ApproveTayarApplicationRequest;
use App\Http\Requests\Api\V1\Admin\ListTayarApplicationsRequest;
use App\Http\Requests\Api\V1\Admin\RejectTayarApplicationRequest;
use App\Http\Resources\Api\V1\TayarProfileResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TayarApplicationReviewController extends Controller
{
    /**
     * @var list<string>
     */
    private const array LIST_RELATIONS = ['user', 'media', 'vehicle.vehicleType', 'vehicle.media'];

    /**
     * @var list<string>
     */
    private const array DETAIL_RELATIONS = [...self::LIST_RELATIONS, 'reviewer', 'reviews.reviewer'];

    /**
     * Tayar applications by status (pending by default), oldest first.
     */
    public function index(ListTayarApplicationsRequest $request): AnonymousResourceCollection
    {
        $applications = TayarProfile::query()
            ->with(self::LIST_RELATIONS)
            ->where('status', $request->status())
            ->when($request->validated('vehicle_type_id'), fn ($query, $vehicleTypeId) => $query
                ->whereHas('vehicles', fn ($vehicles) => $vehicles->where('vehicle_type_id', $vehicleTypeId)))
            ->when($request->validated('search') !== null, fn ($query) => $query
                ->whereHas('user', fn ($users) => $users->whereMatchesSearch($request->searchPhone(), $request->searchPhoneDigits(), $request->searchName())))
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate($request->perPage());

        return TayarProfileResource::collection($applications);
    }

    /**
     * Show one tayar application with every document and the review history.
     */
    public function show(TayarProfile $tayarProfile): TayarProfileResource
    {
        return TayarProfileResource::make($tayarProfile->load(self::DETAIL_RELATIONS));
    }

    /**
     * Approve a pending tayar application and its vehicle.
     */
    public function approve(ApproveTayarApplicationRequest $request, TayarProfile $tayarProfile, ApproveTayarApplicationAction $approveTayarApplication): TayarProfileResource
    {
        $profile = $approveTayarApplication->handle($tayarProfile, $request->user());

        return TayarProfileResource::make($profile->load(self::DETAIL_RELATIONS));
    }

    /**
     * Reject a pending tayar application with a reason the applicant sees.
     */
    public function reject(RejectTayarApplicationRequest $request, TayarProfile $tayarProfile, RejectTayarApplicationAction $rejectTayarApplication): TayarProfileResource
    {
        $profile = $rejectTayarApplication->handle($tayarProfile, $request->user(), $request->validated('reason'));

        return TayarProfileResource::make($profile->load(self::DETAIL_RELATIONS));
    }
}
