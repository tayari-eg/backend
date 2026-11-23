<?php

use App\Http\Controllers\Api\V1\Tayar\LocationController;
use App\Http\Controllers\Api\V1\Tayar\OfferController;
use App\Http\Controllers\Api\V1\Tayar\OrderController;
use App\Http\Controllers\Api\V1\Tayar\WorkSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tayar'])->group(function (): void {
    Route::post('online', [WorkSessionController::class, 'online'])->name('online');
    Route::post('offline', [WorkSessionController::class, 'offline'])->name('offline');
    Route::post('location', LocationController::class)->middleware('throttle:tayar-location')->name('location');

    Route::get('offers', [OfferController::class, 'index'])->name('offers.index');
    Route::post('offers/{offer}/accept', [OfferController::class, 'accept'])->name('offers.accept');
    Route::post('offers/{offer}/reject', [OfferController::class, 'reject'])->name('offers.reject');

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
});
