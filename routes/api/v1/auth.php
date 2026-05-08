<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AuthSessionController;
use App\Http\Controllers\Api\V1\ForgotPasswordController;
use App\Http\Controllers\Api\V1\RegisterController;
use App\Http\Controllers\Api\V1\ResetPasswordController;
use App\Http\Controllers\Api\V1\EmailVerificationController;
use App\Http\Controllers\Api\V1\EmailVerificationNotificationController;

/*
|--------------------------------------------------------------------------
| Auth (public)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('forgot-password', ForgotPasswordController::class);
    Route::post('reset-password', ResetPasswordController::class);
    
    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->name('auth.verify-email');

    Route::post('register', [RegisterController::class, 'store'])
        ->middleware('auth.register.throttle');
});

/*
|--------------------------------------------------------------------------
| Auth (protected)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')
    ->middleware(['auth:api', 'user.active', 'jwt.not_revoked'])
    ->group(function () {
        // Endpoints that do NOT require verification
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'resend']);
        Route::get('email/verification-status', [EmailVerificationController::class, 'status']);

        // Endpoints that DO require verification
        Route::middleware('user.verified')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            
            Route::prefix('sessions')->group(function () {
                Route::get('/', [AuthSessionController::class, 'index']);
                Route::delete('{session}', [AuthSessionController::class, 'destroy']);
                Route::post('revoke-others', [AuthSessionController::class, 'revokeOthers']);
                Route::post('revoke-all', [AuthSessionController::class, 'revokeAll']);
            });
        });
    });

