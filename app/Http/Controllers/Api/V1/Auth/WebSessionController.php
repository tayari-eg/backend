<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\Accounts\Actions\StartWebSessionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\WebLoginRequest;
use App\Http\Resources\Api\V1\Auth\UserResource;
use Symfony\Component\HttpFoundation\Response;

class WebSessionController extends Controller
{
    /**
     * React website and admin panel login with Sanctum's session cookie. Call GET /sanctum/csrf-cookie first.
     *
     * @unauthenticated
     */
    public function __invoke(WebLoginRequest $request, StartWebSessionAction $startWebSession): UserResource
    {
        // The session only exists for requests from a stateful (SANCTUM_STATEFUL_DOMAINS) origin.
        if (! $request->hasSession()) {
            abort(Response::HTTP_BAD_REQUEST, __('auth.session_unavailable'));
        }

        $user = $startWebSession->handle($request->phoneNumber(), $request->validated('password'), $request->session());

        return UserResource::make($user->load(UserResource::RELATIONS));
    }
}
