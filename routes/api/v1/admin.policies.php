<?php

use App\Http\Controllers\Api\V1\AdminPolicyController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/policies')
    ->middleware(['auth:api', 'throttle:admin', 'user.active', 'user.verified', 'jwt.not_revoked'])
    ->group(function () {
        Route::get('/', [AdminPolicyController::class, 'index'])
            ->middleware('perm:admin.policies.view');

        Route::get('{key}', [AdminPolicyController::class, 'show'])
            ->where('key', '[A-Za-z0-9._-]+')
            ->middleware('perm:admin.policies.view');

        Route::patch('/', [AdminPolicyController::class, 'update'])
            ->middleware('perm:admin.policies.manage');
    });
