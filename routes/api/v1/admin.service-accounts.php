<?php

use App\Http\Controllers\Api\V1\AdminApiKeyController;
use App\Http\Controllers\Api\V1\AdminServiceAccountController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Bind: solo users.type=service
Route::bind('serviceAccount', function ($value) {
    return User::query()
        ->where('public_id', $value)
        ->where('type', 'service')
        ->firstOrFail();
});

/*
|--------------------------------------------------------------------------
| Admin Service Accounts Routes
|--------------------------------------------------------------------------
| Prefix: /api/v1/admin/service-accounts
| Middleware: auth:api, throttle:admin, user.active, user.verified, jwt.not_revoked, perm:admin.service_accounts.manage
*/
Route::prefix('admin')
    ->middleware(['auth:api', 'throttle:admin', 'user.active', 'user.verified', 'jwt.not_revoked', 'perm:admin.service_accounts.manage'])
    ->group(function () {
        Route::apiResource('service-accounts', AdminServiceAccountController::class)
            ->only(['index', 'store', 'show', 'update']);
    });

Route::prefix('admin')
    ->middleware(['auth:api', 'throttle:admin', 'user.active', 'user.verified', 'jwt.not_revoked', 'perm:admin.api_keys.manage'])
    ->group(function () {
        Route::get('service-accounts/{serviceAccount}/api-keys', [AdminApiKeyController::class, 'index']);
        Route::post('service-accounts/{serviceAccount}/api-keys', [AdminApiKeyController::class, 'store']);
    });
