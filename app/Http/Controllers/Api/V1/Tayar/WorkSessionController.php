<?php

namespace App\Http\Controllers\Api\V1\Tayar;

use App\Domains\Tayars\Actions\GoOfflineAction;
use App\Domains\Tayars\Actions\GoOnlineAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tayar\GoOnlineRequest;
use App\Http\Resources\Api\V1\Tayar\TayarWorkStatusResource;
use Illuminate\Http\Request;

class WorkSessionController extends Controller
{
    /**
     * Go online in a mode at the current position, with the active vehicle.
     */
    public function online(GoOnlineRequest $request, GoOnlineAction $goOnline): TayarWorkStatusResource
    {
        $profile = $goOnline->handle($request->user()->tayarProfile, $request->lat(), $request->lng(), $request->settings());

        return TayarWorkStatusResource::make($profile->load(['currentWorkSession.mode', 'currentWorkSession.area']));
    }

    /**
     * Go offline and close the work session; refused while holding an accepted, picked-up or ongoing order.
     */
    public function offline(Request $request, GoOfflineAction $goOffline): TayarWorkStatusResource
    {
        $profile = $request->user()->tayarProfile;

        $goOffline->handle($profile, refuseWhileHoldingOrders: true);

        return TayarWorkStatusResource::make($profile->refresh()->load(['currentWorkSession.mode', 'currentWorkSession.area']));
    }
}
