<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AdminPermissionController;

Route::prefix('admin')
    ->middleware(['auth:api', 'throttle:admin', 'user.active', 'user.verified', 'jwt.not_revoked', 'perm:admin.permissions.manage'])
    ->group(function () {
        Route::get('permissions', [AdminPermissionController::class, 'index']);
    });
