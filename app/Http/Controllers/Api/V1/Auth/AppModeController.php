<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\Accounts\Actions\SwitchAppModeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\UpdateAppModeRequest;
use App\Http\Resources\Api\V1\Auth\UserResource;

class AppModeController extends Controller
{
    /**
     * Switch between client and tayar mode; the app reopens in the saved mode.
     */
    public function __invoke(UpdateAppModeRequest $request, SwitchAppModeAction $switchAppMode): UserResource
    {
        $user = $switchAppMode->handle($request->user(), $request->mode());

        return UserResource::make($user->load(UserResource::RELATIONS));
    }
}
