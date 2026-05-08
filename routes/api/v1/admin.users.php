<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AdminUserController;

Route::prefix('admin')
    ->middleware(['auth:api', 'throttle:admin', 'user.active', 'user.verified', 'jwt.not_revoked', 'perm:admin.users.manage'])
    ->group(function () {
        Route::apiResource('users', AdminUserController::class);
    });
