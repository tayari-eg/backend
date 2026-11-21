<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\Accounts\Actions\LogoutAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    /**
     * Log out: deletes the current token on mobile, ends the session on the web.
     */
    public function __invoke(Request $request, LogoutAction $logout): JsonResponse
    {
        $logout->handle($request->user(), $request->hasSession() ? $request->session() : null);

        return response()->json(['message' => __('auth.logged_out')]);
    }
}
