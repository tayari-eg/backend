<?php

use App\Http\Controllers\Api\V1\Public\AppConfigController;
use App\Http\Controllers\Api\V1\Public\AreaController;
use App\Http\Controllers\Api\V1\Public\BusinessTypeController;
use App\Http\Controllers\Api\V1\Public\CategoryController;
use App\Http\Controllers\Api\V1\Public\MediaController;
use App\Http\Controllers\Api\V1\Public\ModeController;
use App\Http\Controllers\Api\V1\Public\OrderReasonController;
use App\Http\Controllers\Api\V1\Public\TrackingController;
use App\Http\Controllers\Api\V1\Public\VehicleTypeController;
use App\Http\Middleware\EnsureAppVersionIsSupported;
use Illuminate\Support\Facades\Route;

// Outdated apps must still be able to read the minimum and latest versions.
Route::get('app-config', AppConfigController::class)
    ->withoutMiddleware(EnsureAppVersionIsSupported::class)
    ->name('app-config');

Route::get('business-types', [BusinessTypeController::class, 'index'])->name('business-types.index');
Route::get('vehicle-types', [VehicleTypeController::class, 'index'])->name('vehicle-types.index');
Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('modes', [ModeController::class, 'index'])->name('modes.index');
Route::get('areas', [AreaController::class, 'index'])->name('areas.index');
Route::get('order-reasons', [OrderReasonController::class, 'index'])->name('order-reasons.index');

Route::get('track/{trackingToken}', [TrackingController::class, 'show'])
    ->middleware('throttle:tracking')
    ->where('trackingToken', '[A-Za-z0-9]{40}')
    ->name('tracking.show');
Route::post('track/{trackingToken}/resend-codes', [TrackingController::class, 'resendCodes'])
    ->middleware(['throttle:tracking', 'throttle:tracking-resend-codes'])
    ->where('trackingToken', '[A-Za-z0-9]{40}')
    ->name('tracking.resend-codes');

// The signature is the access check: URLs are only handed out to users the MediaPolicy allows.
Route::get('media/{media}', MediaController::class)->middleware('signed')->name('media.show');
