<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AdminRoleController;

Route::prefix('admin')
    ->middleware(['auth:api', 'throttle:admin', 'user.active', 'user.verified', 'jwt.not_revoked', 'perm:admin.roles.manage'])
    ->group(function () {
        Route::apiResource('roles', AdminRoleController::class);
        Route::put('roles/{role}/permissions', [AdminRoleController::class, 'syncPermissions']);
    });
