<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsClient
{
    public function __construct(private EnsureUserIsActive $ensureUserIsActive) {}

    /**
     * A client is an active user with no staff role, and a verified phone when verification is required.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();

        $this->ensureUserIsActive->ensureActive($user);

        if ($user->isStaff()) {
            abort(Response::HTTP_FORBIDDEN, __('auth.client_only'));
        }

        if (config()->boolean('tayari.auth.require_phone_verification', true) && ! $user->hasVerifiedPhone()) {
            abort(Response::HTTP_FORBIDDEN, __('auth.phone_not_verified'));
        }

        return $next($request);
    }
}
