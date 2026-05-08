<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AdminDashboardController;

Route::prefix('admin')
    ->middleware(['auth:api', 'throttle:admin', 'user.active', 'user.verified', 'jwt.not_revoked'])
    ->group(function () {
        // Any authenticated, active, verified user with a non-revoked token can hit this.
        // The controller itself enforces granular permissions and filters data accordingly.
        Route::get('dashboard/summary', AdminDashboardController::class);
    });
