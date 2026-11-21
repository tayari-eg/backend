<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\Accounts\Actions\RegisterDeviceAction;
use App\Domains\Accounts\Actions\RemoveDeviceAction;
use App\Domains\Accounts\Enums\Platform;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\DestroyDeviceRequest;
use App\Http\Requests\Api\V1\Auth\StoreDeviceRequest;
use App\Http\Resources\Api\V1\Auth\DeviceResource;
use Illuminate\Http\Response;

class DeviceController extends Controller
{
    /**
     * Save the FCM push token for this device. A token saved for another user moves to this one.
     */
    public function store(StoreDeviceRequest $request, RegisterDeviceAction $registerDevice): DeviceResource
    {
        return DeviceResource::make($registerDevice->handle(
            $request->user(),
            Platform::from($request->validated('platform')),
            $request->validated('push_token'),
            $request->validated('app_version'),
        ));
    }

    /**
     * Remove the FCM push token, e.g. on logout.
     */
    public function destroy(DestroyDeviceRequest $request, RemoveDeviceAction $removeDevice): Response
    {
        $removeDevice->handle($request->user(), $request->validated('push_token'));

        return response()->noContent();
    }
}
