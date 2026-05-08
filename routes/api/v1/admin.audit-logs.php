<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AdminAuditLogController;

Route::prefix('admin')
    ->middleware([
        'auth:api',
        'throttle:admin',
        'user.active',
        'user.verified',
        'jwt.not_revoked',
        'perm:admin.audit.view',
    ])
    ->group(function () {
        Route::get('audit-logs', [AdminAuditLogController::class, 'index']);
    });
