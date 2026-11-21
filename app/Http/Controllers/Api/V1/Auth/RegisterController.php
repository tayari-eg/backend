<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\Accounts\Actions\RegisterUserAction;
use App\Domains\Accounts\Enums\AccountType;
use App\Http\ClientContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\Auth\UserResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RegisterController extends Controller
{
    /**
     * Register with name, phone and password.
     *
     * When `phone_verification_required` is true, the app shows the code screen. `resend_available_in` is the seconds
     * until a new code may be requested (null when no code is needed).
     *
     * @unauthenticated
     */
    public function __invoke(RegisterRequest $request, RegisterUserAction $registerUser, ClientContext $clientContext): JsonResponse
    {
        ['user' => $user, 'resend_available_in' => $resendAvailableIn] = $registerUser->handle(
            $request->validated('name'),
            $request->phoneNumber(),
            $request->validated('password'),
            AccountType::from($request->validated('account_type')),
            $clientContext->locale,
        );

        return UserResource::make($user->load(UserResource::RELATIONS))
            ->additional([
                'message' => __('auth.registered'),
                'phone_verification_required' => config()->boolean('tayari.auth.require_phone_verification', true),
                'resend_available_in' => $resendAvailableIn,
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
