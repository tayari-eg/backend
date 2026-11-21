<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStaff
{
    public function __construct(private EnsureUserIsActive $ensureUserIsActive) {}

    /**
     * Admin routes are only for active users with a staff role; each route still checks its own permission.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();

        $this->ensureUserIsActive->ensureActive($user);

        if (! $user->isStaff()) {
            abort(Response::HTTP_FORBIDDEN, __('auth.staff_only'));
        }

        return $next($request);
    }
}
