<?php

use App\Exceptions\ApiExceptionRenderer;
use App\Http\Middleware\EnsureAppVersionIsSupported;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserIsClient;
use App\Http\Middleware\EnsureUserIsStaff;
use App\Http\Middleware\SetRequestLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Sentry\Laravel\Integration;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware(['api', EnsureAppVersionIsSupported::class])
                ->prefix('api/v1')
                ->name('api.v1.')
                ->group(function (): void {
                    Route::name('public.')->group(base_path('routes/api/v1/public.php'));
                    Route::prefix('auth')->name('auth.')->group(base_path('routes/api/v1/auth.php'));
                    Route::prefix('client')->name('client.')->group(base_path('routes/api/v1/client.php'));
                    Route::prefix('tayar')->name('tayar.')->group(base_path('routes/api/v1/tayar.php'));
                    Route::prefix('admin')->name('admin.')->group(base_path('routes/api/v1/admin.php'));
                });
        },
    )
    ->withEvents(discover: [
        __DIR__.'/../app/Domains/*/Listeners',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        // Global, so messages are translated even for routes that don't match (404).
        $middleware->prepend(SetRequestLocale::class);

        $middleware->statefulApi();

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'active' => EnsureUserIsActive::class,
            'client' => EnsureUserIsClient::class,
            'staff' => EnsureUserIsStaff::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(
            fn (Throwable $exception, Request $request) => (new ApiExceptionRenderer)->render($exception, $request),
        );
    })->create();
