<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Accounts\Enums\UserStatus;
use App\Domains\Tayars\Models\TayarProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListTayarsRequest;
use App\Http\Resources\Api\V1\TayarProfileResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TayarController extends Controller
{
    /**
     * Approved tayars, newest first.
     */
    public function index(ListTayarsRequest $request): AnonymousResourceCollection
    {
        $tayars = TayarProfile::query()
            ->approved()
            ->with(['user', 'vehicle.vehicleType', 'rank'])
            ->when($request->isOnline() !== null, fn ($query) => $query->where('is_online', $request->isOnline()))
            ->when($request->validated('vehicle_type_id'), fn ($query, $vehicleTypeId) => $query
                ->whereHas('vehicles', fn ($vehicles) => $vehicles->where('vehicle_type_id', $vehicleTypeId)))
            ->when($request->validated('rank_id'), fn ($query, $rankId) => $query->where('rank_id', $rankId))
            ->when($request->blocked() !== null, fn ($query) => $query
                ->whereHas('user', fn ($users) => $users->where('status', $request->blocked() ? UserStatus::Blocked : UserStatus::Active)))
            ->when($request->validated('search') !== null, fn ($query) => $query
                ->whereHas('user', fn ($users) => $users->whereMatchesSearch($request->searchPhone(), $request->searchPhoneDigits(), $request->searchName())))
            ->orderByDesc('id')
            ->paginate($request->perPage());

        return TayarProfileResource::collection($tayars);
    }

    /**
     * Show one approved tayar with the documents, the vehicle, the last position and the review history.
     */
    public function show(TayarProfile $tayar): TayarProfileResource
    {
        return TayarProfileResource::make($tayar->load(['user', 'media', 'vehicle.vehicleType', 'vehicle.media', 'reviewer', 'reviews.reviewer', 'rank']));
    }
}
