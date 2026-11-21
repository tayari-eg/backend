<?php

namespace App\Http\Middleware;

use App\Http\ClientContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetRequestLocale
{
    /**
     * Use the locale from Accept-Language (Arabic by default) for messages and translatable fields.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Runs first for every request, so the scoped context is rebuilt from this request even when the app is reused.
        app()->forgetInstance(ClientContext::class);

        app()->setLocale(app(ClientContext::class)->locale->value);

        return $next($request);
    }
}
