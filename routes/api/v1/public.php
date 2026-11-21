<?php

use App\Http\Controllers\Api\V1\Public\AppConfigController;
use App\Http\Controllers\Api\V1\Public\BusinessTypeController;
use App\Http\Controllers\Api\V1\Public\MediaController;
use App\Http\Middleware\EnsureAppVersionIsSupported;
use Illuminate\Support\Facades\Route;

// Outdated apps must still be able to read the minimum and latest versions.
Route::get('app-config', AppConfigController::class)
    ->withoutMiddleware(EnsureAppVersionIsSupported::class)
    ->name('app-config');

Route::get('business-types', [BusinessTypeController::class, 'index'])->name('business-types.index');

// The signature is the access check: URLs are only handed out to users the MediaPolicy allows.
Route::get('media/{media}', MediaController::class)->middleware('signed')->name('media.show');
