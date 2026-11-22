<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTayar
{
    public function __construct(private EnsureUserIsClient $ensureUserIsClient) {}

    /**
     * Tayar routes are for clients (active, not staff, verified phone when required) with an approved tayar profile.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $this->ensureUserIsClient->handle($request, function (Request $request) use ($next): Response {
            /** @var User $user */
            $user = $request->user();

            if ($user->tayarProfile?->isApproved() !== true) {
                abort(Response::HTTP_FORBIDDEN, __('tayars.not_approved'));
            }

            return $next($request);
        });
    }
}
