<?php

use App\Http\Controllers\Api\V1\Auth\DeviceController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\MobileTokenController;
use App\Http\Controllers\Api\V1\Auth\PasswordController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResendOtpController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\Auth\VerifyPhoneController;
use App\Http\Controllers\Api\V1\Auth\WebSessionController;
use Illuminate\Support\Facades\Route;

Route::post('register', RegisterController::class)->middleware('throttle:otp-send')->name('register');
Route::post('verify-phone', VerifyPhoneController::class)->middleware('throttle:otp-verify')->name('verify-phone');
Route::post('otp/resend', ResendOtpController::class)->middleware('throttle:otp-send')->name('otp.resend');
Route::post('forgot-password', ForgotPasswordController::class)->middleware('throttle:otp-send')->name('forgot-password');
Route::post('reset-password', ResetPasswordController::class)->middleware('throttle:otp-verify')->name('reset-password');

Route::post('token', MobileTokenController::class)->middleware('throttle:login')->name('token');
Route::post('login', WebSessionController::class)->middleware('throttle:login')->name('login');

// Logout stays open to blocked users, so they can always end their own session.
Route::post('logout', LogoutController::class)->middleware('auth:sanctum')->name('logout');

Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
    Route::get('me', MeController::class)->name('me');
    Route::put('password', PasswordController::class)->name('password.update');

    Route::post('devices', [DeviceController::class, 'store'])->name('devices.store');
    Route::delete('devices', [DeviceController::class, 'destroy'])->name('devices.destroy');
});
