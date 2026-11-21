<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\Accounts\Actions\IssueMobileTokenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\MobileLoginRequest;
use App\Http\Resources\Api\V1\Auth\UserResource;
use Illuminate\Http\JsonResponse;

class MobileTokenController extends Controller
{
    /**
     * Mobile app login. Returns a Sanctum bearer token for the device.
     *
     * @unauthenticated
     */
    public function __invoke(MobileLoginRequest $request, IssueMobileTokenAction $issueMobileToken): JsonResponse
    {
        ['user' => $user, 'token' => $token] = $issueMobileToken->handle(
            $request->phoneNumber(),
            $request->validated('password'),
            $request->validated('device_name'),
        );

        return UserResource::make($user->load(UserResource::RELATIONS))
            ->additional([
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
            ])
            ->response();
    }
}
