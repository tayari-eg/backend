<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Incentives\Actions\UpdateTayarRankAction;
use App\Domains\Incentives\Models\TayarRank;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateTayarRankRequest;
use App\Http\Resources\Api\V1\Admin\TayarRankResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TayarRankController extends Controller
{
    /**
     * Every tayar rank, lowest level first, with how many tayars hold it.
     */
    public function index(): AnonymousResourceCollection
    {
        return TayarRankResource::collection(TayarRank::query()->withCount('tayarProfiles')->orderBy('level')->get());
    }

    /**
     * Show one rank with its names in every language.
     */
    public function show(TayarRank $tayarRank): TayarRankResource
    {
        return TayarRankResource::make($tayarRank->loadCount('tayarProfiles'));
    }

    /**
     * Update a rank's name, thresholds or colour; send only the fields that change. Tayars move at the next nightly run.
     */
    public function update(UpdateTayarRankRequest $request, TayarRank $tayarRank, UpdateTayarRankAction $updateTayarRank): TayarRankResource
    {
        return TayarRankResource::make($updateTayarRank->handle($tayarRank, $request->validated())->loadCount('tayarProfiles'));
    }
}
