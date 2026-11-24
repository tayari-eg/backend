<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Tayars\Models\TayarProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListTayarRankHistoryRequest;
use App\Http\Resources\Api\V1\Admin\TayarRankHistoryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TayarRankHistoryController extends Controller
{
    /**
     * One tayar's rank changes, newest first.
     */
    public function __invoke(ListTayarRankHistoryRequest $request, TayarProfile $tayar): AnonymousResourceCollection
    {
        $histories = $tayar->rankHistories()
            ->with(['fromRank', 'toRank'])
            ->orderByDesc('id')
            ->paginate($request->perPage());

        return TayarRankHistoryResource::collection($histories);
    }
}
