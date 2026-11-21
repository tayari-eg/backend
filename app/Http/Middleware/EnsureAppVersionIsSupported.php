<?php

namespace App\Http\Middleware;

use App\Http\ClientContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppVersionIsSupported
{
    /**
     * Share the client's platform and app version with the logs, and block mobile app versions below the configured minimum.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientContext = app(ClientContext::class);

        Context::add([
            'platform' => $clientContext->platform?->value,
            'app_version' => $clientContext->appVersion,
        ]);

        $minimumVersion = config("tayari.min_supported_versions.{$clientContext->platform?->value}");

        if ($clientContext->isBelowMinimumVersion($minimumVersion)) {
            Log::info('Blocked unsupported app version', ['minimum_version' => $minimumVersion]);

            abort(Response::HTTP_UPGRADE_REQUIRED, __('This version of the app is no longer supported. Please update the app to continue.'));
        }

        return $next($request);
    }
}
