<?php

namespace App\Http\Controllers\Api\V1\Tayar;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Tayar\RankStandingResource;
use Illuminate\Http\Request;

class RankController extends Controller
{
    /**
     * My rank, my 30-day delivered orders and rating, what the next rank needs, and every rank.
     */
    public function __invoke(Request $request): RankStandingResource
    {
        return RankStandingResource::make($request->user()->tayarProfile);
    }
}
