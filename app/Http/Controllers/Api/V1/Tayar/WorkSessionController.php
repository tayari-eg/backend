<?php

namespace App\Http\Controllers\Api\V1\Tayar;

use App\Domains\Tayars\Actions\GoOfflineAction;
use App\Domains\Tayars\Actions\GoOnlineAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tayar\TayarPositionRequest;
use App\Http\Resources\Api\V1\Tayar\TayarWorkStatusResource;
use Illuminate\Http\Request;

class WorkSessionController extends Controller
{
    /**
     * Go online at the current position, with the active vehicle.
     */
    public function online(TayarPositionRequest $request, GoOnlineAction $goOnline): TayarWorkStatusResource
    {
        $profile = $goOnline->handle($request->user()->tayarProfile, $request->lat(), $request->lng());

        return TayarWorkStatusResource::make($profile->load('currentWorkSession'));
    }

    /**
     * Go offline and close the work session.
     */
    public function offline(Request $request, GoOfflineAction $goOffline): TayarWorkStatusResource
    {
        $profile = $request->user()->tayarProfile;

        $goOffline->handle($profile);

        return TayarWorkStatusResource::make($profile->refresh()->load('currentWorkSession'));
    }
}
