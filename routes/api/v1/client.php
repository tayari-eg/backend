<?php

use App\Http\Controllers\Api\V1\Client\BusinessDocumentController;
use App\Http\Controllers\Api\V1\Client\BusinessProfileController;
use App\Http\Controllers\Api\V1\Client\NationalIdController;
use App\Http\Controllers\Api\V1\Client\OrderController;
use App\Http\Controllers\Api\V1\Client\OrderEstimateController;
use App\Http\Controllers\Api\V1\Client\OrderRatingController;
use App\Http\Controllers\Api\V1\Client\TayarDocumentController;
use App\Http\Controllers\Api\V1\Client\TayarProfileController;
use App\Http\Controllers\Api\V1\Client\TayarVehicleController;
use App\Http\Controllers\Api\V1\Client\VehicleDocumentController;
use App\Http\Controllers\Api\V1\Client\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'client'])->group(function (): void {
    Route::get('business-profile', [BusinessProfileController::class, 'show'])->name('business-profile.show');
    Route::post('business-profile', [BusinessProfileController::class, 'store'])->name('business-profile.store');
    Route::patch('business-profile', [BusinessProfileController::class, 'update'])->name('business-profile.update');
    Route::post('business-profile/documents/{collection}', BusinessDocumentController::class)->name('business-profile.documents.store');

    Route::post('national-id', NationalIdController::class)->name('national-id.store');

    Route::get('tayar-profile', [TayarProfileController::class, 'show'])->name('tayar-profile.show');
    Route::post('tayar-profile', [TayarProfileController::class, 'store'])->name('tayar-profile.store');
    Route::patch('tayar-profile', [TayarProfileController::class, 'update'])->name('tayar-profile.update');
    Route::post('tayar-profile/documents/{collection}', TayarDocumentController::class)->name('tayar-profile.documents.store');
    Route::patch('tayar-profile/vehicle', [TayarVehicleController::class, 'update'])->name('tayar-profile.vehicle.update');
    Route::post('tayar-profile/vehicle/documents/{collection}', VehicleDocumentController::class)->name('tayar-profile.vehicle.documents.store');

    Route::post('orders/estimate', OrderEstimateController::class)->name('orders.estimate');
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::put('orders/{order}/rating', [OrderRatingController::class, 'update'])->name('orders.rating.update');

    Route::get('wallet', [WalletController::class, 'show'])->name('wallet.show');
    Route::get('wallet/transactions', [WalletController::class, 'transactions'])->name('wallet.transactions');
});
