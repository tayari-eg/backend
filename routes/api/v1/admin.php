<?php

use App\Domains\Accounts\Enums\StaffPermission;
use App\Http\Controllers\Api\V1\Admin\BusinessProfileReviewController;
use App\Http\Controllers\Api\V1\Admin\BusinessTypeController;
use App\Http\Controllers\Api\V1\Admin\NationalIdReviewController;
use App\Http\Controllers\Api\V1\Admin\StaffController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'staff'])->group(function (): void {
    Route::get('business-profiles', [BusinessProfileReviewController::class, 'index'])
        ->middleware('permission:'.StaffPermission::BusinessProfilesView->value)
        ->name('business-profiles.index');
    Route::get('business-profiles/{businessProfile}', [BusinessProfileReviewController::class, 'show'])
        ->middleware('permission:'.StaffPermission::BusinessProfilesView->value)
        ->name('business-profiles.show');
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

    Route::middleware('permission:'.StaffPermission::BusinessTypesView->value)->group(function (): void {
        Route::get('business-types', [BusinessTypeController::class, 'index'])->name('business-types.index');
        Route::get('business-types/{businessType}', [BusinessTypeController::class, 'show'])->name('business-types.show');
    });

    Route::middleware('permission:'.StaffPermission::BusinessTypesManage->value)->group(function (): void {
        Route::post('business-types', [BusinessTypeController::class, 'store'])->name('business-types.store');
        Route::patch('business-types/{businessType}', [BusinessTypeController::class, 'update'])->name('business-types.update');
        Route::post('business-types/{businessType}/activate', [BusinessTypeController::class, 'activate'])->name('business-types.activate');
        Route::post('business-types/{businessType}/deactivate', [BusinessTypeController::class, 'deactivate'])->name('business-types.deactivate');
        Route::delete('business-types/{businessType}', [BusinessTypeController::class, 'destroy'])->name('business-types.destroy');
    });

    Route::middleware('permission:'.StaffPermission::UsersView->value)->group(function (): void {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{customer}', [UserController::class, 'show'])->name('users.show');
    });

    Route::middleware('permission:'.StaffPermission::UsersBlock->value)->group(function (): void {
        Route::post('users/{customer}/block', [UserController::class, 'block'])->name('users.block');
        Route::post('users/{customer}/unblock', [UserController::class, 'unblock'])->name('users.unblock');
    });

    Route::middleware('permission:'.StaffPermission::StaffManage->value)->group(function (): void {
        Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
        Route::put('staff/{staff}/role', [StaffController::class, 'updateRole'])->name('staff.role.update');
        Route::post('staff/{staff}/block', [StaffController::class, 'block'])->name('staff.block');
        Route::post('staff/{staff}/unblock', [StaffController::class, 'unblock'])->name('staff.unblock');
    });
});
