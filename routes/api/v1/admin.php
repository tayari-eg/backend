<?php

use App\Domains\Accounts\Enums\StaffPermission;
use App\Http\Controllers\Api\V1\Admin\AreaController;
use App\Http\Controllers\Api\V1\Admin\BusinessProfileReviewController;
use App\Http\Controllers\Api\V1\Admin\BusinessTypeController;
use App\Http\Controllers\Api\V1\Admin\CategoryController;
use App\Http\Controllers\Api\V1\Admin\CategoryIconController;
use App\Http\Controllers\Api\V1\Admin\ModeController;
use App\Http\Controllers\Api\V1\Admin\NationalIdReviewController;
use App\Http\Controllers\Api\V1\Admin\OrderController;
use App\Http\Controllers\Api\V1\Admin\OrderOfferController;
use App\Http\Controllers\Api\V1\Admin\PricingRuleController;
use App\Http\Controllers\Api\V1\Admin\RatingController;
use App\Http\Controllers\Api\V1\Admin\StaffController;
use App\Http\Controllers\Api\V1\Admin\TayarApplicationReviewController;
use App\Http\Controllers\Api\V1\Admin\TayarController;
use App\Http\Controllers\Api\V1\Admin\TayarRankController;
use App\Http\Controllers\Api\V1\Admin\TayarRankHistoryController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Admin\VehicleTypeController;
use App\Http\Controllers\Api\V1\Admin\WalletAdjustmentReasonController;
use App\Http\Controllers\Api\V1\Admin\WalletController;
use App\Http\Controllers\Api\V1\Admin\WalletPostingController;
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

    Route::middleware('permission:'.StaffPermission::TayarsView->value)->group(function (): void {
        Route::get('tayar-applications', [TayarApplicationReviewController::class, 'index'])->name('tayar-applications.index');
        Route::get('tayar-applications/{tayarProfile}', [TayarApplicationReviewController::class, 'show'])->name('tayar-applications.show');
        Route::get('tayars', [TayarController::class, 'index'])->name('tayars.index');
        Route::get('tayars/{tayar}', [TayarController::class, 'show'])->name('tayars.show');
    });

    Route::middleware('permission:'.StaffPermission::TayarsReview->value)->group(function (): void {
        Route::post('tayar-applications/{tayarProfile}/approve', [TayarApplicationReviewController::class, 'approve'])->name('tayar-applications.approve');
        Route::post('tayar-applications/{tayarProfile}/reject', [TayarApplicationReviewController::class, 'reject'])->name('tayar-applications.reject');
    });

    Route::middleware('permission:'.StaffPermission::VehicleTypesView->value)->group(function (): void {
        Route::get('vehicle-types', [VehicleTypeController::class, 'index'])->name('vehicle-types.index');
        Route::get('vehicle-types/{vehicleType}', [VehicleTypeController::class, 'show'])->name('vehicle-types.show');
    });

    Route::middleware('permission:'.StaffPermission::VehicleTypesManage->value)->group(function (): void {
        Route::post('vehicle-types', [VehicleTypeController::class, 'store'])->name('vehicle-types.store');
        Route::patch('vehicle-types/{vehicleType}', [VehicleTypeController::class, 'update'])->name('vehicle-types.update');
        Route::post('vehicle-types/{vehicleType}/activate', [VehicleTypeController::class, 'activate'])->name('vehicle-types.activate');
        Route::post('vehicle-types/{vehicleType}/deactivate', [VehicleTypeController::class, 'deactivate'])->name('vehicle-types.deactivate');
        Route::delete('vehicle-types/{vehicleType}', [VehicleTypeController::class, 'destroy'])->name('vehicle-types.destroy');
    });

    Route::middleware('permission:'.StaffPermission::CategoriesView->value)->group(function (): void {
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
    });

    Route::middleware('permission:'.StaffPermission::CategoriesManage->value)->group(function (): void {
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::patch('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::post('categories/{category}/activate', [CategoryController::class, 'activate'])->name('categories.activate');
        Route::post('categories/{category}/deactivate', [CategoryController::class, 'deactivate'])->name('categories.deactivate');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        // POST, not PATCH: PHP doesn't parse multipart bodies on PATCH.
        Route::post('categories/{category}/icon', [CategoryIconController::class, 'store'])->name('categories.icon.store');
        Route::delete('categories/{category}/icon', [CategoryIconController::class, 'destroy'])->name('categories.icon.destroy');
    });

    Route::middleware('permission:'.StaffPermission::ModesView->value)->group(function (): void {
        Route::get('modes', [ModeController::class, 'index'])->name('modes.index');
        Route::get('modes/{mode}', [ModeController::class, 'show'])->name('modes.show');
    });

    // Modes are fixed in code: no create or delete.
    Route::middleware('permission:'.StaffPermission::ModesManage->value)->group(function (): void {
        Route::patch('modes/{mode}', [ModeController::class, 'update'])->name('modes.update');
        Route::post('modes/{mode}/activate', [ModeController::class, 'activate'])->name('modes.activate');
        Route::post('modes/{mode}/deactivate', [ModeController::class, 'deactivate'])->name('modes.deactivate');
    });

    Route::middleware('permission:'.StaffPermission::AreasView->value)->group(function (): void {
        Route::get('areas', [AreaController::class, 'index'])->name('areas.index');
        Route::get('areas/{area}', [AreaController::class, 'show'])->name('areas.show');
    });

    Route::middleware('permission:'.StaffPermission::AreasManage->value)->group(function (): void {
        Route::post('areas', [AreaController::class, 'store'])->name('areas.store');
        Route::patch('areas/{area}', [AreaController::class, 'update'])->name('areas.update');
        Route::post('areas/{area}/activate', [AreaController::class, 'activate'])->name('areas.activate');
        Route::post('areas/{area}/deactivate', [AreaController::class, 'deactivate'])->name('areas.deactivate');
        Route::delete('areas/{area}', [AreaController::class, 'destroy'])->name('areas.destroy');
    });

    Route::middleware('permission:'.StaffPermission::PricingRulesView->value)->group(function (): void {
        Route::get('pricing-rules', [PricingRuleController::class, 'index'])->name('pricing-rules.index');
        Route::get('pricing-rules/{pricingRule}', [PricingRuleController::class, 'show'])->name('pricing-rules.show');
    });

    Route::middleware('permission:'.StaffPermission::PricingRulesManage->value)->group(function (): void {
        Route::post('pricing-rules', [PricingRuleController::class, 'store'])->name('pricing-rules.store');
        Route::patch('pricing-rules/{pricingRule}', [PricingRuleController::class, 'update'])->name('pricing-rules.update');
        Route::post('pricing-rules/{pricingRule}/activate', [PricingRuleController::class, 'activate'])->name('pricing-rules.activate');
        Route::post('pricing-rules/{pricingRule}/deactivate', [PricingRuleController::class, 'deactivate'])->name('pricing-rules.deactivate');
        Route::delete('pricing-rules/{pricingRule}', [PricingRuleController::class, 'destroy'])->name('pricing-rules.destroy');
    });

    Route::middleware('permission:'.StaffPermission::OrdersView->value)->group(function (): void {
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('orders/{order}/offers', OrderOfferController::class)->name('orders.offers.index');
    });

    Route::middleware('permission:'.StaffPermission::OrdersManage->value)->group(function (): void {
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('orders/{order}/not-delivered', [OrderController::class, 'notDelivered'])->name('orders.not-delivered');
        Route::post('orders/{order}/resend-codes', [OrderController::class, 'resendCodes'])->name('orders.resend-codes');
    });

    Route::middleware('permission:'.StaffPermission::WalletsView->value)->group(function (): void {
        Route::get('wallets', [WalletController::class, 'index'])->name('wallets.index');
        Route::get('wallets/{wallet}', [WalletController::class, 'show'])->name('wallets.show');
        Route::get('wallets/{wallet}/transactions', [WalletController::class, 'transactions'])->name('wallets.transactions');
        Route::get('wallet-adjustment-reasons', [WalletAdjustmentReasonController::class, 'index'])->name('wallet-adjustment-reasons.index');
        Route::get('wallet-adjustment-reasons/{walletAdjustmentReason}', [WalletAdjustmentReasonController::class, 'show'])->name('wallet-adjustment-reasons.show');
    });

    Route::post('wallets/{wallet}/settlements', [WalletController::class, 'settle'])
        ->middleware('permission:'.StaffPermission::WalletsSettle->value)
        ->name('wallets.settlements.store');

    Route::middleware('permission:'.StaffPermission::WalletsAdjust->value)->group(function (): void {
        Route::post('wallets/{wallet}/adjustments', [WalletController::class, 'adjust'])->name('wallets.adjustments.store');
        Route::post('wallet-postings/{walletPosting}/reverse', [WalletPostingController::class, 'reverse'])->name('wallet-postings.reverse');
        Route::post('wallet-adjustment-reasons', [WalletAdjustmentReasonController::class, 'store'])->name('wallet-adjustment-reasons.store');
        Route::patch('wallet-adjustment-reasons/{walletAdjustmentReason}', [WalletAdjustmentReasonController::class, 'update'])->name('wallet-adjustment-reasons.update');
        Route::post('wallet-adjustment-reasons/{walletAdjustmentReason}/activate', [WalletAdjustmentReasonController::class, 'activate'])->name('wallet-adjustment-reasons.activate');
        Route::post('wallet-adjustment-reasons/{walletAdjustmentReason}/deactivate', [WalletAdjustmentReasonController::class, 'deactivate'])->name('wallet-adjustment-reasons.deactivate');
        Route::delete('wallet-adjustment-reasons/{walletAdjustmentReason}', [WalletAdjustmentReasonController::class, 'destroy'])->name('wallet-adjustment-reasons.destroy');
    });

    Route::middleware('permission:'.StaffPermission::RatingsView->value)->group(function (): void {
        Route::get('ratings', [RatingController::class, 'index'])->name('ratings.index');
        Route::get('ratings/{rating}', [RatingController::class, 'show'])->name('ratings.show');
    });

    Route::middleware('permission:'.StaffPermission::RatingsModerate->value)->group(function (): void {
        Route::post('ratings/{rating}/hide-comment', [RatingController::class, 'hideComment'])->name('ratings.hide-comment');
        Route::post('ratings/{rating}/unhide-comment', [RatingController::class, 'unhideComment'])->name('ratings.unhide-comment');
    });

    Route::middleware('permission:'.StaffPermission::RanksView->value)->group(function (): void {
        Route::get('tayar-ranks', [TayarRankController::class, 'index'])->name('tayar-ranks.index');
        Route::get('tayar-ranks/{tayarRank}', [TayarRankController::class, 'show'])->name('tayar-ranks.show');
        Route::get('tayars/{tayar}/rank-history', TayarRankHistoryController::class)->name('tayars.rank-history');
    });

    Route::patch('tayar-ranks/{tayarRank}', [TayarRankController::class, 'update'])
        ->middleware('permission:'.StaffPermission::RanksManage->value)
        ->name('tayar-ranks.update');

    Route::middleware('permission:'.StaffPermission::StaffManage->value)->group(function (): void {
        Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
        Route::put('staff/{staff}/role', [StaffController::class, 'updateRole'])->name('staff.role.update');
        Route::post('staff/{staff}/block', [StaffController::class, 'block'])->name('staff.block');
        Route::post('staff/{staff}/unblock', [StaffController::class, 'unblock'])->name('staff.unblock');
    });
});
