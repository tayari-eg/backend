<?php

use App\Http\Controllers\Api\V1\Tayar\LocationController;
use App\Http\Controllers\Api\V1\Tayar\WorkSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tayar'])->group(function (): void {
    Route::post('online', [WorkSessionController::class, 'online'])->name('online');
    Route::post('offline', [WorkSessionController::class, 'offline'])->name('offline');
    Route::post('location', LocationController::class)->middleware('throttle:tayar-location')->name('location');
});
