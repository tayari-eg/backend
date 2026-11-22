<?php

namespace App\Http\Controllers\Api\V1\Tayar;

use App\Domains\Tayars\Actions\UpdateTayarLocationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tayar\TayarPositionRequest;
use Illuminate\Http\Response;

class LocationController extends Controller
{
    /**
     * Send the current position while online.
     */
    public function __invoke(TayarPositionRequest $request, UpdateTayarLocationAction $updateTayarLocation): Response
    {
        $updateTayarLocation->handle($request->user()->tayarProfile, $request->lat(), $request->lng());

        return response()->noContent();
    }
}
