<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Domains\Orders\Actions\ResendReceiverCodesAction;
use App\Domains\Orders\Models\Order;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Public\TrackingResource;
use Illuminate\Http\Response;

class TrackingController extends Controller
{
    /**
     * The receiver's tracking page: the order's status, the tayar's first name and vehicle, and the last position while
     * the goods are on the way. No phones and no codes.
     *
     * @unauthenticated
     */
    public function show(#[\SensitiveParameter] string $trackingToken): TrackingResource
    {
        return TrackingResource::make($this->order($trackingToken)->load(['sender.businessProfile', 'tayar.tayarProfile', 'workSession.vehicle.vehicleType']));
    }

    /**
     * Send new delivery and refusal codes by SMS to the receiver's phone, while the goods are with the tayar.
     *
     * @unauthenticated
     */
    public function resendCodes(#[\SensitiveParameter] string $trackingToken, ResendReceiverCodesAction $resendReceiverCodes): Response
    {
        $resendReceiverCodes->handle($this->order($trackingToken));

        return response()->noContent();
    }

    private function order(#[\SensitiveParameter] string $trackingToken): Order
    {
        return Order::query()->where('tracking_token', $trackingToken)->firstOrFail();
    }
}
