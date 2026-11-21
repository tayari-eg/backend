<?php

use App\Http\Controllers\Api\V1\Client\BusinessDocumentController;
use App\Http\Controllers\Api\V1\Client\BusinessProfileController;
use App\Http\Controllers\Api\V1\Client\NationalIdController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'client'])->group(function (): void {
    Route::get('business-profile', [BusinessProfileController::class, 'show'])->name('business-profile.show');
    Route::post('business-profile', [BusinessProfileController::class, 'store'])->name('business-profile.store');
    Route::patch('business-profile', [BusinessProfileController::class, 'update'])->name('business-profile.update');
    Route::post('business-profile/documents/{collection}', BusinessDocumentController::class)->name('business-profile.documents.store');

    Route::post('national-id', NationalIdController::class)->name('national-id.store');
});
