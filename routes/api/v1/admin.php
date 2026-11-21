<?php

use App\Domains\Accounts\Enums\StaffPermission;
use App\Http\Controllers\Api\V1\Admin\BusinessProfileReviewController;
use App\Http\Controllers\Api\V1\Admin\NationalIdReviewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'staff'])->group(function (): void {
    Route::get('business-profiles', [BusinessProfileReviewController::class, 'index'])
        ->middleware('permission:'.StaffPermission::BusinessProfilesView->value)
        ->name('business-profiles.index');
    Route::post('business-profiles/{businessProfile}/approve', [BusinessProfileReviewController::class, 'approve'])
        ->middleware('permission:'.StaffPermission::BusinessProfilesReview->value)
        ->name('business-profiles.approve');
    Route::post('business-profiles/{businessProfile}/reject', [BusinessProfileReviewController::class, 'reject'])
        ->middleware('permission:'.StaffPermission::BusinessProfilesReview->value)
        ->name('business-profiles.reject');

    Route::get('national-ids', [NationalIdReviewController::class, 'index'])
        ->middleware('permission:'.StaffPermission::NationalIdsView->value)
        ->name('national-ids.index');
    Route::post('users/{user}/national-id/verify', [NationalIdReviewController::class, 'verify'])
        ->middleware('permission:'.StaffPermission::NationalIdsReview->value)
        ->name('national-ids.verify');
    Route::post('users/{user}/national-id/reject', [NationalIdReviewController::class, 'reject'])
        ->middleware('permission:'.StaffPermission::NationalIdsReview->value)
        ->name('national-ids.reject');
});
