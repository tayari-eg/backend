<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Blocked users keep a live web session until it expires, so every authenticated route checks the status again.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->ensureActive($request->user());

        return $next($request);
    }

    public function ensureActive(User $user): void
    {
        if ($user->isBlocked()) {
            abort(Response::HTTP_FORBIDDEN, __('auth.blocked'));
        }
    }
}
