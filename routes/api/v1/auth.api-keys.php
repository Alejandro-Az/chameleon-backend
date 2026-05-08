<?php

use App\Http\Controllers\Api\V1\AuthApiKeyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Keys Exchange Routes
|--------------------------------------------------------------------------
| Prefix: /api/v1/auth/api-keys
| Throttled dynamically in controller
*/
Route::prefix('auth/api-keys')->group(function () {
    Route::post('exchange', [AuthApiKeyController::class, 'exchange']);
});
