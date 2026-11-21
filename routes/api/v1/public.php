<?php

use App\Http\Controllers\Api\V1\Public\AppConfigController;
use App\Http\Middleware\EnsureAppVersionIsSupported;
use Illuminate\Support\Facades\Route;

// Outdated apps must still be able to read the minimum and latest versions.
Route::get('app-config', AppConfigController::class)
    ->withoutMiddleware(EnsureAppVersionIsSupported::class)
    ->name('app-config');
