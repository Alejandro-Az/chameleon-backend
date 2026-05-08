<?php

use App\Http\Controllers\Api\V1\AdminLoginAttemptController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Security Routes
|--------------------------------------------------------------------------
| Prefix: /api/v1/admin/security
| Middleware: auth:api, throttle:admin, user.active, user.verified, jwt.not_revoked, perm:admin.security.view
*/
Route::prefix('admin/security')
    ->middleware(['auth:api', 'throttle:admin', 'user.active', 'user.verified', 'jwt.not_revoked', 'perm:admin.security.view'])
    ->group(function () {
        Route::get('login-attempts', [AdminLoginAttemptController::class, 'index']);
    });
