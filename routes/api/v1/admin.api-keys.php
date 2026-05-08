<?php

use App\Http\Controllers\Api\V1\AdminApiKeyController;
use App\Models\ApiKey;
use Illuminate\Support\Facades\Route;

// Bind: solo api keys cuyo owner es service account
Route::bind('apiKey', function ($value) {
    return ApiKey::query()
        ->where('public_id', $value)
        ->whereHas('owner', fn ($q) => $q->where('type', 'service'))
        ->firstOrFail();
});

/*
|--------------------------------------------------------------------------
| Admin API Keys Routes
|--------------------------------------------------------------------------
| Prefix: /api/v1/admin
| Middleware: auth:api, throttle:admin, user.active, user.verified, jwt.not_revoked, perm:admin.api_keys.manage
*/
Route::prefix('admin')
    ->middleware(['auth:api', 'throttle:admin', 'user.active', 'user.verified', 'jwt.not_revoked', 'perm:admin.api_keys.manage'])
    ->group(function () {
        Route::post('api-keys/{apiKey}/rotate', [AdminApiKeyController::class, 'rotate']);
        Route::delete('api-keys/{apiKey}', [AdminApiKeyController::class, 'destroy']);
    });
